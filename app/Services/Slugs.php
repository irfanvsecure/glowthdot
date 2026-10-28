<?php

namespace App\Services;

use App\Mcp\ToolError;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;
use Illuminate\Support\Str;

/**
 * Pages and posts share the site's root (/{slug}/), so a slug must be free in both.
 */
class Slugs
{
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** Addresses the site uses for other things */
    public const RESERVED = ['api', 'mcp', 'category', 'robots', 'sitemap', 'sitemap_index', 'images', 'css', 'js', 'fonts', 'build',
        'storage', 'vendor', 'public', 'index', 'wp-admin', 'wp-content', 'wp-json', 'feed', 'tag', 'author', 'page', 'home'];

    public static function make(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(Str::ascii($text))), '-');
    }

    /** A root slug for a page or post; $ignore is the item being renamed */
    public static function assertFreeRoot(string $slug, ?object $ignore = null): void
    {
        self::assertValid($slug);
        if (in_array($slug, self::RESERVED, true) && ! ($ignore instanceof Page && $ignore->slug === $slug)) {
            throw new ToolError("The slug \"$slug\" is used by the site itself. Pick another.");
        }
        $page = Page::withTrashed()->where('path', '/'.$slug.'/')->first();
        if ($page && ! ($ignore instanceof Page && $ignore->is($page))) {
            throw new ToolError("The page \"{$page->title}\" (id {$page->id}".($page->trashed() ? ', in the trash' : '').") already uses /$slug/.");
        }
        $post = Post::withTrashed()->where('slug', $slug)->first();
        if ($post && ! ($ignore instanceof Post && $ignore->is($post))) {
            throw new ToolError("The blog post \"{$post->title}\" (id {$post->id}".($post->trashed() ? ', in the trash' : '').") already uses /$slug/.");
        }
        if (Redirect::where('from_path', '/'.$slug.'/')->exists()) {
            throw new ToolError("/$slug/ is an old address that redirects elsewhere. Delete that redirect first (list_redirects) or pick another slug.");
        }
    }

    public static function assertFreeCategory(string $slug, ?Category $ignore = null): void
    {
        self::assertValid($slug);
        $found = Category::withTrashed()->where('slug', $slug)->first();
        if ($found && ! $found->is($ignore)) {
            throw new ToolError("The category \"{$found->name}\" (id {$found->id}) already uses this slug.");
        }
    }

    public static function assertFreeTag(string $slug): void
    {
        self::assertValid($slug);
        if (Tag::withTrashed()->where('slug', $slug)->exists()) {
            throw new ToolError("A tag with the slug \"$slug\" exists already.");
        }
    }

    public static function assertValid(string $slug): void
    {
        if (! preg_match(self::PATTERN, $slug) || strlen($slug) > 120) {
            throw new ToolError("\"$slug\" is not a valid slug: use lowercase letters, numbers and single hyphens (e.g. salon-seo-dubai).");
        }
    }

    /** A 301 from the old address to the new one, when a published item is renamed */
    public static function redirectRenamed(string $oldPath, string $newPath): void
    {
        if ($oldPath === $newPath) {
            return;
        }
        // Anything that pointed at the old address now points at the new one
        Redirect::where('to_path', $oldPath)->update(['to_path' => $newPath]);
        Redirect::withTrashed()->where('from_path', $newPath)->forceDelete();
        Redirect::withTrashed()->updateOrCreate(['from_path' => $oldPath], ['to_path' => $newPath, 'status_code' => 301, 'deleted_at' => null]);
        Activity::log('redirect.auto', null, null, ['from' => $oldPath, 'to' => $newPath], $oldPath);
    }
}
