<?php

namespace App\Mcp\Tools;

use App\Mcp\SiteTool;
use App\Mcp\ToolError;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\SectionRevision;
use App\Models\SeoMeta;
use App\Services\Activity;
use App\Services\HtmlSanitizer;
use App\Services\Slugs;
use App\Support\SiteRenderer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;

class PageTools
{
    /** @return list<SiteTool> */
    public static function tools(): array
    {
        return [
            new SiteTool('list_pages', 'List the site\'s pages: id, slug, address, title, status, template and number of sections. Filter by status or a word in the title or slug.',
                fn (JsonSchema $s) => [
                    'status' => $s->string()->enum(Page::STATUSES)->description('Only pages with this status'),
                    'search' => $s->string()->description('A word in the title or slug'),
                ],
                ['status' => 'nullable|in:'.implode(',', Page::STATUSES), 'search' => 'nullable|string|max:100'],
                fn (array $in) => ['pages' => Page::withCount('sections')
                    ->when($in['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
                    ->when($in['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('title', 'like', "%$v%")->orWhere('slug', 'like', "%$v%")))
                    ->orderBy('path')->get()
                    ->map(fn ($p) => self::summary($p) + ['sections' => $p->sections_count])->all()]),

            new SiteTool('get_page', 'One page with its settings, SEO tags and a list of its sections (key, type, visible, a text preview). Find it by id, slug or address.',
                fn (JsonSchema $s) => self::pageRef($s),
                self::pageRefRules(),
                function (array $in) {
                    $page = self::find($in);

                    return self::summary($page) + [
                        'body_class' => $page->body_class,
                        'assets' => $page->assets,
                        'seo' => $page->seo?->only(SeoMeta::FIELDS),
                        'schema_blocks' => $page->schemaBlocks->map(fn ($b) => ['id' => $b->id, 'type' => $b->type])->all(),
                        'sections' => $page->sections->map(fn ($s) => self::sectionSummary($s))->all(),
                        'updated_at' => $page->updated_at?->toIso8601String(),
                    ];
                }),

            new SiteTool('create_page', 'Create a page at /{slug}/. It takes the look (body classes, stylesheets, wrapper markup) of an existing page given in "like" (default about-us) and starts with the sections you give, or none. New pages are drafts unless status says otherwise.',
                fn (JsonSchema $s) => [
                    'title' => $s->string()->required()->description('Page name, also the default SEO title'),
                    'slug' => $s->string()->description('Address: /{slug}/. Made from the title when left out'),
                    'status' => $s->string()->enum(Page::STATUSES)->description('Default draft'),
                    'like' => $s->string()->description('Slug of the page whose layout to reuse, e.g. about-us, dubai (location layout), seo-for-beauty-salons-clinics (service layout)'),
                    'sections' => $s->array()->items($s->object(['key' => $s->string(), 'html' => $s->string()]))->description('Sections in order: {"key", "html"}'),
                    'meta_description' => $s->string(),
                ],
                ['title' => 'required|string|max:200', 'slug' => 'nullable|string|max:120', 'status' => 'nullable|in:'.implode(',', Page::STATUSES),
                    'like' => 'nullable|string', 'sections' => 'nullable|array|max:60', 'sections.*.key' => 'nullable|string|max:60',
                    'sections.*.html' => 'required_with:sections|string', 'meta_description' => 'nullable|string|max:400'],
                function (array $in) {
                    $slug = $in['slug'] ?? Slugs::make($in['title']);
                    Slugs::assertFreeRoot($slug);
                    $like = Page::where('slug', $in['like'] ?? 'about-us')->first() ?? throw new ToolError('No page with the slug "'.($in['like'] ?? 'about-us').'" to take the layout from.');

                    return DB::transaction(function () use ($in, $slug, $like) {
                        $page = Page::create([
                            'slug' => $slug, 'path' => '/'.$slug.'/', 'title' => $in['title'], 'template' => 'sections',
                            'status' => $in['status'] ?? 'draft', 'body_class' => $like->body_class,
                            'wrapper_open' => $like->wrapper_open, 'wrapper_close' => $like->wrapper_close,
                            'assets' => $like->assets,
                        ]);
                        foreach (array_values($in['sections'] ?? []) as $i => $section) {
                            $page->sections()->create(['key' => $section['key'] ?? 'section-'.($i + 1), 'type' => 'html',
                                'sort_order' => ($i + 1) * 10, 'content' => ['html' => app(HtmlSanitizer::class)->clean($section['html'])], 'is_visible' => true]);
                        }
                        $page->seo()->create([
                            'title' => $in['title'].' | '.setting('site_name', 'Glowth'), 'description' => $in['meta_description'] ?? null,
                            'canonical' => $page->path, 'og_type' => 'article', 'og_title' => $in['title'].' | '.setting('site_name', 'Glowth'),
                            'og_description' => $in['meta_description'] ?? null, 'og_url' => $page->path,
                        ]);
                        Activity::log('page.create', $page, null, $page->only(['slug', 'title', 'status']));

                        return ['created' => self::summary($page->fresh())];
                    });
                }, writes: true),

            new SiteTool('update_page', 'Change a page\'s title, slug, status (published, draft, hidden) or template. A new slug on a published page adds a 301 from the old address.',
                fn (JsonSchema $s) => self::pageRef($s) + [
                    'title' => $s->string(),
                    'new_slug' => $s->string()->description('The new address /{new_slug}/'),
                    'status' => $s->string()->enum(Page::STATUSES),
                    'template' => $s->string()->enum(Page::TEMPLATES),
                    'body_class' => $s->string()->description('CSS classes of <body> (advanced)'),
                ],
                self::pageRefRules() + ['title' => 'nullable|string|max:200', 'new_slug' => 'nullable|string|max:120',
                    'status' => 'nullable|in:'.implode(',', Page::STATUSES), 'template' => 'nullable|in:'.implode(',', Page::TEMPLATES),
                    'body_class' => 'nullable|string|max:500'],
                function (array $in) {
                    $page = self::find($in);
                    $before = $page->only(['slug', 'path', 'title', 'status', 'template', 'body_class']);
                    if (isset($in['new_slug']) && $in['new_slug'] !== $page->slug) {
                        if ($page->path === '/') {
                            throw new ToolError('The home page always lives at /.');
                        }
                        Slugs::assertFreeRoot($in['new_slug'], $page);
                        $old = $page->path;
                        $page->slug = $in['new_slug'];
                        $page->path = '/'.$in['new_slug'].'/';
                        if ($page->status !== 'draft') {
                            Slugs::redirectRenamed($old, $page->path);
                        }
                        if ($page->seo && $page->seo->canonical === $old) {
                            $page->seo->update(['canonical' => $page->path, 'og_url' => $page->path]);
                        }
                    }
                    $page->fill(array_filter(array_intersect_key($in, array_flip(['title', 'status', 'template', 'body_class'])), fn ($v) => $v !== null))->save();
                    Activity::log('page.update', $page, $before, $page->only(array_keys($before)));

                    return ['updated' => self::summary($page)];
                }, writes: true),

            new SiteTool('delete_page', 'Move a page to the trash (restore_item brings it back). The home page cannot be deleted.',
                fn (JsonSchema $s) => self::pageRef($s),
                self::pageRefRules(),
                function (array $in) {
                    $page = self::find($in);
                    if ($page->path === '/') {
                        throw new ToolError('The home page cannot be deleted.');
                    }
                    $page->delete();
                    Activity::log('page.delete', $page, $page->only(['slug', 'title', 'status']));

                    return ['deleted' => self::summary($page), 'note' => 'In the trash: restore_item with type "page" and this id brings it back.'];
                }, writes: true),

            new SiteTool('get_page_sections', 'The sections of a page in order, with their full HTML. Pass section_key to get one.',
                fn (JsonSchema $s) => self::pageRef($s) + ['section_key' => $s->string(), 'include_hidden' => $s->boolean()->description('Default true')],
                self::pageRefRules() + ['section_key' => 'nullable|string', 'include_hidden' => 'nullable|boolean'],
                function (array $in) {
                    $page = self::find($in);
                    $sections = $page->sections()
                        ->when($in['section_key'] ?? null, fn ($q, $k) => $q->where('key', $k))
                        ->when(($in['include_hidden'] ?? true) === false, fn ($q) => $q->where('is_visible', true))->get();

                    return ['page' => self::summary($page), 'sections' => $sections->map(fn ($s) => self::sectionSummary($s, true))->all()];
                }),

            new SiteTool('add_page_section', 'Add a section to a page: after the section given in "after" (a key), or at the end. Its HTML should use the page\'s existing classes (copy a similar section from get_page_sections).',
                fn (JsonSchema $s) => self::pageRef($s) + [
                    'key' => $s->string()->required()->description('A short name, unique on the page: pricing, faq-2 ...'),
                    'html' => $s->string()->required(),
                    'after' => $s->string()->description('Key of the section to put it after'),
                    'type' => $s->string()->enum(PageSection::TYPES)->description('html (default) or posts_grid (latest posts at <!--posts-->)'),
                    'count' => $s->integer()->description('posts_grid: how many posts, default 6'),
                ],
                self::pageRefRules() + ['key' => 'required|string|max:60|regex:/^[a-z0-9-]+$/', 'html' => 'required|string', 'after' => 'nullable|string',
                    'type' => 'nullable|in:'.implode(',', PageSection::TYPES), 'count' => 'nullable|integer|min:1|max:24'],
                function (array $in) {
                    $page = self::find($in);
                    if ($page->sections()->where('key', $in['key'])->exists()) {
                        throw new ToolError("The page already has a section \"{$in['key']}\".");
                    }
                    $type = $in['type'] ?? 'html';
                    $html = app(HtmlSanitizer::class)->clean($in['html']);
                    if ($type === 'posts_grid' && ! str_contains($html, PageSection::POSTS_MARKER)) {
                        throw new ToolError('A posts_grid section needs '.PageSection::POSTS_MARKER.' where the posts go.');
                    }
                    $sections = $page->sections->values();
                    $at = isset($in['after']) ? $sections->search(fn ($s) => $s->key === $in['after']) : $sections->count() - 1;
                    if ($at === false) {
                        throw new ToolError("No section \"{$in['after']}\" on this page.");
                    }
                    $section = DB::transaction(function () use ($page, $sections, $at, $in, $type, $html) {
                        $new = $page->sections()->create(['key' => $in['key'], 'type' => $type, 'sort_order' => 0, 'is_visible' => true,
                            'content' => ['html' => $html] + ($type === 'posts_grid' ? ['count' => $in['count'] ?? 6] : [])]);
                        $sections->splice($at + 1, 0, [$new]);
                        self::renumber($sections);

                        return $new;
                    });
                    $section->saveRevision('created');
                    Activity::log('section.add', $section, null, ['page' => $page->slug, 'key' => $section->key, 'html' => $html]);

                    return ['added' => self::sectionSummary($section->fresh()), 'order' => $page->sections()->pluck('key')->all()];
                }, writes: true),

            new SiteTool('update_page_section', 'Change a section: give find + replace for a small text or link change (the text must occur exactly once unless replace_all), or html for the whole section. A revision of the old version is kept.',
                fn (JsonSchema $s) => self::pageRef($s) + [
                    'section_key' => $s->string()->required(),
                    'find' => $s->string()->description('Exact text or HTML to change'),
                    'replace' => $s->string(),
                    'replace_all' => $s->boolean(),
                    'html' => $s->string()->description('The full new HTML of the section'),
                    'new_key' => $s->string(),
                    'count' => $s->integer()->description('posts_grid: number of posts'),
                ],
                self::pageRefRules() + ['section_key' => 'required|string', 'find' => 'nullable|string|required_with:replace', 'replace' => 'nullable|string|present_with:find',
                    'replace_all' => 'nullable|boolean', 'html' => 'nullable|string', 'new_key' => 'nullable|string|max:60|regex:/^[a-z0-9-]+$/',
                    'count' => 'nullable|integer|min:1|max:24'],
                function (array $in) {
                    $section = self::section($in);
                    $html = $section->html();
                    if (isset($in['html'])) {
                        $new = app(HtmlSanitizer::class)->clean($in['html']);
                    } elseif (isset($in['find'])) {
                        $n = substr_count($html, $in['find']);
                        if ($n === 0) {
                            throw new ToolError('The text in "find" is not in this section (it must match exactly, including HTML entities such as &amp;).');
                        }
                        if ($n > 1 && empty($in['replace_all'])) {
                            throw new ToolError("The text in \"find\" occurs $n times in this section. Make it longer so it is unique, or pass replace_all: true.");
                        }
                        $new = app(HtmlSanitizer::class)->clean(str_replace($in['find'], $in['replace'] ?? '', $html));
                    } elseif (! isset($in['new_key']) && ! isset($in['count'])) {
                        throw new ToolError('Nothing to change: give find + replace, html, new_key or count.');
                    }
                    if (isset($in['new_key']) && $in['new_key'] !== $section->key && $section->page->sections()->where('key', $in['new_key'])->exists()) {
                        throw new ToolError("The page already has a section \"{$in['new_key']}\".");
                    }
                    $section->saveRevision('before update');
                    $content = $section->content;
                    if (isset($new)) {
                        if ($section->type === 'posts_grid' && ! str_contains($new, PageSection::POSTS_MARKER)) {
                            throw new ToolError('This posts_grid section must keep '.PageSection::POSTS_MARKER.' in its HTML.');
                        }
                        $content['html'] = $new;
                    }
                    if (isset($in['count']) && $section->type === 'posts_grid') {
                        $content['count'] = $in['count'];
                    }
                    $section->update(['content' => $content, 'key' => $in['new_key'] ?? $section->key]);
                    Activity::log('section.update', $section, ['html' => $html], ['html' => $content['html']], $section->page->slug.'#'.$section->key);

                    return ['updated' => self::sectionSummary($section), 'changed_bytes' => strlen($content['html']) - strlen($html)];
                }, writes: true),

            new SiteTool('delete_page_section', 'Move a section to the trash (restore_item with type "section" brings it back).',
                fn (JsonSchema $s) => self::pageRef($s) + ['section_key' => $s->string()->required()],
                self::pageRefRules() + ['section_key' => 'required|string'],
                function (array $in) {
                    $section = self::section($in);
                    $section->saveRevision('before delete');
                    $section->delete();
                    Activity::log('section.delete', $section, ['key' => $section->key, 'html' => $section->html()], null, $section->page->slug.'#'.$section->key);

                    return ['deleted' => ['id' => $section->id, 'key' => $section->key], 'order' => $section->page->sections()->pluck('key')->all()];
                }, writes: true),

            new SiteTool('reorder_page_sections', 'Put a page\'s sections in a new order: the list of all their keys, in the order you want.',
                fn (JsonSchema $s) => self::pageRef($s) + ['order' => $s->array()->items($s->string())->required()],
                self::pageRefRules() + ['order' => 'required|array|min:1', 'order.*' => 'string'],
                function (array $in) {
                    $page = self::find($in);
                    $sections = $page->sections->keyBy('key');
                    $missing = $sections->keys()->diff($in['order'])->values()->all();
                    $unknown = collect($in['order'])->diff($sections->keys())->values()->all();
                    if ($missing || $unknown || count($in['order']) !== count(array_unique($in['order']))) {
                        throw new ToolError('The order must list every section key once.'.($missing ? ' Missing: '.implode(', ', $missing).'.' : '').($unknown ? ' Not on this page: '.implode(', ', $unknown).'.' : ''));
                    }
                    $before = $sections->keys()->all();
                    self::renumber(collect($in['order'])->map(fn ($k) => $sections[$k]));
                    Activity::log('section.reorder', $page, ['order' => $before], ['order' => $in['order']]);

                    return ['order' => $page->sections()->pluck('key')->all()];
                }, writes: true),

            new SiteTool('toggle_section_visibility', 'Show or hide a section without deleting it.',
                fn (JsonSchema $s) => self::pageRef($s) + ['section_key' => $s->string()->required(), 'visible' => $s->boolean()->description('Leave out to flip it')],
                self::pageRefRules() + ['section_key' => 'required|string', 'visible' => 'nullable|boolean'],
                function (array $in) {
                    $section = self::section($in);
                    $section->saveRevision('before visibility change');
                    $section->update(['is_visible' => $in['visible'] ?? ! $section->is_visible]);
                    Activity::log('section.visibility', $section, null, ['is_visible' => $section->is_visible], $section->page->slug.'#'.$section->key);

                    return ['section' => $section->key, 'visible' => $section->is_visible];
                }, writes: true),

            new SiteTool('list_section_revisions', 'Earlier versions of a section (one is kept on every change), newest first.',
                fn (JsonSchema $s) => self::pageRef($s) + ['section_key' => $s->string()->required(), 'with_html' => $s->boolean()],
                self::pageRefRules() + ['section_key' => 'required|string', 'with_html' => 'nullable|boolean'],
                function (array $in) {
                    $section = self::section($in, withTrashed: true);

                    return ['section' => $section->key, 'revisions' => $section->revisions->map(fn ($r) => [
                        'id' => $r->id, 'saved_at' => $r->created_at->toIso8601String(), 'note' => $r->note, 'key' => $r->key,
                        'visible' => $r->is_visible, 'bytes' => strlen($r->content['html'] ?? ''),
                    ] + (! empty($in['with_html']) ? ['html' => $r->content['html'] ?? ''] : ['preview' => self::preview($r->content['html'] ?? '')]))->all()];
                }),

            new SiteTool('restore_section_revision', 'Put a section back to an earlier version (list_section_revisions). The current version is kept as a revision too.',
                fn (JsonSchema $s) => ['revision_id' => $s->integer()->required()],
                ['revision_id' => 'required|integer'],
                function (array $in) {
                    $revision = SectionRevision::find($in['revision_id']) ?? throw new ToolError('No revision with this id.');
                    $section = $revision->section;
                    if ($section->trashed()) {
                        $section->restore();
                    }
                    $section->saveRevision('before restore');
                    $before = $section->content;
                    $section->update(['content' => $revision->content, 'is_visible' => $revision->is_visible, 'key' => $revision->key]);
                    Activity::log('section.restore', $section, ['html' => $before['html'] ?? ''], ['html' => $revision->content['html'] ?? '', 'revision' => $revision->id]);

                    return ['restored' => self::sectionSummary($section)];
                }, writes: true),

            new SiteTool('search_content', 'Find text across all page sections, blog posts and SEO titles/descriptions. Matches are case-insensitive; HTML tags are searched too.',
                fn (JsonSchema $s) => ['query' => $s->string()->required(), 'limit' => $s->integer()->description('Default 50')],
                ['query' => 'required|string|min:2|max:200', 'limit' => 'nullable|integer|min:1|max:500'],
                fn (array $in) => ['matches' => self::search($in['query'], $in['limit'] ?? 50)]),

            new SiteTool('replace_text', 'Find and replace text everywhere (page sections, blog post content, excerpts and SEO texts). dry_run (the default) only shows what would change; run again with dry_run: false to apply. Every changed section keeps a revision.',
                fn (JsonSchema $s) => [
                    'find' => $s->string()->required(), 'replace' => $s->string()->required(),
                    'dry_run' => $s->boolean()->description('Default true'),
                    'case_sensitive' => $s->boolean()->description('Default true'),
                    'only' => $s->string()->enum(['sections', 'posts', 'seo'])->description('Limit to one kind'),
                ],
                ['find' => 'required|string|min:2', 'replace' => 'present|string', 'dry_run' => 'nullable|boolean', 'case_sensitive' => 'nullable|boolean',
                    'only' => 'nullable|in:sections,posts,seo'],
                fn (array $in) => self::replaceEverywhere($in), writes: true),
        ];
    }

    // ---------------------------------------------------------------- shared

    public static function pageRef(JsonSchema $s): array
    {
        return [
            'id' => $s->integer()->description('Page id'),
            'slug' => $s->string()->description('Page slug (home for the home page)'),
            'path' => $s->string()->description('Page address, e.g. /about-us/'),
        ];
    }

    public static function pageRefRules(): array
    {
        return ['id' => 'nullable|integer', 'slug' => 'nullable|string', 'path' => 'nullable|string'];
    }

    public static function find(array $in, bool $withTrashed = false): Page
    {
        $q = Page::query()->when($withTrashed, fn ($q) => $q->withTrashed());
        $page = match (true) {
            isset($in['id']) => $q->find($in['id']),
            isset($in['slug']) => $q->where('slug', $in['slug'])->first(),
            isset($in['path']) => $q->where('path', SiteRenderer::normalize($in['path']))->first(),
            default => throw new ToolError('Say which page: id, slug or path.'),
        };

        return $page ?? throw new ToolError('No such page. list_pages shows them all.');
    }

    private static function section(array $in, bool $withTrashed = false): PageSection
    {
        $page = self::find($in);

        return $page->sections()->when($withTrashed, fn ($q) => $q->withTrashed())->where('key', $in['section_key'])->first()
            ?? throw new ToolError("No section \"{$in['section_key']}\" on {$page->path}. Its sections: ".$page->sections->pluck('key')->implode(', '));
    }

    public static function summary(Page $p): array
    {
        return ['id' => $p->id, 'slug' => $p->slug, 'path' => $p->path, 'title' => $p->title, 'status' => $p->status, 'template' => $p->template];
    }

    private static function sectionSummary(PageSection $s, bool $html = false): array
    {
        return ['id' => $s->id, 'key' => $s->key, 'type' => $s->type, 'order' => $s->sort_order, 'visible' => $s->is_visible,
            'bytes' => strlen($s->html())]
            + ($s->type === 'posts_grid' ? ['count' => $s->content['count'] ?? 6] : [])
            + ($html ? ['html' => $s->html()] : ['preview' => self::preview($s->html())]);
    }

    public static function preview(string $html, int $length = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(preg_replace('#<(script|style|svg)\b.*?</\1>#is', ' ', $html)), ENT_QUOTES | ENT_HTML5)));

        return mb_strlen($text) > $length ? mb_substr($text, 0, $length).'…' : $text;
    }

    private static function renumber($sections): void
    {
        foreach ($sections->values() as $i => $section) {
            if ($section->sort_order !== ($i + 1) * 10) {
                $section->update(['sort_order' => ($i + 1) * 10]);
            }
        }
    }

    private static function search(string $query, int $limit): array
    {
        $out = [];
        $snippet = function (string $haystack) use ($query) {
            $at = mb_stripos($haystack, $query);

            return $at === false ? null : '…'.mb_substr($haystack, max(0, $at - 60), mb_strlen($query) + 120).'…';
        };
        foreach (PageSection::with('page')->get() as $s) {
            if ($s->page && ($snip = $snippet($s->html()))) {
                $out[] = ['kind' => 'section', 'page' => $s->page->path, 'section_key' => $s->key, 'snippet' => $snip, 'count' => substr_count(mb_strtolower($s->html()), mb_strtolower($query))];
            }
        }
        foreach (Post::all() as $p) {
            foreach (['title', 'excerpt', 'content'] as $field) {
                if ($snip = $snippet((string) $p->$field)) {
                    $out[] = ['kind' => 'post', 'post' => $p->url(), 'field' => $field, 'snippet' => $snip];
                }
            }
        }
        foreach (SeoMeta::with('seoable')->get() as $m) {
            foreach (['title', 'description', 'og_title', 'og_description'] as $field) {
                if ($m->seoable && ($snip = $snippet((string) $m->$field))) {
                    $out[] = ['kind' => 'seo', 'item' => $m->seoable->url(), 'field' => $field, 'snippet' => $snip];
                }
            }
        }

        return array_slice($out, 0, $limit);
    }

    private static function replaceEverywhere(array $in): array
    {
        $dry = $in['dry_run'] ?? true;
        $cs = $in['case_sensitive'] ?? true;
        $count = fn (string $s) => $cs ? substr_count($s, $in['find']) : substr_count(mb_strtolower($s), mb_strtolower($in['find']));
        $swap = fn (string $s) => $cs ? str_replace($in['find'], $in['replace'], $s) : str_ireplace($in['find'], $in['replace'], $s);
        $changes = [];
        $only = $in['only'] ?? null;
        $sanitizer = app(HtmlSanitizer::class);

        DB::transaction(function () use (&$changes, $dry, $count, $swap, $only, $sanitizer) {
            if (! $only || $only === 'sections') {
                foreach (PageSection::with('page')->get() as $s) {
                    if ($n = $count($s->html())) {
                        $changes[] = ['kind' => 'section', 'page' => $s->page?->path, 'section_key' => $s->key, 'occurrences' => $n];
                        if (! $dry) {
                            $s->saveRevision('before replace_text');
                            $before = $s->html();
                            $s->update(['content' => ['html' => $sanitizer->clean($swap($before))] + $s->content]);
                            Activity::log('section.replace_text', $s, ['html' => $before], ['html' => $s->html()], ($s->page?->slug).'#'.$s->key);
                        }
                    }
                }
            }
            if (! $only || $only === 'posts') {
                foreach (Post::all() as $p) {
                    $fields = array_filter(['title', 'excerpt', 'content'], fn ($f) => $p->$f && $count((string) $p->$f));
                    if ($fields) {
                        $changes[] = ['kind' => 'post', 'post' => $p->url(), 'fields' => array_values($fields), 'occurrences' => array_sum(array_map(fn ($f) => $count((string) $p->$f), $fields))];
                        if (! $dry) {
                            $before = $p->only($fields);
                            foreach ($fields as $f) {
                                $p->$f = $f === 'content' ? $sanitizer->clean($swap($p->$f)) : $swap($p->$f);
                            }
                            $p->modified_at = now();
                            $p->save();
                            Activity::log('post.replace_text', $p, $before, $p->only($fields));
                        }
                    }
                }
            }
            if (! $only || $only === 'seo') {
                foreach (SeoMeta::with('seoable')->get() as $m) {
                    $fields = array_filter(['title', 'description', 'og_title', 'og_description'], fn ($f) => $m->$f && $count((string) $m->$f));
                    if ($fields && $m->seoable) {
                        $changes[] = ['kind' => 'seo', 'item' => $m->seoable->url(), 'fields' => array_values($fields)];
                        if (! $dry) {
                            $before = $m->only($fields);
                            foreach ($fields as $f) {
                                $m->$f = $swap($m->$f);
                            }
                            $m->save();
                            Activity::log('seo.replace_text', $m, $before, $m->only($fields), $m->seoable->url());
                        }
                    }
                }
            }
        });

        return ['dry_run' => $dry, 'changes' => $changes, 'total' => count($changes)]
            + ($dry && $changes ? ['next' => 'Nothing changed yet. Run again with dry_run: false to apply.'] : []);
    }
}
