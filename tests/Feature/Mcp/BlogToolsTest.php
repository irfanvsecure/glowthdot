<?php

namespace Tests\Feature\Mcp;

use App\Models\Post;
use Tests\Concerns\CallsMcp;
use Tests\TestCase;

class BlogToolsTest extends TestCase
{
    use CallsMcp;

    public function test_list_and_get_posts(): void
    {
        $this->assertCount(30, $this->tool('list_blogs', ['limit' => 100])['posts']);
        $this->assertCount(16, $this->tool('list_blogs', ['category' => 'salon-marketing', 'limit' => 100])['posts']);

        $post = $this->tool('get_blog', ['slug' => 'salon-marketing-dubai-guide']);
        $this->assertSame('/salon-marketing-dubai-guide/', $post['url']);
        $this->assertStringContainsString('<figure class="blog-hero">', $post['content']);
        $this->assertSame(['salon-marketing'], $post['categories']);
        $this->assertStringContainsString('No such blog post', $this->tool('get_blog', ['slug' => 'nope'], error: true)['error']);
    }

    public function test_write_publish_schedule_and_unpublish_a_post(): void
    {
        $tag = $this->tool('create_tag', ['name' => 'Google Maps'])['created'];
        $created = $this->tool('create_blog', [
            'title' => 'Salon SEO Checklist for 2027', 'excerpt' => 'Twelve things to fix before the year starts.',
            'content' => '<p>First paragraph.</p><h2>Profile</h2><p>Second.</p><script>alert(1)</script>',
            'categories' => ['salon-marketing'], 'tags' => [$tag['slug']],
        ])['created'];
        $this->assertSame('draft', $created['status']);
        $this->get('/salon-seo-checklist-for-2027/')->assertNotFound();
        $this->assertStringNotContainsString('<script>', Post::find($created['id'])->content);

        $this->tool('publish_blog', ['id' => $created['id']]);
        $this->get('/salon-seo-checklist-for-2027/')->assertOk()->assertSee('<h1 class="entry-title">Salon SEO Checklist for 2027</h1>', false)
            ->assertSee('<p>First paragraph.</p>', false);
        $this->get('/category/salon-marketing/')->assertSee('Salon SEO Checklist for 2027');
        $this->get('/our-blog/')->assertSee('Salon SEO Checklist for 2027');
        $this->assertStringContainsString('/salon-seo-checklist-for-2027/<', $this->get('/post-sitemap.xml')->getContent());

        $this->tool('schedule_blog', ['id' => $created['id'], 'published_at' => now()->addDays(3)->toIso8601String()]);
        $this->assertSame('scheduled', $this->tool('get_blog', ['id' => $created['id']])['status']);
        $this->get('/salon-seo-checklist-for-2027/')->assertNotFound();
        $this->assertStringContainsString('has passed', $this->tool('schedule_blog', ['id' => $created['id'], 'published_at' => '2020-01-01'], error: true)['error']);

        $this->tool('publish_blog', ['id' => $created['id']]);
        $this->tool('unpublish_blog', ['id' => $created['id']]);
        $this->get('/salon-seo-checklist-for-2027/')->assertNotFound();

        $this->assertStringContainsString('already uses', $this->tool('create_blog', ['title' => 'About Us', 'slug' => 'about-us', 'content' => '<p>x</p>'], error: true)['error']);
        $this->assertStringContainsString('Unknown categories', $this->tool('create_blog', ['title' => 'Y', 'content' => '<p>x</p>', 'categories' => ['nope']], error: true)['error']);
    }

    public function test_update_rename_delete_and_restore_a_post(): void
    {
        $this->tool('update_blog', ['slug' => 'salon-marketing-dubai-guide', 'find' => 'Your stylists are excellent.', 'replace' => 'Your stylists are brilliant.']);
        $this->get('/salon-marketing-dubai-guide/')->assertSee('Your stylists are brilliant.');

        $this->tool('update_blog', ['slug' => 'salon-marketing-dubai-guide', 'new_slug' => 'salon-bookings-dubai-guide']);
        $this->get('/salon-marketing-dubai-guide/')->assertStatus(301)->assertRedirect('http://localhost/salon-bookings-dubai-guide/');
        $this->get('/salon-bookings-dubai-guide/')->assertOk();

        $id = Post::where('slug', 'salon-bookings-dubai-guide')->value('id');
        $this->tool('delete_blog', ['id' => $id]);
        $this->get('/salon-bookings-dubai-guide/')->assertNotFound();
        $this->tool('restore_item', ['type' => 'post', 'id' => $id]);
        $this->get('/salon-bookings-dubai-guide/')->assertOk();
    }

    public function test_categories_tags_and_stale_posts(): void
    {
        $category = $this->tool('create_category', ['name' => 'Spa Marketing', 'description' => 'Guides for spas.'])['created'];
        $this->assertSame('/category/spa-marketing/', $category['url']);
        $this->tool('set_blog_taxonomies', ['slug' => 'salon-marketing-dubai-guide', 'categories' => ['salon-marketing', 'spa-marketing']]);
        $this->get('/category/spa-marketing/')->assertOk()->assertSee('How to Get More Salon Bookings in Dubai');

        $this->tool('update_category', ['slug' => 'spa-marketing', 'name' => 'Spa & Wellness Marketing', 'new_slug' => 'spa-wellness']);
        $this->get('/category/spa-marketing/')->assertStatus(301);
        $this->get('/category/spa-wellness/')->assertOk()->assertSee('Spa &amp; Wellness Marketing', false);
        $this->assertContains('spa-wellness', array_column($this->tool('list_categories')['categories'], 'slug'));

        $this->tool('delete_category', ['slug' => 'spa-wellness']);
        $this->get('/category/spa-wellness/')->assertNotFound();

        $this->tool('create_tag', ['name' => 'Reviews']);
        $this->assertSame(['reviews'], array_column($this->tool('list_tags')['tags'], 'slug'));
        $this->assertStringContainsString('exists already', $this->tool('create_tag', ['name' => 'Reviews'], error: true)['error']);

        $this->assertNotEmpty($this->tool('list_stale_posts', ['days' => 1])['posts']);
        $this->assertSame([], $this->tool('list_stale_posts', ['days' => 3000])['posts']);
    }

    public function test_category_archives_paginate(): void
    {
        $this->get('/category/salon-marketing/')->assertOk()->assertSee('/category/salon-marketing/page/2/', false);
        $this->get('/category/salon-marketing/page/2/')->assertOk()->assertSee('Page 2 of 2');
        $this->get('/category/salon-marketing/page/3/')->assertNotFound();
        $this->get('/category/salon-marketing/page/1/')->assertNotFound();
    }
}
