<?php

/**
 * Home page "v2" design hooks (styles: public/css/home-v2.css, motion: public/js/home-v2.js).
 *  - section kickers ("- Our Services", "— FAQs") lose the leading dash and become pills (.v2-kicker)
 *  - the heading after each kicker is the section title (.v2-title)
 *  - every top-level section gets a theme: .v2-sec--light / --soft / --dark
 *  - the 5-step process repeated the industries heading; it now has its own
 * Wording is otherwise untouched. Safe to re-run. Usage: php scripts/home-v2.php
 */

$file = dirname(__DIR__).'/storage/app/live-html/home.html';
$h = $orig = file_get_contents($file);

// Top-level sections by Elementor id
$themes = [
    'a4a4457' => 'light',  // Who we serve
    '4b4818b' => 'soft',   // Industries
    'd95b995' => 'light',  // Services
    'e09f55f' => 'dark',   // How we grow: SEO
    '09a2c9f' => 'dark',   // Paid ads
    '6e43672' => 'dark',   // AI booking
    'eeda67b' => 'soft',   // Revenue calculator
    'fcb9e69' => 'light',  // 5-step process
    '64ef0a0' => 'soft',   // Comparison table
    '8fec5e0' => 'light',  // Free audit form
    'ec998d4' => 'soft',   // Guides
    '0e3fbe8' => 'light',  // Why niche
    '7c9952f' => 'dark',   // Where we work
    '5df61ff' => 'soft',   // FAQs
    '8c7c125' => 'dark',   // Closing CTA
];
foreach ($themes as $id => $theme) {
    $h = preg_replace(
        '#(<div class="elementor-element elementor-element-'.$id.' )(?!v2-sec)#',
        '$1v2-sec v2-sec--'.$theme.' ',
        $h,
        1
    );
}

// The 5-step process carried the industries heading
$process = strpos($h, 'elementor-element-fcb9e69 ');
if ($process !== false) {
    $end = strpos($h, 'elementor-element-64ef0a0 ', $process) ?: strlen($h);
    $part = substr($h, $process, $end - $process);
    $part = preg_replace('#>\s*-\s*Industries we serve\s*<#', '>- How it works<', $part, 1);
    $part = preg_replace(
        '#>Beauty industries we specialize in across the UAE\. <span class="hl"></span><#',
        '>From free audit to a <span class="hl">fuller calendar</span>, in 5 steps<',
        $part,
        1
    );
    $h = substr($h, 0, $process).$part.substr($h, $end);
}

// Kickers: a heading whose text starts with "- " or "— " (inside the page body, after the hero)
$body = strpos($h, 'data-elementor-type="wp-page"');
$head = substr($h, 0, $body);
$rest = substr($h, $body);
$rest = preg_replace(
    '#<(span|p|h[1-6]) class="elementor-heading-title elementor-size-default">\s*[-—]\s+#u',
    '<$1 class="elementor-heading-title elementor-size-default v2-kicker">',
    $rest
);
// The comparison table's kicker has no dash
$rest = str_replace(
    'class="elementor-heading-title elementor-size-default">The difference, in one table',
    'class="elementor-heading-title elementor-size-default v2-kicker">The difference, in one table',
    $rest
);
// A label in the middle of "Who we serve", above the cards: no title of its own
$rest = str_replace('v2-kicker">We Serve Entire Beauty Industry', 'v2-sublabel">We Serve Entire Beauty Industry', $rest);

// Title: the first heading after each kicker (marked afresh on every run)
$rest = str_replace(' v2-title"', '"', $rest);
$out = '';
$pos = 0;
while (($k = strpos($rest, 'v2-kicker">', $pos)) !== false) {
    $t = strpos($rest, 'class="elementor-heading-title elementor-size-default">', $k);
    if ($t === false) {
        break;
    }
    $cut = $t + strlen('class="elementor-heading-title elementor-size-default');
    $out .= substr($rest, $pos, $cut - $pos).' v2-title';
    $pos = $cut;
}
$rest = $out.substr($rest, $pos);
$h = $head.$rest;

if ($h === $orig) {
    echo "home v2: already up to date\n";
} else {
    file_put_contents($file, $h);
    echo "home v2: updated\n";
}
