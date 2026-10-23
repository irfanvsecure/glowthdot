<?php

$ch = curl_init('http://127.0.0.1:8003/');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
$h = curl_exec($ch) ?: '';
curl_close($ch);

echo 'mojibake âœ/â˜: '.(preg_match('/âœ|â˜/u', $h) ? 'BAD' : 'clean').PHP_EOL;
echo '★='.substr_count($h, '★').' ✓='.substr_count($h, '✓').' ✗='.substr_count($h, '✗').PHP_EOL;

$badFiles = 0;
foreach (glob(dirname(__DIR__).'/storage/app/live-html/*.html') ?: [] as $f) {
    $x = file_get_contents($f) ?: '';
    if (preg_match('/âœ|â˜|â€¦|â—/u', $x)) {
        echo 'BAD '.basename($f).PHP_EOL;
        $badFiles++;
    }
}
echo "bad_files=$badFiles\n";
