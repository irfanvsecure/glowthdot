<?php

$htmlDir = dirname(__DIR__).'/storage/app/live-html';

foreach (['industries', 'home'] as $slug) {
    $file = $htmlDir.'/'.$slug.'.html';
    $html = file_get_contents($file) ?: '';

    // Inside glowth-card-link, convert nested <a class="elementor-icon"...> to <span>
    $html = preg_replace_callback(
        '/<a class="glowth-card-link"[^>]*>.*?<\/a>/is',
        function ($m) {
            $block = $m[0];
            // elementor-icon anchors → span
            $block = preg_replace(
                '/<a\b([^>]*class="[^"]*elementor-icon[^"]*"[^>]*)>/i',
                '<span$1>',
                $block
            ) ?? $block;
            // careful: only close those we opened as span — replace first </a> after elementor-icon span
            // Simpler: replace all remaining <a href=...> inside with span, and matching </a> before final
            $block = preg_replace_callback(
                '/<a class="glowth-card-link"([^>]*)>(.*)<\/a>$/is',
                function ($mm) {
                    $inner = $mm[2];
                    // strip any remaining <a ...> ... </a> to span.../span (non-greedy pairs)
                    $inner = preg_replace('/<a\b([^>]*)>(.*?)<\/a>/is', '<span$1>$2</span>', $inner) ?? $inner;

                    return '<a class="glowth-card-link"'.$mm[1].'>'.$inner.'</a>';
                },
                $block
            ) ?? $block;

            return $block;
        },
        $html
    ) ?? $html;

    file_put_contents($file, $html);

    // count nested a inside glowth-card-link
    $nested = 0;
    if (preg_match_all('/<a class="glowth-card-link"[^>]*>(.*?)<\/a>/is', $html, $m)) {
        foreach ($m[1] as $inner) {
            if (preg_match('/<a\b/i', $inner)) {
                $nested++;
            }
        }
    }
    echo "$slug nested anchors remaining: $nested\n";
}
