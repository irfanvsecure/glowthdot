<?php

/**
 * Service pages (/seo-for-beauty-salons-clinics/ and the other seven in the Services menu): content lives in
 * storage/app/service-content/{slug}.json and is rendered with resources/service-page/template.php into the
 * page snapshot, between the site header and footer.
 *
 * The first run converts the old Elementor sections, word for word, into blocks: hero, cards, stats,
 * feature rows, reviews, FAQ and form. Parts the converter does not recognise (mock-ups, calculators,
 * the lead form) are kept as their original Elementor markup ("raw"), so they look and work as before.
 * After that, edit the JSON and run the script again.
 *
 * Usage: php scripts/build-service-pages.php [--extract] [slug ...]
 */

$base = dirname(__DIR__);
$all = [
    'seo-for-beauty-salons-clinics', 'websites-landing-pages-for-beauty', 'social-media-management-for-beauty',
    'google-ads-for-beauty', 'instagram-facebook-ads-for-beauty', 'ai-appointment-booking-bot',
    'ecommerce-for-beauty', 'reviews-reputation',
];
$args = array_slice($argv, 1);
$forceExtract = in_array('--extract', $args, true);
$pages = array_values(array_intersect($all, $args)) ?: $all;

require_once $base.'/scripts/lib/page-assets.php';

