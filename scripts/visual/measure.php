<?php

/**
 * Pixel measurements on a screenshot, to line a rebuilt element up with the original.
 *   box  <png> <hexcolor> [tolerance=40] [fromY=0] [toY=end]  bounding box of pixels near a colour
 *   row  <png> <y> [threshold=60]                              x-ranges of dark pixels on one row
 */
$cmd = $argv[1];
$im = imagecreatefrompng($argv[2]);
$w = imagesx($im);
$h = imagesy($im);
if ($cmd === 'box') {
    $c = hexdec(ltrim($argv[3], '#'));
    $tol = (int) ($argv[4] ?? 40);
    $from = (int) ($argv[5] ?? 0);
    $to = (int) ($argv[6] ?? $h);
    [$x0, $y0, $x1, $y1] = [PHP_INT_MAX, PHP_INT_MAX, -1, -1];
    for ($y = $from; $y < $to; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $p = imagecolorat($im, $x, $y);
            if (abs(($p >> 16 & 255) - ($c >> 16 & 255)) <= $tol && abs(($p >> 8 & 255) - ($c >> 8 & 255)) <= $tol && abs(($p & 255) - ($c & 255)) <= $tol) {
                $x0 = min($x0, $x); $y0 = min($y0, $y); $x1 = max($x1, $x); $y1 = max($y1, $y);
            }
        }
    }
    echo $x1 < 0 ? "not found\n" : "x $x0-$x1 (w ".($x1 - $x0 + 1).")  y $y0-$y1 (h ".($y1 - $y0 + 1).")  right gap ".($w - 1 - $x1)."  bottom gap ".($h - 1 - $y1)."\n";
} else {
    $y = (int) $argv[3];
    $t = (int) ($argv[4] ?? 60);
    $runs = [];
    $start = null;
    for ($x = 0; $x <= $w; $x++) {
        $dark = false;
        if ($x < $w) {
            $p = imagecolorat($im, $x, $y);
            $dark = (($p >> 16 & 255) + ($p >> 8 & 255) + ($p & 255)) / 3 < $t;
        }
        if ($dark && $start === null) $start = $x;
        if (! $dark && $start !== null) { $runs[] = "$start-".($x - 1); $start = null; }
    }
    echo 'dark x-ranges: '.implode(', ', $runs)."\n";
}
