<?php

/**
 * Home page conversion fixes: wording is untouched, only where buttons lead.
 *  - every "free audit" CTA opens the audit funnel at /get-a-free-audit/
 *  - "How It Works" scrolls to the 5-step process
 *  - "Who we serve" cards open the matching industry page
 *  - "View All Guides" opens the blog
 *  - keyword chips "Lip Fillers Hair" / "Transplant UAE" split back into "Lip Fillers" / "Hair Transplant UAE"
 * Safe to re-run. Usage: php scripts/home-cro.php
 */

$file = dirname(__DIR__).'/storage/app/live-html/home.html';
$h = $orig = file_get_contents($file);

// Section anchors
$anchors = ['8fec5e0' => 'free-audit', 'fcb9e69' => 'how-it-works'];
foreach ($anchors as $dataId => $anchor) {
    if (! str_contains($h, 'id="'.$anchor.'"')) {
        $h = preg_replace('#(<div class="[^"]*elementor-element-'.$dataId.'[^"]*" )(data-id="'.$dataId.'")#', '$1id="'.$anchor.'" $2', $h, 1);
    }
}

/** Point the Elementor button(s) labelled $text at $href. $nth limits it to that occurrence (1-based). */
function relink(string $h, string $text, string $href, ?int $nth = null, int $from = 0): string
{
    $i = 0;

    return substr($h, 0, $from).preg_replace_callback(
        '#<a\b([^>]*\belementor-button\b[^>]*)>(.*?)</a>#s',
        function ($m) use ($text, $href, $nth, &$i) {
            $label = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5)));
            if ($label !== $text || ($nth !== null && ++$i !== $nth)) {
                return $m[0];
            }

            return '<a'.preg_replace('#\bhref="[^"]*"#', 'href="'.$href.'"', $m[1], 1).'>'.$m[2].'</a>';
        },
        substr($h, $from)
    );
}

foreach ([
    'Get My SEO Audit – Free', 'Get My Ads Audit - Free', 'Get the Bot on My Clinic',
    'Start With an Audit', 'Unlock This Growth — Free Audit', 'Get My Free Audit',
] as $cta) {
    $h = relink($h, $cta, '/get-a-free-audit/');
}
$h = relink($h, 'How It Works', '#how-it-works');
$h = relink($h, 'View All Guides', '/our-blog/');


// "Who we serve" cards: the five "View More →" buttons right after that section starts
$who = strpos($h, 'data-id="a4a4457"');
foreach (['/beauty-salons/', '/day-spas/', '/aesthetic-clinics/', '/industries/', '/ecommerce-for-beauty/'] as $n => $href) {
    $h = relink($h, 'View More →', $href, $n + 1, $who);
}

$h = preg_replace(
    ['#(elementor-button-text">)\s*Lip Fillers Hair\s*<#', '#(elementor-button-text">)\s*Transplant UAE\s*<#'],
    ['$1Lip Fillers<', '$1Hair Transplant UAE<'],
    $h
);

if ($h !== $orig) {
    file_put_contents($file, $h);
    echo "home.html updated\n";
} else {
    echo "home.html already up to date\n";
}
