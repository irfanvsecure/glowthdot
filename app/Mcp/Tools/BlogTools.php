<?php

namespace App\Mcp\Tools;

use App\Mcp\SiteTool;
use App\Mcp\ToolError;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\SeoMeta;
use App\Models\Tag;
use App\Services\Activity;
use App\Services\HtmlSanitizer;
use App\Services\Slugs;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BlogTools
{
    /** @return list<SiteTool> */
    public static function tools(): array
    {
        return [
            new SiteTool('list_blogs', 'List blog posts, newest first: id, slug, title, status (draft, published, scheduled), dates, categories. Filter by status, category or a word.',
                fn (JsonSchema $s) => [
                    'status' => $s->string()->enum(['draft', 'published', 'scheduled']),
                    'category' => $s->string()->description('Category slug'),
                    'search' => $s->string(),
                    'limit' => $s->integer()->description('Default 50'),
                ],
                ['status' => 'nullable|in:draft,published,scheduled', 'category' => 'nullable|string', 'search' => 'nullable|string|max:100', 'limit' => 'nullable|integer|min:1|max:500'],
                fn (array $in) => ['posts' => Post::with('categories', 'tags')
                    ->when(($in['status'] ?? null) === 'draft', fn ($q) => $q->where('status', 'draft'))
                    ->when(($in['status'] ?? null) === 'published', fn ($q) => $q->live())
                    ->when(($in['status'] ?? null) === 'scheduled', fn ($q) => $q->where('status', 'published')->where('published_at', '>', now()))
                    ->when($in['category'] ?? null, fn ($q, $c) => $q->whereHas('categories', fn ($w) => $w->where('slug', $c)))
                    ->when($in['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('title', 'like', "%$v%")->orWhere('slug', 'like', "%$v%")))
                    ->orderByDesc('published_at')->limit($in['limit'] ?? 50)->get()->map(fn ($p) => self::summary($p))->all()]),

            new SiteTool('get_blog', 'One blog post with its full content (HTML), excerpt, featured image, categories, tags and SEO tags. Find it by id or slug.',
                fn (JsonSchema $s) => self::ref($s),
                self::refRules(),
                function (array $in) {
                    $post = self::find($in);

                    return self::summary($post) + ['excerpt' => $post->excerpt, 'featured_image' => $post->featured_image, 'author' => $post->author_name,
                        'content' => $post->content, 'seo' => $post->seo?->only(SeoMeta::FIELDS),
                        'schema_blocks' => $post->schemaBlocks->map(fn ($b) => ['id' => $b->id, 'type' => $b->type])->all()];
                }),

            new SiteTool('create_blog', 'Write a new blog post at /{slug}/. Content is HTML (headings h2/h3, paragraphs, lists, figures, tables). It is a draft unless you publish it or give a published_at date. SEO title and description are filled from the title and excerpt unless given.',
                fn (JsonSchema $s) => self::fields($s, true),
                self::fieldRules(true),
                function (array $in) {
                    $slug = $in['slug'] ?? Slugs::make($in['title']);
                    Slugs::assertFreeRoot($slug);
                    self::assertImage($in['featured_image'] ?? null);
                    // A new post looks like the latest one: same body classes and stylesheets
                    $like = Post::live()->orderByDesc('published_at')->first();

                    return DB::transaction(function () use ($in, $slug, $like) {
                        $at = isset($in['published_at']) ? Carbon::parse($in['published_at']) : null;
                        $status = $in['status'] ?? ($at ? 'published' : 'draft');
                        $post = Post::create([
                            'slug' => $slug, 'title' => $in['title'], 'excerpt' => $in['excerpt'] ?? null,
                            'content' => app(HtmlSanitizer::class)->clean($in['content']),
                            'featured_image' => $in['featured_image'] ?? null, 'author_name' => $in['author'] ?? setting('default_author', 'Abu Bakar'),
                            'status' => $status, 'published_at' => $status === 'published' ? ($at ?? now()) : $at,
                            'modified_at' => now(), 'body_class' => $like?->body_class, 'assets' => $like?->assets,
                        ]);
                        self::syncTaxonomies($post, $in);
                        $title = $in['meta_title'] ?? $in['title'];
                        $image = $post->image();
                        $post->seo()->create([
                            'title' => $title, 'description' => $in['meta_description'] ?? $in['excerpt'] ?? null,
                            'canonical' => $post->url(), 'og_type' => 'article', 'og_title' => $title,
                            'og_description' => $in['meta_description'] ?? $in['excerpt'] ?? null, 'og_url' => $post->url(),
                            'og_image' => $image?->path, 'og_image_width' => $image?->width, 'og_image_height' => $image?->height, 'og_image_type' => $image?->mime,
                            'published_time' => $post->published_at?->toIso8601String(), 'modified_time' => now()->toIso8601String(), 'author' => $post->author_name,
                        ]);
                        Activity::log('post.create', $post, null, $post->only(['slug', 'title', 'status']));

                        return ['created' => self::summary($post->fresh(['categories', 'tags']))];
                    });
                }, writes: true),

            new SiteTool('update_blog', 'Change a blog post: title, slug, content (full HTML, or find + replace), excerpt, featured image, author, categories, tags. A new slug on a published post adds a 301 from the old address.',
                fn (JsonSchema $s) => self::ref($s) + self::fields($s, false) + [
                    'new_slug' => $s->string(),
                    'find' => $s->string()->description('Change only this exact text in the content'),
                    'replace' => $s->string(),
                ],
                self::refRules() + self::fieldRules(false) + ['new_slug' => 'nullable|string|max:120', 'find' => 'nullable|string|required_with:replace', 'replace' => 'nullable|string|present_with:find'],
                function (array $in) {
                    $post = self::find($in);
                    $before = $post->only(['slug', 'title', 'excerpt', 'featured_image', 'author_name', 'content']);
                    self::assertImage($in['featured_image'] ?? null);
                    if (isset($in['find'])) {
                        $n = substr_count($post->content, $in['find']);
                        if ($n !== 1) {
                            throw new ToolError($n === 0 ? 'The text in "find" is not in the post.' : "The text in \"find\" occurs $n times; make it unique.");
                        }
                        $in['content'] = str_replace($in['find'], $in['replace'] ?? '', $post->content);
                    }
                    if (isset($in['new_slug']) && $in['new_slug'] !== $post->slug) {
                        Slugs::assertFreeRoot($in['new_slug'], $post);
                        $old = $post->url();
                        $post->slug = $in['new_slug'];
                        if ($post->isLive()) {
                            Slugs::redirectRenamed($old, $post->url());
                        }
                        if ($post->seo?->canonical === $old) {
                            $post->seo->update(['canonical' => $post->url(), 'og_url' => $post->url()]);
                        }
                    }
                    foreach (['title' => 'title', 'excerpt' => 'excerpt', 'featured_image' => 'featured_image', 'author' => 'author_name'] as $field => $column) {
                        if (array_key_exists($field, $in) && $in[$field] !== null) {
                            $post->$column = $in[$field];
                        }
                    }
                    if (isset($in['content'])) {
                        $post->content = app(HtmlSanitizer::class)->clean($in['content']);
                    }
                    $post->modified_at = now();
                    $post->save();
                    $post->seo?->update(['modified_time' => now()->toIso8601String()]);
                    self::syncTaxonomies($post, $in);
                    Activity::log('post.update', $post, $before, $post->only(array_keys($before)));

                    return ['updated' => self::summary($post->fresh(['categories', 'tags']))];
                }, writes: true),

            new SiteTool('delete_blog', 'Move a blog post to the trash (restore_item with type "post" brings it back).',
                fn (JsonSchema $s) => self::ref($s),
                self::refRules(),
                function (array $in) {
                    $post = self::find($in);
                    $post->delete();
                    Activity::log('post.delete', $post, $post->only(['slug', 'title', 'status']));

                    return ['deleted' => self::summary($post)];
                }, writes: true),

            new SiteTool('publish_blog', 'Publish a blog post now (or keep its date if it already has one in the past).',
                fn (JsonSchema $s) => self::ref($s),
                self::refRules(),
                fn (array $in) => self::setStatus(self::find($in), 'published', null), writes: true),

            new SiteTool('unpublish_blog', 'Take a blog post off the site: it becomes a draft.',
                fn (JsonSchema $s) => self::ref($s),
                self::refRules(),
                fn (array $in) => self::setStatus(self::find($in), 'draft', null), writes: true),

            new SiteTool('schedule_blog', 'Publish a blog post at a later date and time (ISO 8601, e.g. 2026-11-05T09:00:00+04:00). It appears on the site by itself at that time.',
                fn (JsonSchema $s) => self::ref($s) + ['published_at' => $s->string()->required()],
                self::refRules() + ['published_at' => 'required|date'],
                function (array $in) {
                    $at = Carbon::parse($in['published_at']);
                    if ($at->lte(now())) {
                        throw new ToolError('That time has passed; use publish_blog to publish now.');
                    }

                    return self::setStatus(self::find($in), 'published', $at);
                }, writes: true),

            new SiteTool('list_categories', 'Blog categories with their number of published posts and archive address.',
                fn (JsonSchema $s) => [],
                [],
                fn () => ['categories' => Category::orderBy('name')->get()->map(fn ($c) => self::category($c))->all()]),

            new SiteTool('create_category', 'Add a blog category, with an archive at /category/{slug}/.',
                fn (JsonSchema $s) => ['name' => $s->string()->required(), 'slug' => $s->string(), 'description' => $s->string()],
                ['name' => 'required|string|max:100', 'slug' => 'nullable|string|max:120', 'description' => 'nullable|string|max:1000'],
                function (array $in) {
                    $slug = $in['slug'] ?? Slugs::make($in['name']);
                    Slugs::assertFreeCategory($slug);
                    $like = Category::orderBy('id')->first();
                    $category = Category::create(['slug' => $slug, 'name' => $in['name'], 'description' => $in['description'] ?? null,
                        'body_class' => $like?->body_class, 'assets' => $like?->assets]);
                    $title = $in['name'].' Archives - '.setting('site_name', 'Glowth');
                    $category->seo()->create(['title' => $title, 'description' => $in['description'] ?? null, 'canonical' => $category->url(),
                        'og_type' => 'article', 'og_title' => $title, 'og_description' => $in['description'] ?? null, 'og_url' => $category->url()]);
                    Activity::log('category.create', $category, null, $category->only(['slug', 'name']));

                    return ['created' => self::category($category)];
                }, writes: true),

            new SiteTool('update_category', 'Rename a category, change its slug (adds a 301 from the old archive) or its description.',
                fn (JsonSchema $s) => ['id' => $s->integer(), 'slug' => $s->string(), 'name' => $s->string(), 'new_slug' => $s->string(), 'description' => $s->string()],
                ['id' => 'nullable|integer', 'slug' => 'nullable|string', 'name' => 'nullable|string|max:100', 'new_slug' => 'nullable|string|max:120', 'description' => 'nullable|string|max:1000'],
                function (array $in) {
                    $category = self::findCategory($in);
                    $before = $category->only(['slug', 'name', 'description']);
                    if (isset($in['new_slug']) && $in['new_slug'] !== $category->slug) {
                        Slugs::assertFreeCategory($in['new_slug'], $category);
                        $old = $category->url();
                        $category->slug = $in['new_slug'];
                        Slugs::redirectRenamed($old, $category->url());
                        if ($category->seo?->canonical === $old) {
                            $category->seo->update(['canonical' => $category->url(), 'og_url' => $category->url()]);
                        }
                    }
                    $category->fill(array_filter(['name' => $in['name'] ?? null, 'description' => $in['description'] ?? null], fn ($v) => $v !== null))->save();
                    Activity::log('category.update', $category, $before, $category->only(array_keys($before)));

                    return ['updated' => self::category($category)];
                }, writes: true),

            new SiteTool('delete_category', 'Move a category to the trash. Its posts stay; they just leave this category.',
                fn (JsonSchema $s) => ['id' => $s->integer(), 'slug' => $s->string()],
                ['id' => 'nullable|integer', 'slug' => 'nullable|string'],
                function (array $in) {
                    $category = self::findCategory($in);
                    $category->delete();
                    Activity::log('category.delete', $category, $category->only(['slug', 'name']));

                    return ['deleted' => self::category($category)];
                }, writes: true),

            new SiteTool('list_tags', 'Blog tags with their number of posts.',
                fn (JsonSchema $s) => [],
                [],
                fn () => ['tags' => Tag::withCount('posts')->orderBy('name')->get()->map(fn ($t) => ['id' => $t->id, 'slug' => $t->slug, 'name' => $t->name, 'posts' => $t->posts_count])->all()]),

            new SiteTool('create_tag', 'Add a blog tag.',
                fn (JsonSchema $s) => ['name' => $s->string()->required(), 'slug' => $s->string()],
                ['name' => 'required|string|max:100', 'slug' => 'nullable|string|max:120'],
                function (array $in) {
                    $slug = $in['slug'] ?? Slugs::make($in['name']);
                    Slugs::assertFreeTag($slug);
                    $tag = Tag::create(['slug' => $slug, 'name' => $in['name']]);
                    Activity::log('tag.create', $tag, null, $tag->only(['slug', 'name']));

                    return ['created' => $tag->only(['id', 'slug', 'name'])];
                }, writes: true),

            new SiteTool('set_blog_taxonomies', 'Set a post\'s categories and/or tags (slugs). The lists replace what the post had.',
                fn (JsonSchema $s) => self::ref($s) + ['categories' => $s->array()->items($s->string()), 'tags' => $s->array()->items($s->string())],
                self::refRules() + ['categories' => 'nullable|array', 'categories.*' => 'string', 'tags' => 'nullable|array', 'tags.*' => 'string'],
                function (array $in) {
                    $post = self::find($in);
                    $before = ['categories' => $post->categories->pluck('slug')->all(), 'tags' => $post->tags->pluck('slug')->all()];
                    self::syncTaxonomies($post, $in);
                    $post->load('categories', 'tags');
                    Activity::log('post.taxonomies', $post, $before, ['categories' => $post->categories->pluck('slug')->all(), 'tags' => $post->tags->pluck('slug')->all()]);

                    return ['post' => self::summary($post)];
                }, writes: true),

            new SiteTool('list_stale_posts', 'Published posts whose content has not changed for a number of days (default 180), oldest first: candidates for a refresh.',
                fn (JsonSchema $s) => ['days' => $s->integer()],
                ['days' => 'nullable|integer|min:1|max:3650'],
                function (array $in) {
                    $days = $in['days'] ?? 180;
                    $cutoff = now()->subDays($days);

                    return ['days' => $days, 'posts' => Post::live()->with('categories', 'tags')
                        ->where(fn ($q) => $q->where('modified_at', '<', $cutoff)->orWhere(fn ($w) => $w->whereNull('modified_at')->where('published_at', '<', $cutoff)))
                        ->orderBy('modified_at')->get()->map(fn ($p) => self::summary($p) + ['days_since_update' => (int) ($p->modified_at ?? $p->published_at)->diffInDays(now())])->all()];
                }),
        ];
    }

    // ---------------------------------------------------------------- shared

    private static function ref(JsonSchema $s): array
    {
        return ['id' => $s->integer()->description('Post id'), 'slug' => $s->string()->description('Post slug')];
    }

    private static function refRules(): array
    {
        return ['id' => 'nullable|integer', 'slug' => 'nullable|string'];
    }

    private static function fields(JsonSchema $s, bool $create): array
    {
        return [
            'title' => $create ? $s->string()->required() : $s->string(),
            'slug' => $s->string()->description($create ? 'Made from the title when left out' : 'Which post (see id)'),
            'content' => $create ? $s->string()->required()->description('HTML of the post body') : $s->string()->description('The full new HTML of the post body'),
            'excerpt' => $s->string()->description('One or two sentences for the cards and the meta description'),
            'featured_image' => $s->string()->description('Path of an image from list_images / upload_image, e.g. /images/2026/10/photo.webp'),
            'author' => $s->string(),
            'categories' => $s->array()->items($s->string())->description('Category slugs'),
            'tags' => $s->array()->items($s->string())->description('Tag slugs'),
        ] + ($create ? [
            'status' => $s->string()->enum(['draft', 'published']),
            'published_at' => $s->string()->description('ISO 8601; in the future = scheduled'),
            'meta_title' => $s->string(),
            'meta_description' => $s->string(),
        ] : []);
    }

    private static function fieldRules(bool $create): array
    {
        return [
            'title' => ($create ? 'required' : 'nullable').'|string|max:200',
            'slug' => 'nullable|string|max:120',
            'content' => ($create ? 'required' : 'nullable').'|string',
            'excerpt' => 'nullable|string|max:600',
            'featured_image' => 'nullable|string|max:300',
            'author' => 'nullable|string|max:100',
            'categories' => 'nullable|array', 'categories.*' => 'string',
            'tags' => 'nullable|array', 'tags.*' => 'string',
        ] + ($create ? ['status' => 'nullable|in:draft,published', 'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:200', 'meta_description' => 'nullable|string|max:400'] : []);
    }

    private static function find(array $in): Post
    {
        $post = match (true) {
            isset($in['id']) => Post::find($in['id']),
            isset($in['slug']) => Post::where('slug', $in['slug'])->first(),
            default => throw new ToolError('Say which post: id or slug.'),
        };

        return $post ?? throw new ToolError('No such blog post. list_blogs shows them.');
    }

    private static function findCategory(array $in): Category
    {
        $category = isset($in['id']) ? Category::find($in['id']) : (isset($in['slug']) ? Category::where('slug', $in['slug'])->first() : null);

        return $category ?? throw new ToolError('No such category. list_categories shows them.');
    }

    private static function assertImage(?string $path): void
    {
        if ($path && ! Media::where('path', $path)->exists()) {
            throw new ToolError("No image at $path in the media library (list_images, upload_image).");
        }
    }

    private static function syncTaxonomies(Post $post, array $in): void
    {
        if (isset($in['categories'])) {
            $found = Category::whereIn('slug', $in['categories'])->pluck('id', 'slug');
            if ($missing = array_diff($in['categories'], $found->keys()->all())) {
                throw new ToolError('Unknown categories: '.implode(', ', $missing).'. create_category adds one.');
            }
            $post->categories()->sync($found->values());
        }
        if (isset($in['tags'])) {
            $found = Tag::whereIn('slug', $in['tags'])->pluck('id', 'slug');
            if ($missing = array_diff($in['tags'], $found->keys()->all())) {
                throw new ToolError('Unknown tags: '.implode(', ', $missing).'. create_tag adds one.');
            }
            $post->tags()->sync($found->values());
        }
    }

    private static function setStatus(Post $post, string $status, ?Carbon $at): array
    {
        $before = ['status' => self::state($post), 'published_at' => $post->published_at?->toIso8601String()];
        $post->status = $status;
        if ($status === 'published') {
            $post->published_at = $at ?? ($post->published_at && $post->published_at->lte(now()) ? $post->published_at : now());
            $post->seo?->update(['published_time' => $post->published_at->toIso8601String()]);
        }
        $post->save();
        Activity::log('post.'.($at ? 'schedule' : ($status === 'published' ? 'publish' : 'unpublish')), $post, $before,
            ['status' => self::state($post), 'published_at' => $post->published_at?->toIso8601String()]);

        return ['post' => self::summary($post)];
    }

    private static function state(Post $p): string
    {
        return $p->status === 'published' && $p->published_at?->isFuture() ? 'scheduled' : $p->status;
    }

    private static function summary(Post $p): array
    {
        return ['id' => $p->id, 'slug' => $p->slug, 'url' => $p->url(), 'title' => $p->title, 'status' => self::state($p),
            'published_at' => $p->published_at?->toIso8601String(), 'modified_at' => $p->modified_at?->toIso8601String(),
            'categories' => $p->categories->pluck('slug')->all(), 'tags' => $p->tags->pluck('slug')->all()];
    }

    private static function category(Category $c): array
    {
        return ['id' => $c->id, 'slug' => $c->slug, 'name' => $c->name, 'description' => $c->description, 'url' => $c->url(),
            'posts' => $c->posts()->live()->count()];
    }
}
