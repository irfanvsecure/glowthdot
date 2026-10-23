<?php

$h = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html');
$p = 0;
$n = 0;
while (($i = stripos($h, '100vh', $p)) !== false && $n < 20) {
    echo substr($h, max(0, $i - 100), 200)."\n---\n";
    $p = $i + 5;
    $n++;
}
echo "count=$n\n";

// Also nichebar markup + any style after ticker
$pos = stripos($h, 'class="nichebar"');
echo substr($h, $pos, 1200)."\n";
