<?php

namespace Tests\Feature;

use App\Support\DesignV2;
use Tests\TestCase;

class DesignV2Test extends TestCase
{
    public static function pages(): array
    {
        return [['/'], ['/about-us/'], ['/services/'], ['/contact-us/'], ['/dubai/'], ['/seo-for-beauty-salons-clinics/'], ['/salon-marketing-dubai-guide/']];
    }

    /** @dataProvider pages */
    public function test_every_page_loads_the_v2_layer(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('#<script src="/js/v2\.js\?v=\d+" defer></script>#', $html));
        preg_match("~id='glowth-bundle-css' href='([^']+)'~", $html, $bundle);
        $this->assertNotEmpty($bundle, "$url has no CSS bundle");
        $this->assertStringContainsString('.lp-kicker::before', file_get_contents(public_path(parse_url($bundle[1], PHP_URL_PATH))));
    }

    public function test_home_configures_the_engine_before_it_runs(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertLessThan(strpos($html, '/js/v2.js'), strpos($html, '/js/home-v2.js'));
    }

    public function test_elementor_pages_get_kicker_and_title_hooks(): void
    {
        $html = $this->get('/services/')->getContent();

        $this->assertStringContainsString('v2-kicker">Our Services', $html);
        $this->assertSame(1, preg_match('#v2-kicker">Our Services.*?v2-title">Beauty Marketing#s', $html));
        $this->assertStringNotContainsString('>- Our Services', $html);
    }

    public function test_marking_leaves_header_footer_and_marked_pages_alone(): void
    {
        $design = new DesignV2();
        $page = '<header><span class="elementor-heading-title elementor-size-default">- Menu</span></header>'
            .'<div data-elementor-type="wp-page"><p class="elementor-heading-title elementor-size-default">— FAQs</p>'
            .'<h2 class="elementor-heading-title elementor-size-default">Questions</h2></div>'
            .'<div data-elementor-type="footer"><span class="elementor-heading-title elementor-size-default">- Links</span></div>';

        $out = $design->apply($page);

        $this->assertStringContainsString('>- Menu<', $out);
        $this->assertStringContainsString('>- Links<', $out);
        $this->assertStringContainsString('v2-kicker">FAQs<', $out);
        $this->assertStringContainsString('v2-title">Questions<', $out);
        $this->assertSame($out, $design->apply($out));
    }
}
