<?php

/**
 * Location pages (/dubai/, /abu-dhabi/, /sharjah/): content lives in
 * storage/app/location-content/{slug}.json and is rendered with resources/location-page/template.php
 * into the page snapshot, between the site header and footer.
 *
 * The first run extracts the JSON from the old Elementor HTML widgets, word for word.
 * After that, edit the JSON and run the script again.
 *
 * Usage: php scripts/build-location-pages.php [--extract]   (--extract re-reads the old markup)
 */

$base = dirname(__DIR__);
$pages = ['dubai' => 'Dubai', 'abu-dhabi' => 'Abu Dhabi', 'sharjah' => 'Sharjah'];
$forceExtract = in_array('--extract', $argv, true);

require_once $base.'/scripts/lib/page-assets.php';

foreach ($pages as $slug => $city) {
    $file = $base.'/storage/app/live-html/'.$slug.'.html';
    $json = $base.'/storage/app/location-content/'.$slug.'.json';
    $html = file_get_contents($file) ?: '';

    $start = strpos($html, CONTENT_START);
    $end = strpos($html, CONTENT_END);
    if ($start === false || $end === false) {
        fwrite(STDERR, "$slug: content markers not found\n");
        exit(1);
    }

    if ($forceExtract || ! is_file($json)) {
        if (str_contains($html, 'class="lp"')) {
            fwrite(STDERR, "$slug: already rebuilt, nothing to extract (restore the snapshot from git first)\n");
            exit(1);
        }
        $data = extractContent(substr($html, $start, $end - $start));
        $data['slug'] = $slug;
        $data['city'] = $city;
        @mkdir(dirname($json), 0755, true);
        file_put_contents($json, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
        echo "$slug: extracted ".count($data)." blocks to ".substr($json, strlen($base) + 1)."\n";
    }

    $data = json_decode(file_get_contents($json), true, 512, JSON_THROW_ON_ERROR);

    if (! is_file($base.'/resources/location-page/template.php')) {
        echo "$slug: no template yet, snapshot left as it is\n";

        continue;
    }

    ob_start();
    require $base.'/resources/location-page/template.php';
    $content = ob_get_clean();

    $html = substr($html, 0, $start).CONTENT_START."\n".$content."\n\t".substr($html, $end);
    $html = withPageAssets($html, ['location.css'], 'location.js', $data['faq']['items']);

    file_put_contents($file, $html);
    echo "$slug: rendered (".strlen($content)." bytes)\n";
}

function extractContent(string $markup): array
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="root">'.$markup.'</div>', LIBXML_NOERROR);
    libxml_clear_errors();
    $x = new DOMXPath($doc);

    $q = fn (string $path, ?DOMNode $ctx = null) => $x->query($path, $ctx);
    $one = fn (string $path, ?DOMNode $ctx = null) => $x->query($path, $ctx)->item(0);
    $cls = fn (string $c) => "contains(concat(' ', normalize-space(@class), ' '), ' $c ')";
    $inner = function (?DOMNode $n) use ($doc): string {
        if (! $n) {
            return '';
        }
        $out = '';
        foreach ($n->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim(preg_replace('/\s+/', ' ', $out));
    };
    $text = fn (?DOMNode $n) => $n ? trim(preg_replace('/\s+/', ' ', $n->textContent)) : '';
    // Kicker, heading and intro shared by every section
    $intro = function (DOMNode $sec) use ($one, $inner, $text, $cls) {
        return [
            'kicker' => $text($one(".//*[{$cls('kicker')}]", $sec)),
            'title' => $inner($one('.//h2', $sec)),
            'intro' => $inner($one(".//p[{$cls('sub')}]", $sec)),
        ];
    };

    // The ten widgets, in page order
    $widgets = [];
    foreach ($q("//*[{$cls('elementor-widget-html')}]") as $w) {
        $widgets[] = $w;
    }
    if (count($widgets) !== 10) {
        throw new RuntimeException('Expected 10 content widgets, found '.count($widgets));
    }
    [$hero, $market, $rules, $services, $areas, $maps, $compare, $faq, $links, $final] = $widgets;

    $data = [];

    $data['hero'] = [
        'pill' => $text($one(".//*[{$cls('pill')}]", $hero)),
        'title' => $inner($one('.//h1', $hero)),
        'intro' => $inner($one(".//p[{$cls('sub')}]", $hero)),
        'ctas' => array_map(fn ($a) => ['href' => $a->getAttribute('href'), 'label' => $text($a)], iterator_to_array($q(".//*[{$cls('hero-ctas')}]/a", $hero))),
        'stats' => array_map(fn ($s) => [
            'value' => $text($one('.//b', $s)),
            'label' => $text($one('.//span', $s)),
            'source' => $inner($one('.//small', $s)),
        ], iterator_to_array($q(".//*[{$cls('stat-card')}]", $hero))),
    ];

    $card = $one(".//*[{$cls('niche-card')}]", $market);
    $data['market'] = $intro($market) + [
        'body' => $inner($one(".//*[{$cls('prose')}]", $market)),
        'glance' => [
            'title' => $text($one('.//h3', $card)),
            'items' => array_map(fn ($li) => ['value' => $text($one('.//b', $li)), 'text' => trim(preg_replace('/^\s*<b>.*?<\/b>\s*/s', '', $inner($li)))], iterator_to_array($q('.//li', $card))),
            'source' => $inner($one('.//small', $card)),
        ],
    ];

    $ruleCards = [];
    foreach ($q(".//*[{$cls('svc')} or {$cls('reg')}]", $rules) as $c) {
        $tag = $one(".//*[{$cls('tagx')} or {$cls('tag')} or {$cls('who')}]", $c);
        $h3 = $one('.//h3', $c);
        $body = '';
        foreach ($c->childNodes as $child) {
            if ($child === $tag || $child === $h3) {
                continue;
            }
            $body .= $c->ownerDocument->saveHTML($child);
        }
        $ruleCards[] = ['tag' => $text($tag), 'title' => $inner($h3), 'body' => trim(preg_replace('/\s+/', ' ', $body))];
    }
    $data['rules'] = $intro($rules) + [
        'cards' => $ruleCards,
        'note' => $inner($one(".//p[{$cls('note')} or {$cls('reg-note')}]", $rules)),
    ];

    $data['services'] = $intro($services) + [
        'cards' => array_map(fn ($c) => [
            'href' => $one(".//a[{$cls('svc-link')}]", $c)?->getAttribute('href') ?? '',
            'icon' => ($svg = $one('.//svg', $c)) ? $c->ownerDocument->saveHTML($svg) : '',
            'title' => $inner($one('.//h3', $c)),
            'text' => $inner($one('.//p', $c)),
            'read' => ($rd = $one(".//a[{$cls('rd')}]", $c)) ? ['href' => $rd->getAttribute('href'), 'label' => $text($rd)] : null,
        ], iterator_to_array($q(".//*[{$cls('svc')}]", $services))),
    ];

    // Tabs and panels pair up by position (pages link them by data-p or by an id prefix)
    $tabs = iterator_to_array($q(".//*[{$cls('dtab')}]", $areas));
    $data['areas'] = $intro($areas) + [
        'items' => array_map(function ($p, $i) use ($one, $q, $inner, $text, $cls, $tabs) {
            return [
                'id' => $tabs[$i]->getAttribute('data-d'),
                'tab' => $text($tabs[$i]),
                'title' => $inner($one('.//h3', $p)),
                'text' => $inner($one('.//p', $p)),
                'tags' => array_map($text, iterator_to_array($q(".//*[{$cls('dtags')}]/span", $p))),
                'facts' => array_map(fn ($s) => ['label' => $text($one('.//span', $s)), 'value' => $text($one('.//b', $s))], iterator_to_array($q(".//*[{$cls('dstat')}]", $p))),
            ];
        }, $panels = iterator_to_array($q(".//*[{$cls('dpanel')}]", $areas)), array_keys($panels)),
        'note' => $inner($one(".//*[{$cls('dnote')}]", $areas)),
    ];

    $head = $one(".//*[{$cls('mhead')}]", $maps);
    $data['maps'] = $intro($maps) + [
        // Sharjah's header is plain text, the others wrap it in <span> next to an <em> badge
        'search' => $text($one('.//span', $head) ?? $head),
        'badge' => $text($one('.//em', $head)),
        'rows' => array_map(fn ($r) => [
            'pin' => $text($one(".//*[{$cls('pin')}]", $r)),
            'name' => $text($one('.//b', $r)),
            'detail' => $text($one('.//small', $r)),
            'tag' => $text($one(".//*[{$cls('tag2')}]", $r)),
            'you' => str_contains($r->getAttribute('class'), 'you'),
        ], iterator_to_array($q(".//*[{$cls('mrow')}]", $maps))),
        'note' => $inner($one(".//*[{$cls('mnote')} or {$cls('mfoot')} or {$cls('illus')}]", $maps)),
    ];

    $rows = iterator_to_array($q(".//*[{$cls('cmp-row')}]", $compare));
    $headRow = array_shift($rows);
    $data['compare'] = $intro($compare) + [
        'columns' => array_map($text, iterator_to_array($q('./div', $headRow))),
        'rows' => array_map(fn ($r) => array_map($text, iterator_to_array($q('./div', $r))), $rows),
    ];

    $data['faq'] = $intro($faq) + [
        'items' => array_map(fn ($d) => ['q' => $text($one('.//summary', $d)), 'a' => $inner($one('.//p', $d))], iterator_to_array($q('.//details', $faq))),
    ];

    // A plain <p> starts a group; p.lead is its intro text; div.cov-links holds its links
    $groups = [];
    foreach ($q(".//*[{$cls('cov')}]/*[{$cls('wrap')}]/*", $links) as $el) {
        if ($el->nodeName === 'p' && ! str_contains($el->getAttribute('class'), 'lead')) {
            $groups[] = ['label' => $text($el), 'lead' => '', 'links' => []];
        } elseif ($el->nodeName === 'p' && $groups) {
            $groups[count($groups) - 1]['lead'] = $inner($el);
        } elseif ($groups) {
            foreach ($q('.//a', $el) as $a) {
                $groups[count($groups) - 1]['links'][] = ['href' => $a->getAttribute('href'), 'label' => $text($a)];
            }
        }
    }
    $data['links'] = $groups;

    $data['final'] = $intro($final) + [
        'ctas' => array_map(fn ($a) => ['href' => $a->getAttribute('href'), 'label' => $text($a)], iterator_to_array($q(".//*[{$cls('hero-ctas')}]/a", $final))),
        'trust' => array_map($text, iterator_to_array($q(".//*[{$cls('trust-item')}]", $final))),
    ];

    return $data;
}
