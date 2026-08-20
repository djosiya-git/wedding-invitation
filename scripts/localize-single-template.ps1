param(
    [Parameter(Mandatory=$true)]
    [string]$Template,
    [string]$SourceBase = 'https://inv.punakawandigital.id/',
    [string]$ProjectBase = 'https://invitation.d-webindigital.web.id/'
)

$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$templatePath = Join-Path (Join-Path $root 'templates') $Template
if (-not (Test-Path -LiteralPath $templatePath)) {
    throw "Template not found: $Template"
}

$SourceBase = $SourceBase.TrimEnd('/') + '/'
$ProjectBase = $ProjectBase.TrimEnd('/') + '/'
$uploadRoot = Join-Path $root 'assets/template-assets/punakawan/uploads'
$siteRoot = Join-Path $root 'assets/template-assets/punakawan/site'
New-Item -ItemType Directory -Force -Path $uploadRoot | Out-Null
New-Item -ItemType Directory -Force -Path $siteRoot | Out-Null

function Get-LocalTarget([string]$url) {
    $uri = [Uri]$url
    $relative = [Uri]::UnescapeDataString($uri.AbsolutePath.TrimStart('/'))
    if ($relative -like 'wp-content/uploads/*') {
        return Join-Path $uploadRoot (($relative -replace '^wp-content/uploads/', '') -replace '/', [IO.Path]::DirectorySeparatorChar)
    }
    return Join-Path $siteRoot ($relative -replace '/', [IO.Path]::DirectorySeparatorChar)
}

function Save-Url([string]$url) {
    $target = Get-LocalTarget $url
    $targetDir = Split-Path -Parent $target
    New-Item -ItemType Directory -Force -Path $targetDir | Out-Null
    if (Test-Path -LiteralPath $target) {
        return 'skipped'
    }
    try {
        Invoke-WebRequest -Uri $url -OutFile $target -UseBasicParsing
        return 'downloaded'
    } catch {
        if (Test-Path -LiteralPath $target) {
            Remove-Item -LiteralPath $target -Force
        }
        return "failed`t$url"
    }
}

$content = [IO.File]::ReadAllText($templatePath)
$sourcePattern = [regex]::Escape($SourceBase) + '[^"'' )<>&]+'
$escapedPattern = [regex]::Escape(($SourceBase -replace '/', '\/')) + '[^"'' )<>&]+'
$assetPattern = '\.(?:css|js|mjs|json|png|jpe?g|jpeg|webp|gif|svg|ico|woff2?|ttf|otf|eot|mp3|wav|mp4|webm)(?:\?|$)'
$urls = [System.Collections.Generic.HashSet[string]]::new()

[regex]::Matches($content, $sourcePattern) | ForEach-Object {
    if ($_.Value -match $assetPattern) { [void]$urls.Add($_.Value) }
}
[regex]::Matches($content, $escapedPattern) | ForEach-Object {
    $url = $_.Value -replace '\\/', '/'
    if ($url -match $assetPattern) { [void]$urls.Add($url) }
}

$downloaded = 0
$skipped = 0
$failed = @()
foreach ($url in ($urls | Sort-Object)) {
    $result = Save-Url $url
    if ($result -eq 'downloaded') { $downloaded++ }
    elseif ($result -eq 'skipped') { $skipped++ }
    elseif ($result -like "failed`t*") { $failed += ($result -replace '^failed`t', '') }
}

$updated = $content
$sourceTrimmed = $SourceBase.TrimEnd('/')
$projectTrimmed = $ProjectBase.TrimEnd('/')
$updated = $updated.Replace($sourceTrimmed, $projectTrimmed)
$updated = $updated.Replace(($sourceTrimmed -replace '/', '\/'), ($projectTrimmed -replace '/', '\/'))
$updated = $updated.Replace('Punakawan Digital', 'd-webindigital.web.id')
$updated = $updated.Replace('punakawan digital', 'd-webindigital.web.id')

$bisdevMap = @{
    'https://cdn.jsdelivr.net/gh/Bisdev-gift/elementor-script@main/' = $ProjectBase + 'wp-content/bisdev-gift/elementor-script/'
    'https:\/\/cdn.jsdelivr.net\/gh\/Bisdev-gift\/elementor-script@main\/' = ($ProjectBase + 'wp-content/bisdev-gift/elementor-script/') -replace '/', '\/'
    'https://cdn.jsdelivr.net/gh/Bisdev-gift/bisdev-cover-reveal@main/' = $ProjectBase + 'wp-content/bisdev-gift/bisdev-cover-reveal/'
    'https:\/\/cdn.jsdelivr.net\/gh\/Bisdev-gift\/bisdev-cover-reveal@main\/' = ($ProjectBase + 'wp-content/bisdev-gift/bisdev-cover-reveal/') -replace '/', '\/'
}
foreach ($key in $bisdevMap.Keys) {
    $updated = $updated.Replace($key, $bisdevMap[$key])
}

[IO.File]::WriteAllText($templatePath, $updated, [Text.UTF8Encoding]::new($false))

[pscustomobject]@{
    Template = $Template
    Urls = $urls.Count
    Downloaded = $downloaded
    Skipped = $skipped
    Failed = $failed.Count
}

if ($failed.Count) {
    $failurePath = Join-Path $root 'storage/single-template-download-failures.txt'
    $failed | Sort-Object -Unique | Set-Content -LiteralPath $failurePath
}
