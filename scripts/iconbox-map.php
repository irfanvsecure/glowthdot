<?php

function extractIconBoxes(string $html): array
{
    $boxes = [];
    // Each icon-box widget
    if (! preg_match_all('#data-widget_type="icon-box\.default".*?</div>\s*</div>\s*</div>#is', $html, $m)) {
        // broader: elementor-widget-icon-box blocks
        preg_match_all('#class="[^"]*elementor-widget-icon-box[^"]*"[^>]*>(.*?)</div>\s*</div>\s*</div>\s*</div>#is', $html, $m);
    }
    // title + description approach
    if (preg_match_all('#elementor-icon-box-title[^>]*>\s*(?:<a[^>]*>)?\s*(?:<span[^>]*>)?\s*([^<]+)\s*(?:</span>)?\s*(?:</a>)?\s*</p>.*?:elementor-icon-box-description[^>]*>\s*([^<]*)#is', $html, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $row) {
            $boxes[] = [
                'title' => trim(html_entity_decode($row[1])),
                'desc' => trim(html_entity_decode($row[2])),
            ];
        }
    }

    // simpler title-only
    if (! $boxes && preg_match_all('#class="elementor-icon-box-title"[^>]*>\s*(?:<[^>]+>\s*)*([^<]+)#i', $html, $mm)) {
        foreach ($mm[1] as $t) {
            $boxes[] = ['title' => trim(html_entity_decode($t)), 'desc' => ''];
        }
    }

    return $boxes;
}

function parentHasLink(string $html, string $title): ?string
{
    $pos = stripos($html, $title);
    if ($pos === false) {
        return null;
    }
    // look back 1500 chars for wrapping <a or data-settings link
    $chunk = substr($html, max(0, $pos - 2500), 2500);
    if (preg_match_all('#<a[^>]+href=(["\'])([^"\']+)\1[^>]*>#i', $chunk, $m)) {
        $href = $m[2][count($m[2]) - 1];

        return $href;
    }
    // Elementor container link in data-settings
    if (preg_match('#data-settings="([^"]*url[^"]*)"#i', $chunk, $m)) {
        $raw = html_entity_decode($m[1]);
        if (preg_match('#"url"\s*:\s*"([^"]+)"#', $raw, $u)) {
            return stripslashes($u[1]);
        }
    }

    return null;
}

$local = file_get_contents(dirname(__DIR__).'/storage/app/live-html/industries.html') ?: '';
$live = file_get_contents(dirname(__DIR__).'/storage/app/live-seo/industries-live.html') ?: '';
$home = file_get_contents(dirname(__DIR__).'/storage/app/live-html/home.html') ?: '';

echo "=== INDUSTRIES LOCAL ICON BOXES ===\n";
foreach (extractIconBoxes($local) as $b) {
    $link = parentHasLink($local, $b['title']);
    echo ($link ? "[$link]" : '[NO LINK]').' '.$b['title']."\n";
}

echo "\n=== INDUSTRIES LIVE — check container links near Hair Salons ===\n";
$pos = stripos($live, 'Hair Salons');
echo substr($live, max(0, $pos - 800), 1200)."\n";

echo "\n=== HOME icon box titles (industry section) ===\n";
$boxes = extractIconBoxes($home);
foreach ($boxes as $i => $b) {
    if ($i > 45) {
        break;
    }
    $link = parentHasLink($home, $b['title']);
    echo ($link ? "[$link]" : '[NO LINK]').' '.$b['title']."\n";
}
