<?php

/**
 * Shared by the page builders in scripts/: content markers in the snapshots, and the stylesheet,
 * script and FAQPage schema a rebuilt page needs.
 */

const CONTENT_START = '<div id="content" class="site-content">';
const CONTENT_END = '</div><!-- #content -->';

/**
 * Stylesheets go in <head> (so PageSpeed bundles them), the script at the end of <body>.
 * $faq is a list of ['q' => text, 'a' => html]; no FAQ, no schema. Safe to run again on its own output.
 */
function withPageAssets(string $html, array $styles, string $script, array $faq): string
{
    $public = dirname(__DIR__, 2).'/public';

    $html = preg_replace('#<link rel=\'stylesheet\' id=\'glowth-(?:location|service)-css\'[^>]*>\s*#', '', $html) ?? $html;
    $html = preg_replace('#<script id="glowth-(?:location|page)-js"[^>]*></script>\s*#', '', $html) ?? $html;
    $html = preg_replace('#<script type="application/ld\+json" id="glowth-(?:location|page)-faq">.*?</script>\s*#s', '', $html) ?? $html;

    $head = '';
    foreach ($styles as $file) {
        $id = 'glowth-'.basename($file, '.css').'-css';
        $head .= "<link rel='stylesheet' id='".$id."' href='/css/".$file.'?v='.filemtime($public.'/css/'.$file)."' media='all' />\n";
    }
    if ($faq) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($q) => [
                '@type' => 'Question',
                'name' => html_entity_decode(strip_tags($q['q']), ENT_QUOTES | ENT_HTML5),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(html_entity_decode(strip_tags($q['a']), ENT_QUOTES | ENT_HTML5))],
            ], $faq),
        ];
        $head .= '<script type="application/ld+json" id="glowth-page-faq">'.json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."</script>\n";
    }
    $html = preg_replace('#</head>#i', $head.'</head>', $html, 1) ?? $html;

    $js = '<script id="glowth-page-js" src="/js/'.$script.'?v='.filemtime($public.'/js/'.$script).'" defer></script>';

    return preg_replace('#</body>#i', $js."\n</body>", $html, 1) ?? $html;
}
