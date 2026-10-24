<?php

namespace Tests\Feature;

use Tests\TestCase;

class CardLinksTest extends TestCase
{
    public static function pages(): array
    {
        return [['/', 5], ['/industries/', 25]];
    }

    /**
     * A card link that closes inside a <div> it opened gets split in two by browsers, leaving
     * a link that holds only the aria-hidden icon (Lighthouse: "Links must have discernible text").
     *
     * @dataProvider pages
     */
    public function test_card_links_wrap_whole_elements(string $uri, int $cards): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        preg_match_all('#<a class="glowth-card-link"[^>]*>(.*?)</a>#is', $html, $links);
        $this->assertCount($cards, $links[1]);

        foreach ($links[1] as $inner) {
            $this->assertSame(preg_match_all('#<div\b#i', $inner), preg_match_all('#</div>#i', $inner));
            $this->assertMatchesRegularExpression('#elementor-icon-box-title">\s*\S#', $inner);
        }
    }
}
