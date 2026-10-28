<?php

/**
 * Lists the bands of rows that changed in a diff image from scripts/visual/diff.php (red pixels),
 * in full-size page coordinates. Usage: php scripts/visual/regions.php diff.png
 */
$im = imagecreatefrompng($argv[1]);
$w = imagesx($im);
$h = imagesy($im);
$bands = [];
$start = $last = null;
$minX = $maxX = null;
for ($y = 0; $y < $h; $y++) {
    $hit = false;
    for ($x = 0; $x < $w; $x++) {
        if (imagecolorat($im, $x, $y) === 0xFF0000) {
            $hit = true;
            $minX = $minX === null ? $x : min($minX, $x);
            $maxX = $maxX === null ? $x : max($maxX, $x);
        }
    }
    if ($hit) {
        $start ??= $y;
        $last = $y;
    } elseif ($start !== null && $y - $last > 10) {
        $bands[] = sprintf('y %d-%d  x %d-%d', $start * 2, $last * 2, $minX * 2, $maxX * 2);
        $start = $minX = $maxX = null;
    }
}
if ($start !== null) {
    $bands[] = sprintf('y %d-%d  x %d-%d', $start * 2, $last * 2, $minX * 2, $maxX * 2);
}
echo implode("\n", $bands) ?: 'no changes', "\n";
