<?php

$dir = dirname(__DIR__).'/storage/app/live-html';
$found = [];
foreach (glob($dir.'/*.html') ?: [] as $f) {
    $h = file_get_contents($f) ?: '';
    if (preg_match_all('/â[\x80-\xBF\xC2-\xF4]{1,8}/u', $h, $m)) {
        foreach ($m[0] as $x) {
            $found[$x] = ($found[$x] ?? 0) + 1;
        }
    }
    // broader: any â followed by non-ascii
    if (preg_match_all('/â[^\x00-\x7F]{1,6}/u', $h, $m)) {
        foreach ($m[0] as $x) {
            $found[$x] = ($found[$x] ?? 0) + 1;
        }
    }
    if (preg_match_all('/ðŸ[^\x00-\x7F]{0,4}/u', $h, $m)) {
        foreach ($m[0] as $x) {
            $found[$x] = ($found[$x] ?? 0) + 1;
        }
    }
}
arsort($found);
foreach (array_slice($found, 0, 50, true) as $k => $c) {
    echo $c."\t";
    for ($i = 0; $i < strlen($k); $i++) {
        echo sprintf('%02X ', ord($k[$i]));
    }
    echo "\t".$k.PHP_EOL;
}
echo 'unique='.count($found).PHP_EOL;
