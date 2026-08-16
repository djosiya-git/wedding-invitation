param(
    [string]$SourceHost = 'https://inv.punakawandigital.id/wp-content/uploads/',
    [string]$PublicBase = 'https://invitation.d-webindigital.web.id/admin/assets/template-assets/punakawan/uploads/',
    [string]$LocalRoot = 'assets/template-assets/punakawan/uploads'
)

$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$templateDir = Join-Path $root 'templates'
$assetRoot = Join-Path $root $LocalRoot
New-Item -ItemType Directory -Force -Path $assetRoot | Out-Null

$normalPattern = 'https://inv\.punakawandigital\.id/wp-content/uploads/[^"'' )<>&]+'
$escapedPattern = 'https:\\/\\/inv\.punakawandigital\.id\\/wp-content\\/uploads\\/[^"'' )<>&]+'
$urls = [System.Collections.Generic.HashSet[string]]::new()

Get-ChildItem -Path $templateDir -Filter '*.html' | ForEach-Object {
    $content = Get-Content -Raw -LiteralPath $_.FullName
    [regex]::Matches($content, $normalPattern) | ForEach-Object { [void]$urls.Add($_.Value) }
    [regex]::Matches($content, $escapedPattern) | ForEach-Object {
        [void]$urls.Add(($_.Value -replace '\\/', '/'))
    }
}

$downloaded = 0
$skipped = 0
$failed = @()
foreach ($url in ($urls | Sort-Object)) {
    $relative = [Uri]::UnescapeDataString($url.Substring($SourceHost.Length)).TrimStart('/')
    $relative = $relative -replace '[?#].*$', ''
    $target = Join-Path $assetRoot ($relative -replace '/', [IO.Path]::DirectorySeparatorChar)
    $targetDir = Split-Path -Parent $target
    New-Item -ItemType Directory -Force -Path $targetDir | Out-Null
    if (Test-Path -LiteralPath $target) {
        $skipped++
        continue
    }
    try {
        Invoke-WebRequest -Uri $url -OutFile $target -UseBasicParsing
        $downloaded++
    } catch {
        if (Test-Path -LiteralPath $target) {
            Remove-Item -LiteralPath $target -Force
        }
        $failed += $url
    }
}

$rewrittenFiles = 0
$escapedSourceHost = $SourceHost -replace '/', '\/'
$escapedPublicBase = $PublicBase -replace '/', '\/'
$sourceUploadsBase = $SourceHost.TrimEnd('/')
$publicUploadsBase = $PublicBase.TrimEnd('/')
$escapedSourceUploadsBase = $sourceUploadsBase -replace '/', '\/'
$escapedPublicUploadsBase = $publicUploadsBase -replace '/', '\/'
Get-ChildItem -Path $templateDir -Filter '*.html' | ForEach-Object {
    $path = $_.FullName
    $content = Get-Content -Raw -LiteralPath $path
    $updated = $content -replace [regex]::Escape($SourceHost), $PublicBase
    $updated = $updated -replace [regex]::Escape($escapedSourceHost), $escapedPublicBase
    $updated = $updated -replace [regex]::Escape($sourceUploadsBase), $publicUploadsBase
    $updated = $updated -replace [regex]::Escape($escapedSourceUploadsBase), $escapedPublicUploadsBase
    if ($updated -ne $content) {
        Set-Content -LiteralPath $path -Value $updated -NoNewline
        $rewrittenFiles++
    }
}

[pscustomobject]@{
    Urls = $urls.Count
    Downloaded = $downloaded
    Skipped = $skipped
    Failed = $failed.Count
    RewrittenFiles = $rewrittenFiles
}
if ($failed.Count) {
    $failed | Set-Content -LiteralPath (Join-Path $root 'storage/template-asset-download-failures.txt')
}
