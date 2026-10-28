<?php

namespace App\Support;

/**
 * Finds the child elements of an HTML fragment by reading its tags, without parsing and
 * re-serialising it, so every block comes back byte for byte as it was written.
 */
class HtmlBlocks
{
    private const VOID = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];

    private const TAG = '#\G<(/?)([a-zA-Z][\w:-]*)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*?(/?)>#A';

    /**
     * The top-level elements of $html as [start, end] byte offsets; text between them is skipped.
     *
     * @return list<array{0:int,1:int}>
     */
    public static function children(string $html): array
    {
        $out = [];
        $stack = []; // open elements; a closing tag closes back to its own name (unclosed <p>, <li> ...)
        $start = null;
        $len = strlen($html);
        $i = 0;
        while (($i = strpos($html, '<', $i)) !== false) {
            if (substr($html, $i, 4) === '<!--') {
                $end = strpos($html, '-->', $i);
                $i = $end === false ? $len : $end + 3;

                continue;
            }
            if (! preg_match(self::TAG, $html, $m, 0, $i)) {
                $i++;

                continue;
            }
            [$tag, $closing, $name, $selfClosing] = [$m[0], $m[1] === '/', strtolower($m[2]), $m[3] === '/'];
            $tagEnd = $i + strlen($tag);

            if (! $closing && in_array($name, ['script', 'style', 'textarea'], true)) {
                $close = stripos($html, '</'.$name, $tagEnd);
                $close = $close === false ? $len : strpos($html, '>', $close) + 1;
                if ($stack === []) {
                    $out[] = [$i, $close];
                }
                $i = $close;

                continue;
            }

            if ($closing) {
                $at = array_search($name, array_reverse($stack, true), true);
                if ($at !== false) {
                    $stack = array_slice($stack, 0, $at);
                    if ($stack === [] && $start !== null) {
                        $out[] = [$start, $tagEnd];
                        $start = null;
                    }
                }
            } elseif ($selfClosing || in_array($name, self::VOID, true)) {
                if ($stack === []) {
                    $out[] = [$i, $tagEnd];
                }
            } else {
                if ($stack === []) {
                    $start = $i;
                }
                $stack[] = $name;
            }
            $i = $tagEnd;
        }

        return $out;
    }

    /**
     * The top-level elements of $html as strings.
     *
     * @return list<string>
     */
    public static function elements(string $html): array
    {
        return array_map(fn ($r) => substr($html, $r[0], $r[1] - $r[0]), self::children($html));
    }

    /**
     * The opening tag, inner HTML and closing tag of a single element.
     *
     * @return array{0:string,1:string,2:string}|null
     */
    public static function split(string $element): ?array
    {
        $element = trim($element);
        if (! preg_match('#^<([a-zA-Z][\w:-]*)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>#', $element, $open)) {
            return null;
        }
        $closeTag = '</'.$open[1].'>';
        if (! str_ends_with($element, $closeTag)) {
            return null;
        }
        $inner = substr($element, strlen($open[0]), strlen($element) - strlen($open[0]) - strlen($closeTag));

        return [$open[0], $inner, $closeTag];
    }
}
