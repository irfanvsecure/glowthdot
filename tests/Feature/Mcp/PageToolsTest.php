<?php

namespace Tests\Feature\Mcp;

use App\Models\Page;
use Tests\Concerns\CallsMcp;
use Tests\TestCase;

class PageToolsTest extends TestCase
{
    use CallsMcp;

    public function test_list_and_get_pages(): void
    {
        $pages = $this->tool('list_pages')['pages'];
        $this->assertCount(49, $pages);
        $this->assertCount(27, $this->tool('list_pages', ['status' => 'hidden'])['pages']);

        $page = $this->tool('get_page', ['slug' => 'about-us']);
        $this->assertSame('/about-us/', $page['path']);
        $this->assertSame('About Glowth — Beauty Marketing Agency in the UAE', $page['seo']['title']);
        $this->assertSame(['hero', 'hero-2', 'section-3'], array_column($page['sections'], 'key'));
        $this->assertSame($page, $this->tool('get_page', ['path' => '/about-us']));

        $this->assertStringContainsString('No such page', $this->tool('get_page', ['slug' => 'nope'], error: true)['error']);
        $this->assertStringContainsString('Say which page', $this->tool('get_page', [], error: true)['error']);
    }

    public function test_create_publish_rename_delete_and_restore_a_page(): void
    {
        $created = $this->tool('create_page', ['title' => 'Bridal Salon Marketing', 'meta_description' => 'Marketing for bridal salons in the UAE.',
            'sections' => [['key' => 'intro', 'html' => '<div class="c-con c-parent"><h1>Bridal salons</h1><p>Hello</p></div>']]])['created'];
        $this->assertSame('/bridal-salon-marketing/', $created['path']);
        $this->assertSame('draft', $created['status']);
        $this->get('/bridal-salon-marketing/')->assertNotFound();

        $this->tool('update_page', ['id' => $created['id'], 'status' => 'published']);
        $this->get('/bridal-salon-marketing/')->assertOk()->assertSee('<h1>Bridal salons</h1>', false)
            ->assertSee('<title>Bridal Salon Marketing | Glowth</title>', false);
        $this->assertStringContainsString('/bridal-salon-marketing/<', $this->get('/page-sitemap.xml')->getContent());

        // Same slug again: refused; reserved and invalid slugs too
        $this->assertStringContainsString('already uses', $this->tool('create_page', ['title' => 'Bridal Salon Marketing'], error: true)['error']);
        $this->assertStringContainsString('used by the site', $this->tool('create_page', ['title' => 'X', 'slug' => 'category'], error: true)['error']);
        $this->assertStringContainsString('not a valid slug', $this->tool('create_page', ['title' => 'X', 'slug' => 'Bad Slug'], error: true)['error']);
        $this->assertStringContainsString('already uses', $this->tool('create_page', ['title' => 'X', 'slug' => 'salon-marketing-dubai-guide'], error: true)['error']);

        // New slug: the old address redirects
        $this->tool('update_page', ['id' => $created['id'], 'new_slug' => 'bridal-salons']);
        $this->get('/bridal-salon-marketing/')->assertStatus(301)->assertRedirect('http://localhost/bridal-salons/');
        $this->get('/bridal-salons/')->assertOk();
        $this->assertSame('/bridal-salons/', Page::find($created['id'])->seo->canonical);

        $this->tool('delete_page', ['id' => $created['id']]);
        $this->get('/bridal-salons/')->assertNotFound();
        $this->assertContains('page', array_column($this->tool('list_trash')['trash'], 'type'));
        $this->tool('restore_item', ['type' => 'page', 'id' => $created['id']]);
        $this->get('/bridal-salons/')->assertOk();

        $this->assertStringContainsString('cannot be deleted', $this->tool('delete_page', ['slug' => 'home'], error: true)['error']);
    }

    public function test_hidden_status_keeps_the_page_but_hides_its_links(): void
    {
        $this->tool('update_page', ['slug' => 'about-us', 'status' => 'hidden']);

        $this->get('/about-us/')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringNotContainsString('href="/about-us/"', $this->get('/')->getContent());
    }

