<?php

$live = file_get_contents(dirname(__DIR__).'/storage/app/live-seo/live-home-now.html') ?: '';
$local = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html') ?: '';

echo "=== LIVE script src ===\n";
if (preg_match_all('/<script[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $live, $m)) {
    foreach ($m[1] as $s) {
        if (str_contains($s, 'elementor') || str_contains($s, 'swiper') || str_contains($s, 'jquery')) {
            echo "$s\n";
        }
    }
}

echo "\n=== LOCAL script src ===\n";
if (preg_match_all('/<script[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $local, $m)) {
    foreach ($m[1] as $s) {
        if (str_contains($s, 'elementor') || str_contains($s, 'swiper') || str_contains($s, 'jquery')) {
            echo "$s\n";
        }
    }
}

// Fetch a couple live URLs and report sizes
$urls = [];
if (preg_match_all('/src=[\'"](https:\/\/glowthdot\.com\/wp-content\/plugins\/elementor[^\'"]+)[\'"]/i', $live, $m)) {
    $urls = array_unique($m[1]);
}
echo "\n=== Fetch live elementor scripts ===\n";
foreach ($urls as $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo $code.' '.strlen((string) $body).' '.$url.PHP_EOL;
    echo '  head: '.substr(preg_replace('/\s+/', ' ', (string) $body), 0, 120).PHP_EOL;
}
