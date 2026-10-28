param(
    [string]$Work,                               # scratch folder
    [string]$Before = 'http://127.0.0.1:8766',   # the old site (a git worktree of the last commit)
    [string]$After = 'http://127.0.0.1:8765',
    [string[]]$Slugs = @()
)
# Screenshots every page on both servers and diffs them (scripts/visual/diff.php).
$here = $PSScriptRoot
$root = Split-Path -Parent (Split-Path -Parent $here)
Remove-Item "$Work\before", "$Work\after" -Recurse -Force -ErrorAction SilentlyContinue
& "$here\shoot.ps1" -Out "$Work\before" -Base $Before -Slugs $Slugs | Out-Null
& "$here\shoot.ps1" -Out "$Work\after" -Base $After -Slugs $Slugs | Out-Null
php "$root\scripts\visual\diff.php" "$Work\before" "$Work\after" | Out-File -Encoding utf8 "$Work\report.txt"
Get-Content "$Work\report.txt"
