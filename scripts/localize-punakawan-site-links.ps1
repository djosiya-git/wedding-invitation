param(
    [Parameter(Mandatory=$true)]
    [string]$SourceBase,
    [string]$PublicBase = 'https://invitation.d-webindigital.web.id/admin/assets/template-assets/punakawan/site/',
    [string]$ProjectBase = 'https://invitation.d-webindigital.web.id/',
    [string]$LocalRoot = 'assets/template-assets/punakawan/site'
)

$ErrorActionPreference = 'Stop'
$SourceBase = $SourceBase.TrimEnd('/') + '/'
$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$templateDir = Join-Path $root 'templates'
$assetRoot = Join-Path $root $LocalRoot
New-Item -ItemType Directory -Force -Path $assetRoot | Out-Null

$normalPattern = [regex]::Escape($SourceBase) + '[^"'' )<>&]+'
$escapedPattern = [regex]::Escape(($SourceBase -replace '/', '\/')) + '[^"'' )<>&]+'
$assetExtensions = '\.(?:css|js|mjs|json|png|jpe?g|jpeg|webp|gif|svg|ico|woff2?|ttf|otf|eot|mp3|wav|mp4|webm)(?:\?|$)'
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
    if ($url -notmatch $assetExtensions) { continue }
    $relative = [Uri]::UnescapeDataString($url.Substring($SourceBase.Length))
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
$escapedSourceBase = $SourceBase -replace '/', '\/'
$escapedPublicBase = $PublicBase -replace '/', '\/'
$sourceTrimmed = $SourceBase.TrimEnd('/')
$projectTrimmed = $ProjectBase.TrimEnd('/')
$escapedSourceTrimmed = $sourceTrimmed -replace '/', '\/'
$escapedProjectTrimmed = $projectTrimmed -replace '/', '\/'

Get-ChildItem -Path $templateDir -Filter '*.html' | ForEach-Object {
    $path = $_.FullName
    $content = Get-Content -Raw -LiteralPath $path
    $updated = $content

    foreach ($url in ($urls | Sort-Object -Descending)) {
        $localUrl = $ProjectBase
        if ($url -match $assetExtensions) {
            $relative = $url.Substring($SourceBase.Length)
            $localUrl = $PublicBase + $relative
        } else {
            $relativePath = ($url.Substring($SourceBase.Length) -replace '^/?', '')
            $localUrl = $ProjectBase + $relativePath
        }
        $updated = $updated.Replace($url, $localUrl)
        $updated = $updated.Replace(($url -replace '/', '\/'), ($localUrl -replace '/', '\/'))
    }

    $updated = $updated.Replace($sourceTrimmed, $projectTrimmed)
    $updated = $updated.Replace($escapedSourceTrimmed, $escapedProjectTrimmed)
    $updated = $updated.Replace('Punakawan Digital', 'd-webindigital.web.id')
    $updated = $updated.Replace('punakawan digital', 'd-webindigital.web.id')

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
    $failed | Set-Content -LiteralPath (Join-Path $root 'storage/punakawan-site-download-failures.txt')
}
