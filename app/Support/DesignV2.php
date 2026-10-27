<?php

namespace App\Support;

/**
 * v2 design hooks for the mirrored Elementor pages, added as a page is served
 * (styles: public/css/v2.css, motion: public/js/v2.js). The home page has them in its snapshot
 * already (scripts/home-v2.php), so it is left alone.
 *  - a heading whose text starts with "- " or "— " is a section kicker: the dash goes, .v2-kicker
 *  - the next heading after a kicker is the section title: .v2-title
 */
class DesignV2
{
    private const HEADING = 'class="elementor-heading-title elementor-size-default';

    public function apply(string $html): string
    {
        if (str_contains($html, 'v2-kicker')) {
            return $html;
        }

        // Only the page body, never the header or footer templates
        $start = false;
        foreach (['data-elementor-type="wp-page"', 'data-elementor-type="wp-post"'] as $marker) {
            $start = strpos($html, $marker);
            if ($start !== false) {
                break;
            }
        }
        if ($start === false) {
            return $html;
        }
        $end = strpos($html, 'data-elementor-type="footer"', $start) ?: strlen($html);
        $body = substr($html, $start, $end - $start);

        $body = preg_replace(
            '#<(span|p|h[1-6]) '.self::HEADING.'">\s*[-—]\s+#u',
            '<$1 '.self::HEADING.' v2-kicker">',
            $body
        ) ?? $body;

        $out = '';
        $pos = 0;
        while (($k = strpos($body, 'v2-kicker">', $pos)) !== false) {
            $t = strpos($body, self::HEADING.'">', $k);
            if ($t === false) {
                break;
            }
            $cut = $t + strlen(self::HEADING);
            $out .= substr($body, $pos, $cut - $pos).' v2-title';
            $pos = $cut;
        }
        $body = $out.substr($body, $pos);

        return substr($html, 0, $start).$body.substr($html, $end);
    }
}