    public function test_section_edits_keep_revisions_and_can_be_restored(): void
    {
        $sections = $this->tool('get_page_sections', ['slug' => 'faq'])['sections'];
        $key = $sections[0]['key'];
        $original = $sections[0]['html'];

        // find + replace: must be unique and exact
        $this->assertStringContainsString('not in this section', $this->tool('update_page_section', ['slug' => 'faq', 'section_key' => $key, 'find' => 'zzz-not-there', 'replace' => 'x'], error: true)['error']);

        $find = substr($original, 0, 40);
        $this->tool('update_page_section', ['slug' => 'faq', 'section_key' => $key, 'find' => $find, 'replace' => $find.'<p class="mcp-test">Added by MCP</p>']);
        $this->get('/faq/')->assertSee('Added by MCP');

        $revisions = $this->tool('list_section_revisions', ['slug' => 'faq', 'section_key' => $key])['revisions'];
        $this->assertNotEmpty($revisions);
        $this->tool('restore_section_revision', ['revision_id' => $revisions[0]['id']]);
        $this->get('/faq/')->assertDontSee('Added by MCP');
        $this->assertSame($original, $this->tool('get_page_sections', ['slug' => 'faq', 'section_key' => $key])['sections'][0]['html']);
    }

    public function test_add_hide_reorder_and_delete_sections(): void
    {
        $this->tool('add_page_section', ['slug' => 'about-us', 'key' => 'promo', 'after' => 'hero', 'html' => '<div class="c-con"><p>Promo strip</p><script>alert(1)</script><a href="javascript:alert(1)" onclick="x()">Go</a></div>']);
        $html = $this->tool('get_page_sections', ['slug' => 'about-us', 'section_key' => 'promo'])['sections'][0]['html'];
        $this->assertSame('<div class="c-con"><p>Promo strip</p><a href="#">Go</a></div>', $html);
        $this->assertSame(['hero', 'promo', 'hero-2', 'section-3'], array_column($this->tool('get_page', ['slug' => 'about-us'])['sections'], 'key'));
        $this->get('/about-us/')->assertSee('Promo strip');

        $this->tool('toggle_section_visibility', ['slug' => 'about-us', 'section_key' => 'promo', 'visible' => false]);
        $this->get('/about-us/')->assertDontSee('Promo strip');
        $this->tool('toggle_section_visibility', ['slug' => 'about-us', 'section_key' => 'promo']);
        $this->get('/about-us/')->assertSee('Promo strip');

        $this->tool('reorder_page_sections', ['slug' => 'about-us', 'order' => ['promo', 'hero', 'hero-2', 'section-3']]);
        $this->assertSame('promo', $this->tool('get_page', ['slug' => 'about-us'])['sections'][0]['key']);
        $this->assertStringContainsString('Missing: section-3', $this->tool('reorder_page_sections', ['slug' => 'about-us', 'order' => ['promo', 'hero', 'hero-2']], error: true)['error']);

        $this->assertStringContainsString('already has', $this->tool('add_page_section', ['slug' => 'about-us', 'key' => 'promo', 'html' => '<p>x</p>'], error: true)['error']);
        $this->tool('delete_page_section', ['slug' => 'about-us', 'section_key' => 'promo']);
        $this->get('/about-us/')->assertDontSee('Promo strip');
        $trashed = collect($this->tool('list_trash', ['type' => 'section'])['trash'])->firstWhere('label', '/about-us/#promo');
        $this->tool('restore_item', ['type' => 'section', 'id' => $trashed['id']]);
        $this->get('/about-us/')->assertSee('Promo strip');
    }

    public function test_search_and_replace_text_with_a_dry_run(): void
    {
        $matches = $this->tool('search_content', ['query' => 'Abu Dhabi'])['matches'];
        $this->assertNotEmpty($matches);
        $this->assertContains('section', array_column($matches, 'kind'));

        $dry = $this->tool('replace_text', ['find' => 'Musaffah', 'replace' => 'Mussafah']);
        $this->assertTrue($dry['dry_run']);
        $this->assertNotEmpty($dry['changes']);
        $this->get('/abu-dhabi/')->assertSee('Musaffah');

        $applied = $this->tool('replace_text', ['find' => 'Musaffah', 'replace' => 'Mussafah', 'dry_run' => false, 'only' => 'sections']);
        $this->assertFalse($applied['dry_run']);
        $this->assertSame([], $this->tool('replace_text', ['find' => 'Musaffah', 'replace' => 'x', 'only' => 'sections'])['changes']);
        $this->assertDatabaseHas('activity_log', ['action' => 'section.replace_text']);
    }
}
