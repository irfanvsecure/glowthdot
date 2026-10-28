<?php

namespace Tests\Feature\Mcp;

use Tests\Concerns\CallsMcp;
use Tests\TestCase;

class SettingsOpsToolsTest extends TestCase
{
    use CallsMcp;

    public function test_settings_change_the_header_footer_and_buttons(): void
    {
        $settings = $this->tool('get_settings')['settings'];
        $this->assertSame('+971588979925', $settings['phone']['value']);
        $this->assertCount(8, $settings['nav_menu']['value']);

        $menu = $settings['nav_menu']['value'];
        $menu[] = ['label' => 'Pricing', 'url' => '/pricing/'];
        $this->tool('update_settings', ['settings' => [
            'phone' => '+971501112233', 'email' => 'hello@glowthdot.com', 'whatsapp' => '971501112233',
            'header_cta' => ['label' => 'Book a Call', 'url' => '/contact-us/'], 'nav_menu' => $menu,
            'topbar_html' => '<b>New</b> offer <script>x()</script>',
        ]]);
        $html = $this->get('/about-us/')->getContent();
        $this->assertStringContainsString('href="tel:+971501112233"', $html);
        $this->assertStringContainsString('mailto:hello@glowthdot.com', $html);
        $this->assertStringContainsString('https://wa.me/971501112233', $html);
        $this->assertStringContainsString('<div class="h-custom-button">Book a Call</div>', $html);
        $this->assertSame(2, substr_count($html, '>Pricing</a>')); // desktop and mobile menus
        $this->assertStringContainsString('<div class="topbar"><b>New</b> offer </div>', $html);

        $this->assertStringContainsString('email', $this->tool('update_settings', ['settings' => ['email' => 'not-an-email']], error: true)['error']);
        $this->assertStringContainsString('Unknown setting', $this->tool('update_settings', ['settings' => ['db_password' => 'x']], error: true)['error']);
    }

    public function test_footer_columns(): void
    {
        $cols = $this->tool('get_settings', ['key' => 'footer_columns'])['settings']['footer_columns']['value'];
        $cols[0]['links'][] = ['label' => 'Reviews', 'url' => '/reviews-reputation/'];
        $this->tool('update_settings', ['settings' => ['footer_columns' => $cols]]);

        $this->get('/faq/')->assertSee('<span class="x-icon-list-text">Reviews</span>', false);
    }

    public function test_trash_restore_purge_and_activity(): void
    {
        $this->tool('save_redirect', ['from' => '/temp/', 'to' => '/']);
        $id = collect($this->tool('list_redirects')['redirects'])->firstWhere('from', '/temp/')['id'];
        $this->tool('delete_redirect', ['id' => $id]);

        $trash = collect($this->tool('list_trash')['trash']);
        $this->assertTrue($trash->contains(fn ($t) => $t['type'] === 'redirect' && $t['id'] === $id));
        $this->tool('restore_item', ['type' => 'redirect', 'id' => $id]);
        $this->get('/temp/')->assertStatus(301);

        $this->assertStringContainsString('in the trash', $this->tool('purge_item', ['type' => 'redirect', 'id' => $id, 'confirm' => true], error: true)['error']);
        $this->tool('delete_redirect', ['id' => $id]);
        $this->tool('purge_item', ['type' => 'redirect', 'id' => $id, 'confirm' => true]);
        $this->assertDatabaseMissing('redirects', ['id' => $id]);

        $log = $this->tool('activity_log', ['action' => 'redirect', 'with_values' => true])['entries'];
        $this->assertSame(['redirect.delete', 'redirect.delete', 'redirect.save'], array_column($log, 'action'));
        $this->assertSame(['trash.purge', 'trash.restore'], array_column($this->tool('activity_log', ['action' => 'trash'])['entries'], 'action'));
        $this->assertNotEmpty($log[array_search('redirect.save', array_column($log, 'action'))]['after']);
    }

    public function test_cache_performance_and_site_info(): void
    {
        $this->get('/faq/')->assertOk();
        $cleared = $this->tool('clear_cache')['cleared'];
        $this->assertContains('pages and sitemaps', $cleared);
        $this->get('/faq/')->assertOk();

        $perf = $this->tool('audit_performance', ['path' => '/'])['pages']['/'];
        $this->assertGreaterThan(0, $perf['html_kb']);
        $this->assertGreaterThan(0, $perf['css_kb']);

        $info = $this->tool('get_site_info');
        $this->assertSame(49, $info['counts']['pages']);
        $this->assertSame(30, $info['counts']['posts_published']);
        $this->assertSame(PHP_VERSION, $info['versions']['php']);
    }

    public function test_changes_show_on_the_next_request(): void
    {
        // Rendered pages are cached; a write must show at once
        $this->get('/faq/')->assertOk();
        $this->tool('update_settings', ['settings' => ['copyright' => '© 2027 Glowth']]);
        $this->get('/faq/')->assertSee('© 2027 Glowth');
    }
}
