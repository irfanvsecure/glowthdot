<?php

/**
 * Site-wide fix for UTF-8 mojibake (icons showing as âœ” / â˜… etc.)
 */

$base = dirname(__DIR__);
$targets = array_merge(
    glob($base.'/storage/app/live-html/*.html') ?: [],
    glob($base.'/public/images/elementor/css/*.css') ?: []
);

// Exact byte sequences observed on this site + common variants
$byteMap = [
    // ★ BLACK STAR (E2 98 85) misread via CP1252
    "\xC3\xA2\xCB\x9C\xE2\x80\xA6" => "\xE2\x98\x85",
    // ☆ WHITE STAR
    "\xC3\xA2\xCB\x9C\xE2\x80\xA0" => "\xE2\x98\x86",

    // ✓ CHECK MARK (E2 9C 93)
    "\xC3\xA2\xC5\x93\xE2\x80\x9C" => "\xE2\x9C\x93",
    // ✔ HEAVY CHECK (E2 9C 94)
    "\xC3\xA2\xC5\x93\xE2\x80\x9D" => "\xE2\x9C\x94",

    // ✗ BALLOT X (E2 9C 97)
    "\xC3\xA2\xC5\x93\xE2\x80\x94" => "\xE2\x9C\x97",
    // ✖ HEAVY MULTIPLICATION X (E2 9C 98) alt corruptions
    "\xC3\xA2\xC5\x93\xCB\x98" => "\xE2\x9C\x98",

    // ✦ BLACK FOUR POINTED STAR
    "\xC3\xA2\xC5\x93\xC2\xA6" => "\xE2\x9C\xA6",
    "\xC3\xA2\xC5\x93\xC2\xA7" => "\xE2\x9C\xA7",

    // ● BLACK CIRCLE (E2 97 8F) seen as â—
    "\xC3\xA2\xE2\x80\x94\xC2\x8F" => "\xE2\x97\x8F",
    "\xC3\xA2\xE2\x80\x94\x8F" => "\xE2\x97\x8F",

    // … ELLIPSIS
    "\xC3\xA2\xE2\x82\xAC\xC2\xA6" => "\xE2\x80\xA6",
    "\xC3\xA2\xE2\x82\xAC\xA6" => "\xE2\x80\xA6",

    // – en dash / — em dash
    "\xC3\xA2\xE2\x82\xAC\xE2\x80\x9C" => "\xE2\x80\x93",
    "\xC3\xA2\xC2\x80\xC2\x93" => "\xE2\x80\x93",
    "\xC3\xA2\xC2\x80\xC2\x94" => "\xE2\x80\x94",

    // → arrow
    "\xC3\xA2\xC2\x86\xE2\x80\x99" => "\xE2\x86\x92",
    "\xC3\xA2\x86\x92" => "\xE2\x86\x92",

    // • bullet
    "\xC3\xA2\xE2\x82\xAC\xC2\xA2" => "\xE2\x80\xA2",

    // Double-encoded classic (UTF-8 as Latin1 then UTF-8 again)
    "\xC3\xA2\xC2\x98\xC2\x85" => "\xE2\x98\x85",
    "\xC3\xA2\xC2\x9C\xC2\x93" => "\xE2\x9C\x93",
    "\xC3\xA2\xC2\x9C\xC2\x94" => "\xE2\x9C\x94",
    "\xC3\xA2\xC2\x9C\xC2\x97" => "\xE2\x9C\x97",
    "\xC3\xA2\xC2\x9C\xC2\x98" => "\xE2\x9C\x98",
    "\xC3\xA2\xC2\x9C\xC2\xA6" => "\xE2\x9C\xA6",
];

// Unicode string forms (if already decoded to chars in editor)
$charMap = [
    'â˜…' => '★',
    'â˜†' => '☆',
    'âœ“' => '✓',
    'âœ”' => '✔',
    'âœ—' => '✗',
    'âœ˜' => '✗',
    'âœ¦' => '✦',
    'âœ§' => '✧',
    'â€¦' => '…',
    'â€“' => '–',
    'â€”' => '—',
    'â€¢' => '•',
    'â†’' => '→',
    'â—' => '●',
    'Â·' => '·',
    'Â©' => '©',
    'Â®' => '®',
    'Â ' => ' ',
    'ðŸŽ' => '🎁',
];

$changedFiles = 0;
$total = 0;

foreach ($targets as $file) {
    $html = file_get_contents($file);
    if ($html === false) {
        continue;
    }
    $orig = $html;
    $n = 0;

    foreach ($byteMap as $bad => $good) {
        $c = 0;
        $html = str_replace($bad, $good, $html, $c);
        $n += $c;
    }
    foreach ($charMap as $bad => $good) {
        $c = 0;
        $html = str_replace($bad, $good, $html, $c);
        $n += $c;
    }

    // Runs of star mojibake (and truncated tails)
    $html = preg_replace_callback('/(?:\xC3\xA2\xCB\x9C\xE2\x80\xA6)+(?:\xC3\xA2)?/', function ($m) {
        $unit = "\xC3\xA2\xCB\x9C\xE2\x80\xA6";
        $len = (int) floor(strlen($m[0]) / strlen($unit));

        return str_repeat("\xE2\x98\x85", max(1, $len));
    }, $html) ?? $html;

    if ($html !== $orig) {
        file_put_contents($file, $html);
        $changedFiles++;
        $total += $n;
        echo basename($file).": $n\n";
    }
}

echo "\nChanged files: $changedFiles, replacements: $total\n";

// Re-scan
$found = [];
foreach (glob($base.'/storage/app/live-html/*.html') ?: [] as $f) {
    $h = file_get_contents($f) ?: '';
    if (preg_match_all('/â[^\x00-\x7F]{1,8}/u', $h, $m)) {
        foreach ($m[0] as $x) {
            $found[$x] = ($found[$x] ?? 0) + 1;
        }
    }
}
arsort($found);
echo 'Remaining mojibake types: '.count($found)."\n";
foreach (array_slice($found, 0, 20, true) as $k => $c) {
    echo "$c\t$k\t";
    for ($i = 0; $i < strlen($k); $i++) {
        echo sprintf('%02X ', ord($k[$i]));
    }
    echo PHP_EOL;
}

// Verify icons present on home
$home = file_get_contents($base.'/storage/app/live-html/home.html');
foreach (['★' => 'star', '✓' => 'check', '✗' => 'cross', '✔' => 'heavy-check', '✖' => 'heavy-x'] as $ch => $label) {
    echo "$label count=".substr_count($home, $ch)."\n";
}
