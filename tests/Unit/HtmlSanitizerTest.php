<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private function clean(string $html): string
    {
        return (new HtmlSanitizer)->clean($html);
    }

    public function test_safe_markup_comes_back_byte_for_byte(): void
    {
        $html = "<div class=\"x-element k-1 c-con\" data-anim=\"fadeInUp\" style=\"color:#fff\">\n\t<h2 class='x'>Title &amp; more</h2>"
            .'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 1h22"/></svg>'
            .'<details open><summary>Q?</summary><p>A</p></details><img src="/images/a.webp" alt="A" loading="lazy" />'
            .'<form action="/api/enquiry" method="post"><input type="text" name="form_fields[name]"><button>Send</button></form></div>';

        $this->assertSame($html, $this->clean($html));
    }

    public function test_code_that_can_run_is_removed(): void
    {
        $this->assertSame('<p>Hi</p>', $this->clean('<p>Hi</p><script>alert(1)</script>'));
        $this->assertSame('<p>Hi</p>', $this->clean('<p>Hi</p><style>body{display:none}</style>'));
        $this->assertSame('<a href="#">x</a>', $this->clean('<a href="javascript:alert(1)">x</a>'));
        $this->assertSame('<img src="a.png">', $this->clean('<img src="a.png" onerror="alert(1)">'));
        $this->assertSame('<div>text</div>', $this->clean('<div><object data="x.swf">text</object></div>'));
        $this->assertSame('', $this->clean('<iframe src="https://evil.example/"></iframe>'));
        $this->assertSame('<iframe src="https://www.youtube.com/embed/abc"></iframe>', $this->clean('<iframe src="https://www.youtube.com/embed/abc"></iframe>'));
    }

    public function test_json_ld_is_kept_when_it_is_valid(): void
    {
        $this->assertSame('<script type="application/ld+json">{"@type":"Thing"}</script>', $this->clean('<script type="application/ld+json">{"@type":"Thing"}</script>'));
        $this->assertSame('', $this->clean('<script type="application/ld+json">{not json</script>'));
    }
}
