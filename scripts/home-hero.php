<?php

/**
 * Home hero conversion layout: copy on the left, a free-audit card on the right whose website
 * field is the funnel's first step (it opens /get-a-free-audit/?site=... at step 2).
 * The audit CTAs now read "Get My Free Growth Audit", like the header and top bar.
 * Styles live in public/css/home-polish.css (.gh-hero, .gh-audit).
 * Safe to re-run. Usage: php scripts/home-hero.php
 */

$file = dirname(__DIR__).'/storage/app/live-html/home.html';
$h = $orig = file_get_contents($file);

// The hero slide container gets a hook class
$h = preg_replace(
    '#(<div class="elementor-element elementor-element-5bd9929 )(?!gh-hero)#',
    '$1gh-hero ',
    $h,
    1
);

// CTA wording (hero button and the one further down the page)
$h = str_replace(
    '<span class="elementor-button-text">Get My SEO Audit – Free</span>',
    '<span class="elementor-button-text">Get My Free Growth Audit</span>',
    $h
);

$card = <<<'HTML'
<aside class="gh-audit" aria-labelledby="gh-audit-title">
  <span class="gh-audit__badge">Free · worth AED 2,000</span>
  <p class="gh-audit__title" id="gh-audit-title">See why clients book your competitors</p>
  <ul class="gh-audit__list">
    <li>Google rankings &amp; Maps position</li>
    <li>Google Business Profile review</li>
    <li>Website speed &amp; booking flow</li>
    <li>Competitor benchmark + action plan</li>
  </ul>
  <form class="gh-audit__form" action="/get-a-free-audit/" method="get">
    <label class="gh-audit__label" for="gh-site">Your website or Instagram</label>
    <input class="gh-audit__input" id="gh-site" name="site" type="text" placeholder="yourclinic.ae or @instagram" autocomplete="url" autocapitalize="off" spellcheck="false">
    <button class="gh-audit__btn" type="submit">Start My Free Audit →</button>
  </form>
  <p class="gh-audit__note">90 seconds to fill · Results on WhatsApp in 48 hrs · No obligation</p>
  <a class="gh-audit__alt" href="/get-a-free-audit/?site=none">No website yet? Start here</a>
</aside>
HTML;

if (! str_contains($h, 'class="gh-audit"')) {
    // Last child of the hero slide's .e-con-inner, next to the copy column
    $start = strpos($h, 'elementor-element-5bd9929');
    $inner = $start === false ? false : strpos($h, '<div class="e-con-inner">', $start);
    if ($inner === false) {
        fwrite(STDERR, "hero container not found\n");
        exit(1);
    }
    $pos = $inner + strlen('<div class="e-con-inner">');
    $depth = 1;
    $m = [];
    while ($depth > 0 && preg_match('#<(/?)div\b#', $h, $m, PREG_OFFSET_CAPTURE, $pos)) {
        $depth += $m[1][0] === '/' ? -1 : 1;
        $pos = $m[0][1] + strlen($m[0][0]);
    }
    $close = $m[0][1];
    $h = substr($h, 0, $close).$card."\n\t\t\t\t\t".substr($h, $close);
}

if ($h === $orig) {
    echo "home hero: already up to date\n";
} else {
    file_put_contents($file, $h);
    echo "home hero: updated\n";
}