foreach ($pages as $slug) {
    $file = $base.'/storage/app/live-html/'.$slug.'.html';
    $json = $base.'/storage/app/service-content/'.$slug.'.json';
    $html = file_get_contents($file) ?: '';

    $start = strpos($html, CONTENT_START);
    $end = strpos($html, CONTENT_END);
    if ($start === false || $end === false) {
        fwrite(STDERR, "$slug: content markers not found\n");
        exit(1);
    }

    if ($forceExtract || ! is_file($json)) {
        if (str_contains($html, 'class="lp lp--service"')) {
            fwrite(STDERR, "$slug: already rebuilt, nothing to extract (restore the snapshot from git first)\n");
            exit(1);
        }
        $data = (new ServiceExtractor(substr($html, $start, $end - $start)))->extract();
        $data['slug'] = $slug;
        @mkdir(dirname($json), 0755, true);
        file_put_contents($json, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
        $layouts = array_count_values(array_column($data['sections'], 'layout'));
        echo "$slug: extracted hero + ".count($data['sections']).' sections '.json_encode($layouts)."\n";
    }

    $data = json_decode(file_get_contents($json), true, 512, JSON_THROW_ON_ERROR);

    if (! is_file($base.'/resources/service-page/template.php')) {
        echo "$slug: no template yet, snapshot left as it is
";

        continue;
    }

    ob_start();
    require $base.'/resources/service-page/template.php';
    $content = ob_get_clean();

    $html = substr($html, 0, $start).CONTENT_START."\n".$content."\n\t".substr($html, $end);
    $faq = [];
    foreach ($data['sections'] as $section) {
        if ($section['layout'] === 'faq') {
            $faq = array_merge($faq, $section['items']);
        }
    }
    $html = withPageAssets($html, ['location.css', 'service.css'], 'location.js', $faq);

    file_put_contents($file, $html);
    echo "$slug: rendered (".strlen($content)." bytes)\n";
}

class ServiceExtractor
{
    private DOMDocument $doc;

    private DOMXPath $x;

    private string $docId;

    public function __construct(string $markup)
    {
        $this->doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $this->doc->loadHTML('<?xml encoding="UTF-8"><div id="lp-root">'.$markup.'</div>', LIBXML_NOERROR);
        libxml_clear_errors();
        $this->x = new DOMXPath($this->doc);
        $root = $this->x->query('//*[@data-elementor-id][@data-elementor-type="wp-page"]')->item(0);
        $this->docId = $root ? $root->getAttribute('data-elementor-id') : '0';
    }

    public function extract(): array
    {
        $sections = iterator_to_array($this->x->query("//*[{$this->cls('e-parent')}]"));
        $hero = array_shift($sections);

        return [
            'docId' => $this->docId,
            'hero' => $this->hero($hero),
            'sections' => array_map(fn ($s) => $this->section($s), $sections),
        ];
    }

    // ---------- Sections ----------

    private function hero(DOMElement $sec): array
    {
        $h1 = $this->x->query(".//*[@data-widget_type='heading.default'][.//h1]", $sec)->item(0);
        $copy = $h1 ? $h1->parentNode : $sec;
        $out = ['anchors' => $this->anchors($sec), 'pill' => '', 'title' => '', 'intro' => '', 'ctas' => [], 'chips' => [], 'extra' => [], 'visual' => ''];

        foreach ($this->leaves($copy) as $w) {
            $type = $this->type($w);
            if ($w === $h1) {
                $out['title'] = $this->headingHtml($w);
            } elseif ($type === 'icon-list' && $out['title'] === '' && $out['pill'] === '') {
                $out['pill'] = $this->text($w);
            } elseif ($type === 'text-editor' && $out['intro'] === '') {
                $out['intro'] = $this->textHtml($w);
            } elseif ($type === 'button') {
                $out['ctas'][] = $this->button($w);
            } elseif ($type === 'icon-box') {
                $box = $this->iconBox($w);
                $out['chips'][] = ['title' => $box['title'], 'text' => $box['text']];
            } else {
                $out['extra'][] = $this->raw([$w]);
            }
        }

        // Everything beside the copy column (the mock-up) stays as it was
        $visual = [];
        foreach ($this->elementChildren($copy->parentNode) as $sibling) {
            if ($sibling !== $copy) {
                $visual[] = $sibling;
            }
        }
        $out['visual'] = $visual ? $this->raw($visual) : '';

        return $out;
    }

    private function section(DOMElement $sec): array
    {
        $children = $this->unwrap($sec);
        $out = ['anchors' => $this->anchors($sec), 'kicker' => '', 'title' => '', 'intro' => '', 'layout' => 'embed', 'footnote' => ''];

        // Lead: headings and text before the first container, or a first container holding only copy
        $lead = [];
        while ($children && $this->isWidget($children[0]) && in_array($this->type($children[0]), ['heading', 'text-editor'], true)) {
            $lead[] = array_shift($children);
        }
        $leadList = null;
        if (! $lead && count($children) > 1 && $this->isCopyOnly($children[0])) {
            foreach ($this->leaves(array_shift($children)) as $w) {
                if ($this->type($w) === 'icon-list') {
                    $leadList = $w;
                } else {
                    $lead[] = $w;
                }
            }
        }
        $this->applyLead($out, $lead);

        // Footnote: text after the last container
        $tail = [];
        while ($children && $this->isWidget(end($children)) && $this->type(end($children)) === 'text-editor' && count($children) > 1) {
            array_unshift($tail, array_pop($children));
        }
        $out['footnote'] = implode('', array_map(fn ($w) => $this->textHtml($w), $tail));

        $all = $this->leavesOf($children);
        $types = array_map(fn ($w) => $this->type($w), $all);

        if (in_array('form', $types, true)) {
            $out['layout'] = 'form';
            $out['list'] = $leadList ? $this->listItems($leadList) : [];
            $out['form'] = $this->raw($children);

            return $out;
        }
        if ($leadList) {
            $out['intro'] .= $this->raw([$leadList]);
        }

        if (in_array('nested-accordion', $types, true) && count(array_filter($types, fn ($t) => $t === 'nested-accordion')) === 1) {
            $acc = $all[array_search('nested-accordion', $types, true)];
            $out['layout'] = 'faq';
            $out['items'] = $this->faqItems($acc);
            $rest = array_filter($all, fn ($w) => $w !== $acc);
            if ($rest) {
                $out['footnote'] .= implode('', array_map(fn ($w) => $this->type($w) === 'text-editor' ? $this->textHtml($w) : $this->raw([$w]), $rest));
            }

            return $out;
        }

        $items = $this->items($children);
        $kinds = array_map(fn ($i) => $i['kind'], $items);
        $known = count(array_filter($kinds, fn ($k) => $k !== 'raw'));

        if ($items && $known === count($items)) {
            $uniq = array_values(array_unique($kinds));
            $out['layout'] = match (true) {
                $uniq === ['feature'] => 'features',
                $uniq === ['review'] => 'reviews',
                $uniq === ['stat'] => 'stats',
                default => 'cards',
            };
            $out['items'] = $items;

            return $out;
        }

        // Not recognised: keep the body exactly as it was
        $out['layout'] = 'embed';
        $out['embed'] = $children ? $this->raw($children) : '';

        return $out;
    }

    private function applyLead(array &$out, array $lead): void
    {
        $headings = array_values(array_filter($lead, fn ($w) => $this->type($w) === 'heading'));
        $texts = array_values(array_filter($lead, fn ($w) => $this->type($w) === 'text-editor'));
        if (count($headings) >= 2) {
            $out['kicker'] = $this->cleanKicker($this->text(array_shift($headings)));
        }
        if ($headings) {
            $out['title'] = $this->headingHtml(array_shift($headings));
        }
        foreach ($headings as $h) {
            $out['intro'] .= '<p class="lp-sub-heading">'.$this->headingHtml($h).'</p>';
        }
        foreach ($texts as $t) {
            $out['intro'] .= $this->textHtml($t);
        }
    }

    // ---------- Items ----------

    /** Turns the section body into items: feature rows, cards, stats, reviews, or raw. */
    private function items(array $children): array
    {
        $nodes = $children;
        // A single wrapper, or rows of columns: dig down to the actual items
        for ($guard = 0; $guard < 4; $guard++) {
            if (count($nodes) === 1 && $this->isContainer($nodes[0]) && ! $this->isFeature($nodes[0])) {
                $nodes = $this->elementChildren($nodes[0]);

                continue;
            }
            $rows = array_filter($nodes, fn ($n) => $this->isContainer($n) && ! $this->isFeature($n) && $this->onlyContainers($n) && count($this->elementChildren($n)) > 1 && ! $this->isLeafItem($n));
            if ($rows && count($rows) === count($nodes)) {
                $flat = [];
                foreach ($nodes as $row) {
                    array_push($flat, ...$this->elementChildren($row));
                }
                $nodes = $flat;

                continue;
            }
            break;
        }

        $items = [];
        foreach ($nodes as $node) {
            $items[] = $this->item($node);
        }

        return $items;
    }

    private function item(DOMElement $node): array
    {
        if (! $this->isContainer($node)) {
            return ['kind' => 'raw', 'html' => $this->raw([$node])];
        }
        if ($this->isFeature($node)) {
            [$copy, $visual] = $this->elementChildren($node);
            $f = ['kind' => 'feature', 'kicker' => '', 'title' => '', 'body' => '', 'ctas' => [], 'visual' => $this->raw([$visual])];
            $headings = 0;
            foreach ($this->leaves($copy) as $w) {
                $t = $this->type($w);
                if ($t === 'heading') {
                    $headings++;
                    if ($headings === 1 && $this->countType($copy, 'heading') >= 2) {
                        $f['kicker'] = $this->cleanKicker($this->text($w));
                    } elseif ($f['title'] === '') {
                        $f['title'] = $this->headingHtml($w);
                    } else {
                        $f['body'] .= '<p class="lp-sub-heading">'.$this->headingHtml($w).'</p>';
                    }
                } elseif ($t === 'text-editor') {
                    $f['body'] .= $this->textHtml($w);
                } elseif ($t === 'icon-list') {
                    $f['body'] .= $this->checkList($this->listItems($w));
                } elseif ($t === 'button') {
                    $f['ctas'][] = $this->button($w);
                } else {
                    $f['body'] .= $this->raw([$w]);
                }
            }

            return $f;
        }

        $leaves = $this->leaves($node);
        $types = array_map(fn ($w) => $this->type($w), $leaves);

        if (in_array('rating', $types, true)) {
            $r = ['kind' => 'review', 'stars' => 5, 'text' => '', 'who' => ''];
            foreach ($leaves as $w) {
                $t = $this->type($w);
                if ($t === 'rating') {
                    $r['stars'] = (int) round((float) ($this->x->query('.//*[@itemprop="ratingValue"]', $w)->item(0)?->getAttribute('content') ?: 5));
                } elseif ($t === 'text-editor') {
                    $r['text'] .= $this->textHtml($w);
                } elseif ($t === 'heading') {
                    $r['who'] .= $this->headingHtml($w);
                } else {
                    return ['kind' => 'raw', 'html' => $this->raw([$node])];
                }
            }

            return $r;
        }

        $boxes = count(array_filter($types, fn ($t) => $t === 'icon-box'));
        if ($boxes === 1) {
            $c = ['kind' => 'card', 'badge' => '', 'stat' => '', 'icon' => '', 'title' => '', 'href' => '', 'text' => '', 'list' => [], 'foot' => '', 'extra' => ''];
            $seenBox = false;
            foreach ($leaves as $w) {
                $t = $this->type($w);
                if ($t === 'icon-box') {
                    $c = array_merge($c, $this->iconBox($w));
                    $seenBox = true;
                } elseif ($t === 'heading' && ! $seenBox && $c['stat'] === '') {
                    $c['stat'] = $this->headingHtml($w);
                } elseif ($t === 'heading' && $seenBox) {
                    $c['foot'] .= $this->headingHtml($w);
                } elseif ($t === 'icon-list') {
                    array_push($c['list'], ...$this->listItems($w));
                } elseif ($t === 'html' && ! $seenBox && $c['badge'] === '' && strlen($this->text($w)) <= 14) {
                    $c['badge'] = $this->text($w);
                } else {
                    $c['extra'] .= $this->raw([$w]);
                }
            }

            return $c;
        }

        if ($boxes === 0 && $types && $types[0] === 'heading' && count($leaves) <= 3
            && ! array_diff($types, ['heading', 'icon-list', 'text-editor'])) {
            $s = ['kind' => 'stat', 'value' => $this->headingHtml($leaves[0]), 'label' => ''];
            foreach (array_slice($leaves, 1) as $w) {
                $s['label'] .= $this->type($w) === 'icon-list'
                    ? implode('<br>', array_column($this->listItems($w), 'html'))
                    : ($this->type($w) === 'heading' ? $this->headingHtml($w) : $this->textHtml($w));
            }

            return $s;
        }

        return ['kind' => 'raw', 'html' => $this->raw([$node])];
    }

    // ---------- Widgets ----------

    private function iconBox(DOMElement $w): array
    {
        $iconWrap = $this->x->query(".//*[{$this->cls('elementor-icon-box-icon')}]", $w)->item(0);
        $icon = $iconWrap ? $this->x->query('.//svg | .//img', $iconWrap)->item(0) : null;
        $title = $this->x->query(".//*[{$this->cls('elementor-icon-box-title')}]", $w)->item(0);
        $desc = $this->x->query(".//*[{$this->cls('elementor-icon-box-description')}]", $w)->item(0);
        $link = $this->x->query('.//a[@href]', $w)->item(0);

        return [
            'icon' => $icon ? $this->doc->saveHTML($icon) : '',
            'title' => $title ? $this->innerClean($title) : '',
            'href' => $link ? $link->getAttribute('href') : '',
            'text' => $desc ? $this->innerClean($desc) : '',
        ];
    }

    private function listItems(DOMElement $w): array
    {
        $items = [];
        foreach ($this->x->query(".//*[{$this->cls('elementor-icon-list-item')}]", $w) as $li) {
            $text = $this->x->query(".//*[{$this->cls('elementor-icon-list-text')}]", $li)->item(0);
            $link = $this->x->query('.//a[@href]', $li)->item(0);
            $items[] = ['html' => $text ? $this->innerClean($text) : trim($li->textContent), 'href' => $link ? $link->getAttribute('href') : ''];
        }

        return $items;
    }

    private function checkList(array $items): string
    {
        $out = '<ul class="lp-checks">';
        foreach ($items as $i) {
            $out .= '<li>'.($i['href'] ? '<a href="'.htmlspecialchars($i['href']).'">'.$i['html'].'</a>' : $i['html']).'</li>';
        }

        return $out.'</ul>';
    }

    private function button(DOMElement $w): array
    {
        $a = $this->x->query('.//a[@href]', $w)->item(0);
        $label = $this->x->query(".//*[{$this->cls('elementor-button-text')}]", $w)->item(0);

        return ['href' => $a ? $a->getAttribute('href') : '#', 'label' => trim(preg_replace('/\s+/', ' ', ($label ?? $w)->textContent))];
    }

    private function faqItems(DOMElement $acc): array
    {
        $items = [];
        foreach ($this->x->query('.//details', $acc) as $d) {
            $title = $this->x->query(".//*[{$this->cls('e-n-accordion-item-title-text')}]", $d)->item(0) ?? $this->x->query('.//summary', $d)->item(0);
            $answer = '';
            $region = $this->x->query('./*[@role="region"]', $d)->item(0);
            foreach ($region ? $this->leaves($region) : [] as $w) {
                $answer .= $this->type($w) === 'text-editor' ? $this->textHtml($w) : $this->raw([$w]);
            }
            $items[] = ['q' => trim(preg_replace('/\s+/', ' ', $title->textContent)), 'a' => $answer];
        }

        return $items;
    }

    /** Heading markup without Elementor's wrapper tag; keeps any custom HTML placed inside it. */
    private function headingHtml(DOMElement $w): string
    {
        $out = '';
        foreach ($w->childNodes as $child) {
            if ($child instanceof DOMElement && str_contains(' '.$child->getAttribute('class').' ', ' elementor-heading-title ')) {
                $out .= $this->innerClean($child);
            } else {
                $out .= $this->doc->saveHTML($child);
            }
        }

        return trim(preg_replace('/\s+/', ' ', $out));
    }

    private function textHtml(DOMElement $w): string
    {
        $html = trim(preg_replace('/\s+/', ' ', $this->inner($w)));
        if ($html === '') {
            return '';
        }

        // Bare text gets a paragraph so spacing works; existing block markup is kept
        return preg_match('#^<(p|ul|ol|div|h[1-6]|table|blockquote)\b#i', $html) ? $html : '<p>'.$html.'</p>';
    }

    /** Original Elementor markup, scoped so the page's Elementor CSS and scripts still apply. */
    private function raw(array $nodes): string
    {
        $html = '';
        $parent = $nodes[0]->parentNode ?? null;
        $siblings = count($nodes) > 1 && $this->isContainer($parent) && ! array_filter($nodes, fn ($n) => $n->parentNode !== $parent);
        if ($siblings) {
            // Several columns of one row: keep their row (the parent's classes) around them, without the rest of it
            $wrap = $parent->cloneNode(false);
            foreach ($nodes as $n) {
                $wrap->appendChild($n->cloneNode(true));
            }
            $html = $this->doc->saveHTML($wrap);
        } else {
            foreach ($nodes as $n) {
                $html .= $this->doc->saveHTML($n);
            }
        }

        return '<div class="lp-raw elementor elementor-'.$this->docId.'" data-elementor-id="'.$this->docId.'" data-elementor-type="wp-page">'.$html.'</div>';
    }

    // ---------- DOM helpers ----------

    private function cls(string $c): string
    {
        return "contains(concat(' ', normalize-space(@class), ' '), ' $c ')";
    }

    private function type(DOMNode $n): string
    {
        return $n instanceof DOMElement && $n->hasAttribute('data-widget_type') ? explode('.', $n->getAttribute('data-widget_type'))[0] : '';
    }

    private function isWidget(DOMNode $n): bool
    {
        return $this->type($n) !== '';
    }

    private function isContainer(DOMNode $n): bool
    {
        return $n instanceof DOMElement && str_contains(' '.$n->getAttribute('class').' ', ' e-con ');
    }

    /** Direct widget/container children, looking through any plain wrapper divs. */
    private function elementChildren(DOMNode $n): array
    {
        $out = [];
        foreach ($n->childNodes as $c) {
            if (! $c instanceof DOMElement) {
                continue;
            }
            if ($this->isWidget($c) || $this->isContainer($c)) {
                $out[] = $c;
            } elseif (! in_array($c->nodeName, ['script', 'style'], true)) {
                array_push($out, ...$this->elementChildren($c));
            }
        }

        return $out;
    }

    /** Children of a section after stepping through single wrapper containers. */
    private function unwrap(DOMElement $sec): array
    {
        $children = $this->elementChildren($sec);
        while (count($children) === 1 && $this->isContainer($children[0])) {
            $children = $this->elementChildren($children[0]);
        }

        return $children;
    }

    /** Every widget inside, in document order. */
    private function leaves(DOMNode $n): array
    {
        $out = [];
        foreach ($this->elementChildren($n) as $c) {
            if ($this->isWidget($c)) {
                $out[] = $c;
            } else {
                array_push($out, ...$this->leaves($c));
            }
        }

        return $out;
    }

    private function leavesOf(array $nodes): array
    {
        $out = [];
        foreach ($nodes as $n) {
            if ($this->isWidget($n)) {
                $out[] = $n;
            } else {
                array_push($out, ...$this->leaves($n));
            }
        }

        return $out;
    }

    private function onlyContainers(DOMElement $n): bool
    {
        foreach ($this->elementChildren($n) as $c) {
            if (! $this->isContainer($c)) {
                return false;
            }
        }

        return true;
    }

    /** A container whose widgets form one card (one icon box, a rating, or a stat). */
    private function isLeafItem(DOMElement $n): bool
    {
        $types = array_map(fn ($w) => $this->type($w), $this->leaves($n));

        return count(array_filter($types, fn ($t) => $t === 'icon-box')) === 1 || in_array('rating', $types, true);
    }

    private function isCopyOnly(DOMNode $n): bool
    {
        if (! $this->isContainer($n)) {
            return false;
        }
        $types = array_map(fn ($w) => $this->type($w), $this->leaves($n));

        return count(array_filter($types, fn ($t) => $t === 'heading')) >= 2 && ! array_diff($types, ['heading', 'text-editor', 'icon-list']);
    }

    /** Copy column (headings + text) next to a visual column. */
    private function isFeature(DOMNode $n): bool
    {
        if (! $this->isContainer($n)) {
            return false;
        }
        $kids = $this->elementChildren($n);
        if (count($kids) !== 2 || ! $this->isContainer($kids[0]) || ! $this->isContainer($kids[1])) {
            return false;
        }
        $types = array_map(fn ($w) => $this->type($w), $this->leaves($kids[0]));

        return in_array('heading', $types, true) && in_array('text-editor', $types, true)
            && ! array_diff($types, ['heading', 'text-editor', 'icon-list', 'button']);
    }

    private function countType(DOMNode $n, string $type): int
    {
        return count(array_filter($this->leaves($n), fn ($w) => $this->type($w) === $type));
    }

    /** Ids on anything the new layout replaces, so in-page links keep working. */
    private function anchors(DOMElement $sec): array
    {
        $ids = [];
        foreach ($this->x->query('descendant-or-self::*[@id]', $sec) as $el) {
            // Ids inside widgets travel with raw markup or belong to Elementor internals
            if ($this->x->query("ancestor::*[@data-widget_type]", $el)->length === 0) {
                $ids[] = $el->getAttribute('id');
            }
        }

        return array_values(array_unique($ids));
    }

    private function text(DOMNode $n): string
    {
        $clone = $n->cloneNode(true);
        foreach (iterator_to_array((new DOMXPath($this->doc))->query('.//style | .//script', $clone)) as $junk) {
            $junk->parentNode->removeChild($junk);
        }

        return trim(preg_replace('/\s+/', ' ', $clone->textContent));
    }

    private function cleanKicker(string $s): string
    {
        return trim(preg_replace('/^[\s—–\-·•]+/u', '', $s));
    }

    private function inner(DOMNode $n): string
    {
        $out = '';
        foreach ($n->childNodes as $c) {
            $out .= $this->doc->saveHTML($c);
        }

        return $out;
    }

    private function innerClean(DOMNode $n): string
    {
        return trim(preg_replace('/\s+/', ' ', $this->inner($n)));
    }
}
