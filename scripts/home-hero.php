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

// The card sits on a 3D stage with three floating, decorative notifications (public/js/home-v2.js)
$card = <<<'HTML'
<div class="gh-stage" data-v2-stage>
<div class="gh-float gh-float--a" aria-hidden="true" data-depth="1.6"><span class="gh-float__ic">📅</span><span><b>New booking</b><small>Hydrafacial · Sat 11:00 AM</small></span></div>
<div class="gh-float gh-float--b" aria-hidden="true" data-depth="2.2"><span class="gh-float__ic">⭐</span><span><b>New 5-star review</b><small>Google Maps</small></span></div>
<div class="gh-float gh-float--c" aria-hidden="true" data-depth="1.2"><span class="gh-float__ic gh-float__ic--wa">💬</span><span><b>WhatsApp answered</b><small>by your AI receptionist</small></span></div>
<aside class="gh-audit" aria-labelledby="gh-audit-title" data-v2-tilt>
  <div class="gh-audit__top">
    <span class="gh-audit__badge">Free · worth AED 2,000</span>
    <span class="gh-audit__step">Step 1 of 7</span>
  </div>
  <div class="gh-audit__bar" aria-hidden="true"><i></i></div>
  <p class="gh-audit__title" id="gh-audit-title">Get your free growth audit</p>
  <p class="gh-audit__sub">Find out why clients book your competitors.</p>
  <ul class="gh-audit__list">
    <li>Google rankings &amp; Maps</li>
    <li>Business Profile review</li>
    <li>Website speed check</li>
    <li>Competitor benchmark</li>
  </ul>
  <form class="gh-audit__form" action="/get-a-free-audit/" method="get">
    <label class="gh-audit__label" for="gh-site">Your website or Instagram</label>
    <div class="gh-audit__field">
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/></svg>
      <input class="gh-audit__input" id="gh-site" name="site" type="text" placeholder="yourclinic.ae or @instagram" autocomplete="url" autocapitalize="off" spellcheck="false">
    </div>
    <button class="gh-audit__btn" type="submit">Start My Free Audit<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
  </form>
  <p class="gh-audit__note"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>90 seconds · Results on WhatsApp in 48 hrs · No obligation</p>
  <a class="gh-audit__alt" href="/get-a-free-audit/?site=none">No website yet? Start here →</a>
</aside>
</div>
HTML;

// An older version of the card (with or without its stage) is swapped for this one
$h = preg_replace('#<div class="gh-stage".*?</aside>\s*</div>|<aside class="gh-audit".*?</aside>#s', $card, $h, 1);

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
