<?php

namespace App\Support;

/**
 * Wraps each emoji in the page text in <span class="emoji">, sized like the 1em emoji images the
 * pages were designed with (public/css/site.css), so a phone's or PC's own emoji font cannot widen
 * a line and move the layout. Text symbols (→ ★ ✓ ✗) are not emoji here and stay as they are.
 */
class Emoji
{
    private const PATTERN = '/(?:\p{Regional_Indicator}{2}|(?:\p{Emoji_Presentation}|\p{Extended_Pictographic}\x{FE0F})\x{FE0F}?(?:\x{200D}\p{Extended_Pictographic}\x{FE0F}?)*)/u';

    public function wrap(string $html): string
    {
        $start = stripos($html, '<body');
        if ($start === false) {
            return $html;
        }

        // Text between tags only; nothing inside scripts, styles, form fields or attributes
        $parts = preg_split('#(<script\b.*?</script>|<style\b.*?</style>|<textarea\b.*?</textarea>|<[^>]+>)#is', substr($html, $start), -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }
        foreach ($parts as $i => $part) {
            if ($i % 2 === 0 && $part !== '' && preg_match('/[^\x00-\x7F]/', $part)) {
                $parts[$i] = preg_replace(self::PATTERN, '<span class="emoji">$0</span>', $part) ?? $part;
            }
        }

        return substr($html, 0, $start).implode('', $parts);
    }
}
