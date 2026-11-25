<?php

namespace Tests\Feature;

use App\Support\JsMinifier;
use Tests\TestCase;

/** Fixes for the site-checker report of Nov 2026 */
class SeoAuditFixesTest extends TestCase
{
    public function test_llms_txt_lists_the_site(): void
    {
        $res = $this->get('/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $text = $res->getContent();
        $this->assertStringStartsWith('# ', $text);
        $this->assertStringContainsString('## Pages', $text);
        $this->assertStringContainsString('/get-a-free-audit/', $text);
    }

    public function test_query_variants_of_a_page_are_noindex(): void
    {
        $res = $this->get('/get-a-free-audit/?goal=More%20bookings')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow');
        $this->assertStringContainsString('<meta name="robots" content="noindex, follow"', $res->getContent());

        $plain = $this->get('/get-a-free-audit/')->assertOk();
        $this->assertFalse($plain->headers->has('X-Robots-Tag'));
        $this->assertStringNotContainsString('content="noindex', $plain->getContent());
    }

    public function test_whatsapp_links_go_through_the_redirect(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('href="https://wa.me/', $html);
        $this->assertStringContainsString('href="/whatsapp/', $html);

        $this->get('/whatsapp/?text=Hi')->assertStatus(302)->assertRedirect('https://wa.me/'.setting('whatsapp').'?text=Hi');
    }

    public function test_local_scripts_are_served_minified(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('#<script[^>]*src="/js/(?!min/)#', $html);
        preg_match('#src="(/js/min/[\w/-]+\.[0-9a-f]{12}\.js)"#', $html, $m);
        $this->assertNotEmpty($m);
        $this->assertFileExists(public_path($m[1]));
    }

    public function test_minifier_keeps_strings_regexes_and_line_breaks(): void
    {
        $js = "// note\nvar a = 'x // not a comment'; /* block */\n  var re = /\\/\\*[a-z]+/g;\n\n  var t = `line1\n   line2`;\nreturn a\n";
        $out = (new JsMinifier)->minify($js);
        $this->assertSame("var a = 'x // not a comment';\nvar re = /\\/\\*[a-z]+/g;\nvar t = `line1\n   line2`;\nreturn a\n", $out);
    }

    public function test_pages_have_no_indentation_runs(): void
    {
        $html = $this->get('/contact-us/')->assertOk()->getContent();
        $body = preg_replace('#<(pre|textarea|script|style)\b.*?</\1>#is', '', $html);
        $this->assertDoesNotMatchRegularExpression('/\n[ \t]+</', $body);
    }
}
