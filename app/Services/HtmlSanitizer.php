<?php

namespace App\Services;

/**
 * Cleans HTML that arrives through the MCP tools. It does not parse and rewrite the markup
 * (that would change the page's design); it takes out what can run code and leaves every
 * other byte as it was:
 *  - tags that are not on the allow-list (their text stays),
 *  - <script> other than JSON-LD, <style>, and <iframe> from hosts not on the list,
 *  - on* event attributes, and javascript:, vbscript: and data:text/html in links.
 */
class HtmlSanitizer
{
    public const TAGS = [
        // text and layout
        'a', 'abbr', 'address', 'article', 'aside', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup',
        'dd', 'del', 'details', 'dfn', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3', 'h4',
        'h5', 'h6', 'header', 'hr', 'i', 'img', 'ins', 'kbd', 'li', 'main', 'mark', 'nav', 'ol', 'p', 'picture', 'pre', 'q',
        's', 'section', 'small', 'source', 'span', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th',
        'thead', 'time', 'tr', 'u', 'ul', 'video', 'audio', 'track', 'wbr', 'meter', 'progress', 'output', 'dialog',
        // forms (the lead forms post to /api/enquiry)
        'form', 'input', 'select', 'option', 'optgroup', 'textarea', 'button', 'label', 'fieldset', 'legend', 'datalist',
        // icons and illustrations
        'svg', 'g', 'path', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'rect', 'defs', 'lineargradient',
        'radialgradient', 'stop', 'use', 'symbol', 'clippath', 'mask', 'pattern', 'text', 'tspan', 'title', 'desc',
        'filter', 'fegaussianblur', 'feoffset', 'feblend', 'fecolormatrix', 'femerge', 'femergenode', 'foreignobject',
        // checked separately below
        'script', 'iframe',
    ];

    public const IFRAME_HOSTS = ['www.youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com', 'www.google.com', 'maps.google.com'];

    public function clean(string $html): string
    {
        // <style> blocks: page CSS lives in public/css/pages
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? '';

        // scripts: only JSON-LD data
        $html = preg_replace_callback('#<script\b([^>]*)>(.*?)</script>#is', function ($m) {
            if (! preg_match('#\btype\s*=\s*["\']application/ld\+json["\']#i', $m[1])) {
                return '';
            }
            json_decode($m[2]);

            return json_last_error() === JSON_ERROR_NONE ? '<script type="application/ld+json">'.str_replace('</', '<\/', $m[2]).'</script>' : '';
        }, $html) ?? '';

        // iframes: only from video and map hosts, over https
        $html = preg_replace_callback('#<iframe\b[^>]*>.*?</iframe>|<iframe\b[^>]*/?>#is', function ($m) {
            $src = preg_match('#\bsrc\s*=\s*["\']([^"\']+)#i', $m[0], $s) ? $s[1] : '';
            $host = parse_url($src, PHP_URL_HOST);

            return str_starts_with($src, 'https://') && in_array($host, self::IFRAME_HOSTS, true) ? $m[0] : '';
        }, $html) ?? '';

        // tags off the list (keep what is between them), and attributes that can run code
        return preg_replace_callback('#<(/?)([a-zA-Z][\w:-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>#', function ($m) {
            if (! in_array(strtolower($m[2]), self::TAGS, true)) {
                return '';
            }
            if ($m[1] === '/') {
                return $m[0];
            }
            $attrs = preg_replace('#\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $m[3]) ?? '';
            $attrs = preg_replace_callback('#(\s(?:href|src|action|formaction|xlink:href|poster|data)\s*=\s*)(["\']?)([^"\'\s>]*)\2#i',
                fn ($a) => preg_match('#^\s*(?:javascript|vbscript|data:text/html)#i', html_entity_decode($a[3])) ? $a[1].$a[2].'#'.$a[2] : $a[0],
                $attrs) ?? '';
            $attrs = preg_replace('#\s+(?:srcdoc)\s*=\s*(?:"[^"]*"|\'[^\']*\')#i', '', $attrs) ?? '';

            return '<'.$m[2].$attrs.'>';
        }, $html) ?? '';
    }
}
