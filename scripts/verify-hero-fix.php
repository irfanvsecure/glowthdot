<?php

$h = file_get_contents(dirname(__DIR__).'/storage/app/live-seo/served-after-fix.html');
if (! $h) {
    $ch = curl_init('http://127.0.0.1:8003/');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $h = curl_exec($ch) ?: '';
    curl_close($ch);
}

echo 'News Bar: '.(str_contains($h, '/* News Bar */') ? 'yes' : 'no').PHP_EOL;
echo 'tickerScroll: '.(str_contains($h, 'tickerScroll') ? 'yes' : 'no').PHP_EOL;
echo 'padding 14px nichebar: '.(str_contains($h, 'padding: 14px 0') ? 'yes' : 'no').PHP_EOL;
echo 'bg 100vh: '.(preg_match('/#bg[^\{]{0,40}\{[^}]{0,200}100vh/', $h) ? 'yes' : 'no').PHP_EOL;
echo 'hero fallback: '.(str_contains($h, 'glowth-hero-fallback') ? 'yes' : 'no').PHP_EOL;
preg_match('/class="separator">(.*?)<\/span>/s', $h, $m);
echo 'sep bytes: ';
for ($i = 0; $i < strlen($m[1] ?? ''); $i++) {
    echo sprintf('%02X ', ord($m[1][$i]));
}
echo ' char='.($m[1] ?? '').PHP_EOL;
echo 'mojibake: '.(str_contains($h, 'âœ¦') || str_contains($h, "\xC3\xA2\xC5\x93\xC2\xA6") ? 'BAD' : 'clean').PHP_EOL;
echo 'audit: '.(preg_match('/Get My SEO Audit .{1,3} Free/', $h) ? 'yes' : 'no').PHP_EOL;

// local file nichebar style present?
$local = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html');
$pos = strpos($local, 'class="nichebar"');
echo substr($local, $pos, 2000);
