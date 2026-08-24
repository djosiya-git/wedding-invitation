param(
    [int]$Port = 8199,
    [int]$DebugPort = 9223,
    [int]$Width = 564,
    [int]$Height = 900,
    [switch]$OnlyMissing
)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$thumbDir = Join-Path $root 'assets\template-thumbs'
$edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
if (-not (Test-Path $edge)) {
    $edge = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
}
if (-not (Test-Path $edge)) {
    throw 'Microsoft Edge atau Google Chrome tidak ditemukan.'
}

function Receive-CdpMessage {
    param([System.Net.WebSockets.ClientWebSocket]$Socket)
    $buffer = New-Object byte[] 1048576
    $stream = New-Object System.IO.MemoryStream
    do {
        $segment = [ArraySegment[byte]]::new($buffer)
        $result = $Socket.ReceiveAsync($segment, [Threading.CancellationToken]::None).GetAwaiter().GetResult()
        if ($result.Count -gt 0) {
            $stream.Write($buffer, 0, $result.Count)
        }
    } while (-not $result.EndOfMessage)
    [Text.Encoding]::UTF8.GetString($stream.ToArray()) | ConvertFrom-Json
}

function Send-CdpCommand {
    param(
        [System.Net.WebSockets.ClientWebSocket]$Socket,
        [int]$Id,
        [string]$Method,
        [hashtable]$Params = @{}
    )
    $payload = @{ id = $Id; method = $Method; params = $Params } | ConvertTo-Json -Depth 8 -Compress
    $bytes = [Text.Encoding]::UTF8.GetBytes($payload)
    $Socket.SendAsync([ArraySegment[byte]]::new($bytes), [System.Net.WebSockets.WebSocketMessageType]::Text, $true, [Threading.CancellationToken]::None).GetAwaiter().GetResult() | Out-Null
    while ($true) {
        $message = Receive-CdpMessage -Socket $Socket
        if (($message.id -as [int]) -eq $Id) {
            if ($message.error) {
                $errorMessage = 'CDP command failed'
                if ($message.error.message) { $errorMessage = $message.error.message }
                throw $errorMessage
            }
            return $message.result
        }
    }
}

New-Item -ItemType Directory -Force -Path $thumbDir | Out-Null

$phpLog = Join-Path $root 'storage\thumbnail-server.log'
$phpErrorLog = Join-Path $root 'storage\thumbnail-server-error.log'
New-Item -ItemType Directory -Force -Path (Split-Path $phpLog) | Out-Null
foreach ($file in @($phpLog, $phpErrorLog)) {
    if (Test-Path $file) { Remove-Item -LiteralPath $file -Force }
}

$php = Start-Process -FilePath 'php' -ArgumentList @('-S', "127.0.0.1:$Port", '-t', '.') -WorkingDirectory $root -WindowStyle Hidden -RedirectStandardOutput $phpLog -RedirectStandardError $phpErrorLog -PassThru
$browser = $null
$socket = $null
try {
    $ready = $false
    for ($i = 0; $i -lt 30; $i++) {
        try {
            Invoke-WebRequest -UseBasicParsing -Uri "http://127.0.0.1:$Port/template_preview.php?template=animation-01&mute=1" | Out-Null
            $ready = $true
            break
        } catch {
            Start-Sleep -Milliseconds 500
        }
    }
    if (-not $ready) {
        if (Test-Path $phpLog) { Get-Content -Path $phpLog -Tail 30 | ForEach-Object { Write-Host $_ } }
        if (Test-Path $phpErrorLog) { Get-Content -Path $phpErrorLog -Tail 30 | ForEach-Object { Write-Host $_ } }
        throw 'PHP local server tidak siap.'
    }

    $profile = Join-Path $env:TEMP 'dwebin-edge-thumbnail-profile'
    New-Item -ItemType Directory -Force -Path $profile | Out-Null
    $browserLog = Join-Path $root 'storage\thumbnail-browser-error.log'
    if (Test-Path $browserLog) { Remove-Item -LiteralPath $browserLog -Force }
    $browserArgs = @(
        '--enable-logging=stderr',
        '--headless=new',
        '--no-sandbox',
        '--disable-gpu',
        '--disable-dev-shm-usage',
        '--no-first-run',
        '--no-default-browser-check',
        "--remote-debugging-port=$DebugPort",
        "--user-data-dir=$profile",
        'about:blank'
    )
    $browser = Start-Process -FilePath $edge -ArgumentList $browserArgs -WorkingDirectory $root -WindowStyle Hidden -RedirectStandardError $browserLog -PassThru

    $page = $null
    for ($i = 0; $i -lt 40; $i++) {
        try {
            $targets = Invoke-RestMethod -Uri "http://127.0.0.1:$DebugPort/json/list" -UseBasicParsing
            $page = @($targets | Where-Object { $_.type -eq 'page' })[0]
            if ($page.webSocketDebuggerUrl) { break }
        } catch {
            Start-Sleep -Milliseconds 500
        }
    }
    if (-not $page.webSocketDebuggerUrl) {
        if (Test-Path $browserLog) { Get-Content -Path $browserLog -Tail 60 | ForEach-Object { Write-Host $_ } }
        throw 'Browser CDP tidak siap.'
    }

    $socket = [System.Net.WebSockets.ClientWebSocket]::new()
    $socket.ConnectAsync([Uri]$page.webSocketDebuggerUrl, [Threading.CancellationToken]::None).GetAwaiter().GetResult()
    $id = 1
    Send-CdpCommand -Socket $socket -Id ($id++) -Method 'Page.enable' | Out-Null
    Send-CdpCommand -Socket $socket -Id ($id++) -Method 'Emulation.setDeviceMetricsOverride' -Params @{
        width = $Width
        height = $Height
        deviceScaleFactor = 1
        mobile = $false
    } | Out-Null

    $templates = Get-ChildItem -Path (Join-Path $root 'templates') -Filter '*.html' |
        Where-Object { $_.BaseName -match '^[a-z]+(-[a-z]+)*-\d+$' } |
        Sort-Object BaseName
    if ($OnlyMissing) {
        $templates = @($templates | Where-Object { -not (Test-Path (Join-Path $thumbDir "$($_.BaseName).png")) })
    }

    foreach ($template in $templates) {
        $key = $template.BaseName
        $out = Join-Path $thumbDir "$key.png"
        $url = "http://127.0.0.1:$Port/template_preview.php?template=$key&mute=1&thumb=1"
        Send-CdpCommand -Socket $socket -Id ($id++) -Method 'Page.navigate' -Params @{ url = $url } | Out-Null
        Start-Sleep -Milliseconds 6500
        $shot = Send-CdpCommand -Socket $socket -Id ($id++) -Method 'Page.captureScreenshot' -Params @{
            format = 'png'
            fromSurface = $true
            captureBeyondViewport = $false
        }
        [IO.File]::WriteAllBytes($out, [Convert]::FromBase64String($shot.data))
        Write-Host "Generated $key"
    }
} finally {
    if ($socket) {
        try { $socket.Dispose() } catch {}
    }
    if ($browser -and -not $browser.HasExited) {
        Stop-Process -Id $browser.Id -Force
    }
    if ($php -and -not $php.HasExited) {
        Stop-Process -Id $php.Id -Force
    }
}
