param(
    [string]$SourceBase = 'https://invitation.d-webindigital.web.id/',
    [Parameter(Mandatory=$true)]
    [string]$OriginalBase,
    [string]$LocalRoot = 'assets/template-assets/punakawan/site'
)

$ErrorActionPreference = 'Stop'
$OriginalBase = $OriginalBase.TrimEnd('/') + '/'
$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$assetRoot = Resolve-Path (Join-Path $root $LocalRoot)
$downloaded = 0
$skipped = 0
$failed = @()

function Resolve-CssPath([string]$cssPath, [string]$ref) {
    $clean = ($ref -replace '[?#].*$', '').Trim()
    if ($clean -eq '' -or $clean -match '^(?:data:|https?:|//)') { return $null }
    return [IO.Path]::GetFullPath((Join-Path (Split-Path -Parent $cssPath) $clean))
}

function To-OriginalUrl([string]$localPath) {
    $basePath = $assetRoot.Path.TrimEnd('\', '/') + [IO.Path]::DirectorySeparatorChar
    if (-not $localPath.StartsWith($basePath, [StringComparison]::OrdinalIgnoreCase)) { return $null }
    $relative = $localPath.Substring($basePath.Length) -replace '\\', '/'
    return $OriginalBase.TrimEnd('/') + '/' + $relative
}

do {
    $madeProgress = $false
    $cssFiles = Get-ChildItem -Path $assetRoot -Recurse -File -Filter '*.css'
    foreach ($css in $cssFiles) {
        $content = Get-Content -Raw -LiteralPath $css.FullName
        $matches = [regex]::Matches($content, 'url\((?!["'']?data:)["'']?([^\)"'']+)["'']?\)')
        foreach ($match in $matches) {
            $target = Resolve-CssPath $css.FullName $match.Groups[1].Value
            if ($null -eq $target) { continue }
            if (-not $target.StartsWith($assetRoot.Path, [StringComparison]::OrdinalIgnoreCase)) { continue }
            if (Test-Path -LiteralPath $target) {
                $skipped++
                continue
            }
            New-Item -ItemType Directory -Force -Path (Split-Path -Parent $target) | Out-Null
            $url = To-OriginalUrl $target
            if ($null -eq $url) { continue }
            try {
                Invoke-WebRequest -Uri $url -OutFile $target -UseBasicParsing
                $downloaded++
                $madeProgress = $true
            } catch {
                if (Test-Path -LiteralPath $target) {
                    Remove-Item -LiteralPath $target -Force
                }
                $failed += $url
            }
        }
    }
} while ($madeProgress)

[pscustomobject]@{
    Downloaded = $downloaded
    Skipped = $skipped
    Failed = ($failed | Sort-Object -Unique).Count
}

if ($failed.Count) {
    $failed | Sort-Object -Unique | Set-Content -LiteralPath (Join-Path $root 'storage/css-dependency-download-failures.txt')
}
