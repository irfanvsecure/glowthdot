<?php

namespace App\Support;

/**
 * A deliberately conservative JavaScript minifier: drops comments, indentation, trailing spaces and
 * blank lines, and nothing else. Line breaks stay, so automatic semicolon insertion works exactly
 * as before, and strings, template literals and regular expressions are copied untouched.
 */
class JsMinifier
{
    public function minify(string $js): string
    {
        $out = '';
        $n = strlen($js);
        $i = 0;
        $lineStart = true;   // only whitespace written since the last newline
        $last = '';          // last significant character written (for telling / from a regex)
        $lastWord = '';

        while ($i < $n) {
            $c = $js[$i];
            $next = $js[$i + 1] ?? '';

            // Comments
            if ($c === '/' && $next === '/') {
                while ($i < $n && $js[$i] !== "\n") {
                    $i++;
                }

                continue;
            }
            if ($c === '/' && $next === '*') {
                $end = strpos($js, '*/', $i + 2);
                $comment = substr($js, $i, $end === false ? $n - $i : $end + 2 - $i);
                $i += strlen($comment);
                // A comment that spanned lines still separates statements
                if (str_contains($comment, "\n")) {
                    $this->newline($out, $lineStart);
                } elseif (! $lineStart && ! str_ends_with($out, ' ')) {
                    $out .= ' ';
                }

                continue;
            }

            // Strings and template literals: copy as they are
            if ($c === '"' || $c === "'" || $c === '`') {
                $start = $i++;
                while ($i < $n && $js[$i] !== $c) {
                    if ($js[$i] === '\\') {
                        $i++;
                    } elseif ($c === '`' && $js[$i] === '$' && ($js[$i + 1] ?? '') === '{') {
                        $i = $this->skipTemplateExpression($js, $i + 2);

                        continue;
                    }
                    $i++;
                }
                $i++;
                $out .= substr($js, $start, $i - $start);
                $lineStart = false;
                $last = $c;
                $lastWord = '';

                continue;
            }

            // Regular expression literals
            if ($c === '/' && $this->regexCanStart($last, $lastWord)) {
                $start = $i++;
                $inClass = false;
                while ($i < $n && $js[$i] !== "\n") {
                    $ch = $js[$i];
                    if ($ch === '\\') {
                        $i += 2;

                        continue;
                    }
                    if ($ch === '[') {
                        $inClass = true;
                    } elseif ($ch === ']') {
                        $inClass = false;
                    } elseif ($ch === '/' && ! $inClass) {
                        break;
                    }
                    $i++;
                }
                $i++;
                while ($i < $n && ctype_alpha($js[$i])) {
                    $i++;
                }
                $out .= substr($js, $start, $i - $start);
                $lineStart = false;
                $last = '/';
                $lastWord = '';

                continue;
            }

            // Whitespace
            if ($c === "\n" || $c === "\r") {
                $this->newline($out, $lineStart);
                $i++;

                continue;
            }
            if ($c === ' ' || $c === "\t") {
                if (! $lineStart && ! str_ends_with($out, ' ')) {
                    $out .= ' ';
                }
                $i++;

                continue;
            }

            $out .= $c;
            $lineStart = false;
            $last = $c;
            $lastWord = (ctype_alnum($c) || $c === '_' || $c === '$') ? $lastWord.$c : '';
            $i++;
        }

        $this->newline($out, $lineStart);

        return $out;
    }

    private function newline(string &$out, bool &$lineStart): void
    {
        $out = rtrim($out, " \t");
        if ($out !== '' && ! str_ends_with($out, "\n")) {
            $out .= "\n";
        }
        $lineStart = true;
    }

    /** Index just after the } that closes a ${ … } inside a template literal */
    private function skipTemplateExpression(string $js, int $i): int
    {
        $depth = 1;
        $n = strlen($js);
        while ($i < $n && $depth > 0) {
            $c = $js[$i];
            if ($c === '"' || $c === "'" || $c === '`') {
                $i++;
                while ($i < $n && $js[$i] !== $c) {
                    $i += $js[$i] === '\\' ? 2 : 1;
                }
            } elseif ($c === '{') {
                $depth++;
            } elseif ($c === '}') {
                $depth--;
            }
            $i++;
        }

        return $i;
    }

    private function regexCanStart(string $last, string $lastWord): bool
    {
        if (in_array($lastWord, ['return', 'typeof', 'case', 'do', 'else', 'in', 'of', 'new', 'delete', 'void', 'throw', 'instanceof', 'yield', 'await'], true)) {
            return true;
        }

        return $last === '' || str_contains('(,=:[!&|?{};+-*%<>~^', $last);
    }
}
