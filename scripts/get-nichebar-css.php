<?php

$live = file_get_contents(dirname(__DIR__).'/storage/app/live-seo/live-home-enc.html');
if (! $live || strlen($live) < 1000) {
    $ch = curl_init('https://glowthdot.com/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $live = curl_exec($ch) ?: '';
    curl_close($ch);
    file_put_contents(dirname(__DIR__).'/storage/app/live-seo/live-home-enc.html', $live);
}

foreach (['nichebar', 'ticker', 'separator'] as $cls) {
    if (preg_match_all('/\.'.$cls.'[^{]*\{[^}]+\}/s', $live, $m)) {
        echo "=== .$cls ===\n";
        foreach ($m[0] as $r) {
            echo $r."\n";
        }
    }
}

// style block containing nichebar
if (preg_match('/<style[^>]*>[^<]*nichebar[^<]*<\/style>/is', $live, $m)) {
    echo "=== style block ===\n".$m[0]."\n";
}

// html widget full
if (preg_match('/class="nichebar".{0,50}ticker.{0,2500}/s', $live, $m)) {
    echo "=== live nichebar snip ===\n".$m[0]."\n";
}

$local = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html');
if (preg_match('/<style[^>]*>[^<]*nichebar[^<]*<\/style>/is', $local, $m)) {
    echo "=== local style ===\n".$m[0]."\n";
} else {
    echo "local has NO nichebar style block\n";
}
