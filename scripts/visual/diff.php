<?php

/**
 * Compares two folders of page screenshots (scripts/visual/shoot.ps1) and reports, per image,
 * the share of pixels that changed. Images are compared at half size, a pixel counts as changed
 * when any channel differs by more than 24, and a red-marked copy of each changed image is saved.
 * Usage: php scripts/visual/diff.php <before dir> <after dir> [diff dir]
 */
[$_, $before, $after] = $argv + [null, null, null];
$out = $argv[3] ?? $after.'/diff';
@mkdir($out, 0777, true);

$rows = [];
foreach (glob($before.'/*.png') as $a) {
    $name = basename($a);
    $b = $after.'/'.$name;
    if (! is_file($b)) {
        $rows[] = [$name, 'missing'];
        continue;
    }
    $ia = imagecreatefrompng($a);
    $ib = imagecreatefrompng($b);
    // The page's own height: last row that is not plain white, from the bottom
    $w = imagesx($ia);
    $h = min(imagesy($ia), imagesy($ib));
    $sw = intdiv($w, 2);
    $sh = intdiv($h, 2);
    $sa = imagescale($ia, $sw, $sh);
    $sb = imagescale($ib, $sw, $sh);
    $mark = imagecreatetruecolor($sw, $sh);
    imagecopy($mark, $sb, 0, 0, 0, 0, $sw, $sh);
    $red = imagecolorallocate($mark, 255, 0, 0);
    $changed = 0;
    $firstRow = null;
    for ($y = 0; $y < $sh; $y++) {
        for ($x = 0; $x < $sw; $x++) {
            $ca = imagecolorat($sa, $x, $y);
            $cb = imagecolorat($sb, $x, $y);
            if ($ca === $cb) {
                continue;
            }
            if (abs(($ca >> 16 & 255) - ($cb >> 16 & 255)) > 24 || abs(($ca >> 8 & 255) - ($cb >> 8 & 255)) > 24 || abs(($ca & 255) - ($cb & 255)) > 24) {
                $changed++;
                $firstRow ??= $y * 2;
                imagesetpixel($mark, $x, $y, $red);
            }
        }
    }
    $pct = $changed / ($sw * $sh) * 100;
    if ($changed) {
        imagepng($mark, $out.'/'.$name);
    }
    $rows[] = [$name, sprintf('%.3f%%', $pct).($firstRow !== null ? " (first change at y=$firstRow)" : '').(imagesy($ia) !== imagesy($ib) ? ' height differs' : '')];
}
foreach ($rows as [$n, $r]) {
    echo str_pad($n, 60).' '.$r."\n";
}
