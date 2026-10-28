<?php

/**
 * Side-by-side crop of a before and an after screenshot (left: before, right: after), for
 * looking at what scripts/visual/diff.php flagged.
 * Usage: php scripts/visual/pair.php before.png after.png out.png y [height=900] [scale=0.5]
 */
[$_, $a, $b, $out, $y] = $argv;
$h = (int) ($argv[5] ?? 900);
$scale = (float) ($argv[6] ?? 0.5);
$ia = imagecreatefrompng($a);
$ib = imagecreatefrompng($b);
$w = imagesx($ia);
$y = max(0, (int) $y);
$sw = (int) ($w * $scale);
$sh = (int) ($h * $scale);
$canvas = imagecreatetruecolor($sw * 2 + 10, $sh);
imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 0, 0));
imagecopyresampled($canvas, $ia, 0, 0, 0, $y, $sw, $sh, $w, $h);
imagecopyresampled($canvas, $ib, $sw + 10, 0, 0, $y, $sw, $sh, $w, $h);
imagepng($canvas, $out);
echo "saved $out\n";
