<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeV2Test extends TestCase
{
    public function test_home_loads_the_v2_design_layer(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<script src="/js/home-v2\.js\?v=\d+" defer></script>#', $html);
        // home-v2.css ends up in the CSS bundle with the other head styles
        preg_match("~id='glowth-bundle-css' href='([^']+)'~", $html, $bundle);
        $this->assertNotEmpty($bundle);
        $this->assertStringContainsString('.v2-kicker', file_get_contents(public_path(parse_url($bundle[1], PHP_URL_PATH))));
    }

    public function test_sections_have_their_design_hooks(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertSame(15, preg_match_all('#class="elementor-element elementor-element-[0-9a-f]{7} v2-sec v2-sec--(light|soft|dark) #', $html));
        $this->assertSame(preg_match_all('# v2-kicker"#', $html), preg_match_all('# v2-title"#', $html));
        $this->assertStringContainsString('data-v2-stage', $html);
        $this->assertSame(3, substr_count($html, 'class="gh-float gh-float--'));
    }

    public function test_the_process_has_its_own_heading(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertSame(1, substr_count($html, 'Beauty industries we specialize in across the UAE.'));
        $this->assertStringContainsString('From free audit to a <span class="hl">fuller calendar</span>, in 5 steps', $html);
    }

    public function test_other_pages_do_not_load_it(): void
    {
        $this->assertStringNotContainsString('home-v2.js', $this->get('/about-us/')->getContent());
    }
}
