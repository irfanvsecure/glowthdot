<?php

/**
 * Adds "Industries" (/industries/) to the header menu of every page snapshot, after Services.
 * Safe to run again: pages that already have it are skipped.
 */
$base = dirname(__DIR__);
$anchor = '<li id="menu-item-323" class="';

$changed = 0;
foreach (glob($base.'/storage/app/live-html/*.html') as $file) {
    $html = file_get_contents($file);
    if (str_contains($html, 'menu-item-industries') || substr_count($html, $anchor) !== 1) {
        continue;
    }
    $current = basename($file, '.html') === 'industries';
    $item = '<li id="menu-item-industries" class="menu-item menu-item-type-post_type menu-item-object-page'
        .($current ? ' current-menu-item page_item current_page_item' : '').' menu-item-industries">'
        .'<a href="/industries/"'.($current ? ' aria-current="page"' : '').' class="menu-link">Industries</a></li>'."\n";
    file_put_contents($file, str_replace($anchor, $item.$anchor, $html));
    $changed++;
}

echo "Industries menu item added to $changed pages\n";
