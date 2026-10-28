param([string]$Base, [string]$Url, [int]$Width = 500, [string]$Steps)
# Runs public/__probe2.html against one page and prints what it recorded.
$sp = Join-Path $env:TEMP 'glowth-probe2'
$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
$q = "u=$([uri]::EscapeDataString($Url))&w=$Width&steps=$([uri]::EscapeDataString($Steps))"
$dom = cmd /c "`"$chrome`" --headless=new --disable-gpu --user-data-dir=`"$sp`" --window-size=1600,2600 --virtual-time-budget=20000 --dump-dom `"$Base/__probe2.html?$q`" 2>nul"
$text = ($dom -join "`n")
if ($text -match '(?s)<pre id="out">(.*?)</pre>') { [System.Net.WebUtility]::HtmlDecode($Matches[1]) } else { 'no output' }
