<?php

/**
 * make-cards-fully-clickable.php closed each a.glowth-card-link one </div> too early, inside
 * div.elementor-icon-box-content. Browsers repair that by splitting the link in two: one holding
 * only the aria-hidden icon (a link with no text, flagged by Lighthouse) and one holding the text.
 * Moves </a> after the content div so each card is one link again.
 *
 * The split also left the text as a second child of the flex wrapper, so the widget's 15px gap
 * sat between icon and text. The text is inside the link now; a margin keeps the same spacing.
 * Usage: php scripts/fix-card-link-nesting.php
 */

$htmlDir = dirname(__DIR__).'/storage/app/live-html';
$spacing = 'a.glowth-card-link>.elementor-icon-box-content{margin-top:15px;}';

foreach (['industries', 'home'] as $slug) {
    $file = $htmlDir.'/'.$slug.'.html';
    $html = file_get_contents($file) ?: '';
    $fixed = 0;

    $html = preg_replace_callback(
        '#(<a class="glowth-card-link"[^>]*>)(.*?)</a></div>#is',
        function ($m) use (&$fixed) {
            // Only cards that open one more <div> than they close
            if (preg_match_all('#<div\b#i', $m[2]) !== preg_match_all('#</div>#i', $m[2]) + 1) {
                return $m[0];
            }
            $fixed++;

            return $m[1].$m[2].'</div></a>';
        },
        $html
    ) ?? $html;

    if (! str_contains($html, $spacing)) {
        $html = str_replace('<style>/* glowth-card-link */', '<style>/* glowth-card-link */'.$spacing, $html);
    }

    file_put_contents($file, $html);
    echo "$slug cards fixed: $fixed\n";
}
