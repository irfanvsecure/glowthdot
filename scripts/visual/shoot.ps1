param(
    [string]$Out,                        # folder for the PNGs
    [string]$Base = 'http://127.0.0.1:8765',
    [string[]]$Slugs = @(),              # empty = every page in resources/views/pages
    [int[]]$Widths = @(1440, 500)
)
# Full-page screenshots of each page, motion frozen (?__shot=1), for scripts/visual/diff.php.
$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
New-Item -ItemType Directory -Force $Out | Out-Null
$profile = Join-Path $env:TEMP 'glowth-shot-profile'
if (-not $Slugs.Count) {
    $Slugs = Get-ChildItem (Join-Path $root 'resources/views/pages/*.blade.php') | ForEach-Object { $_.Name -replace '\.blade\.php$', '' }
}
foreach ($slug in $Slugs) {
    # routes/web.php: home is /, category-x-page-2 is /category/x/page/2/
    $path = if ($slug -eq 'home') { '/' } elseif ($slug -match '^category-(.+?)(?:-page-(\d+))?$') { "/category/$($Matches[1])/" + $(if ($Matches[2]) { "page/$($Matches[2])/" } else { '' }) } else { "/$slug/" }
    # Warm-up: the first request writes the page's CSS bundle
    try { Invoke-WebRequest "$Base$path`?__shot=1" -UseBasicParsing -TimeoutSec 60 | Out-Null } catch {}
    foreach ($w in $Widths) {
        $file = Join-Path $Out "$slug-$w.png"
        & $chrome --headless=new --disable-gpu --hide-scrollbars --user-data-dir="$profile" --window-size="$w,15000" `
            --virtual-time-budget=4000 --screenshot="$file" "$Base$path`?__shot=1" 2>$null | Out-Null
    }
    Write-Output $slug
}
