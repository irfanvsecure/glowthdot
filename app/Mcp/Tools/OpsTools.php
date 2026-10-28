<?php

namespace App\Mcp\Tools;

use App\Mcp\SiteTool;
use App\Mcp\ToolError;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\SchemaBlock;
use App\Models\Tag;
use App\Services\Activity;
use App\Services\MediaManager;
use App\Support\ContentVersion;
use App\Support\SiteRenderer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class OpsTools
{
    /** What can be in the trash, by the type name the tools use */
    public const TRASHABLE = ['page' => Page::class, 'section' => PageSection::class, 'post' => Post::class, 'category' => Category::class,
        'tag' => Tag::class, 'image' => Media::class, 'redirect' => Redirect::class, 'schema' => SchemaBlock::class];

    /** @return list<SiteTool> */
    public static function tools(): array
    {
        return [
            new SiteTool('list_trash', 'Everything deleted and not yet purged: pages, sections, posts, categories, tags, images, redirects and schema blocks, newest first.',
                fn (JsonSchema $s) => ['type' => $s->string()->enum(array_keys(self::TRASHABLE))],
                ['type' => 'nullable|in:'.implode(',', array_keys(self::TRASHABLE))],
                function (array $in) {
                    $out = [];
                    foreach (self::TRASHABLE as $type => $class) {
                        if (isset($in['type']) && $in['type'] !== $type) {
                            continue;
                        }
                        foreach ($class::onlyTrashed()->latest('deleted_at')->get() as $item) {
                            $out[] = ['type' => $type, 'id' => $item->id, 'label' => self::label($item), 'deleted_at' => $item->deleted_at->toIso8601String()];
                        }
                    }
                    usort($out, fn ($a, $b) => strcmp($b['deleted_at'], $a['deleted_at']));

                    return ['trash' => $out];
                }),

            new SiteTool('restore_item', 'Bring something back from the trash (list_trash gives type and id). A restored section goes back to its page; a page or post whose address was taken meanwhile is refused.',
                fn (JsonSchema $s) => ['type' => $s->string()->enum(array_keys(self::TRASHABLE))->required(), 'id' => $s->integer()->required()],
                ['type' => 'required|in:'.implode(',', array_keys(self::TRASHABLE)), 'id' => 'required|integer'],
                function (array $in) {
                    $item = self::TRASHABLE[$in['type']]::onlyTrashed()->find($in['id']) ?? throw new ToolError("No {$in['type']} {$in['id']} in the trash.");
                    if ($item instanceof Page && Post::where('slug', $item->slug)->exists()) {
                        throw new ToolError("A blog post now uses /{$item->slug}/.");
                    }
                    if ($item instanceof Post && Page::where('path', $item->url())->exists()) {
                        throw new ToolError("A page now uses {$item->url()}.");
                    }
                    if ($item instanceof PageSection && $item->page?->trashed()) {
                        throw new ToolError('Its page is in the trash too: restore the page first.');
                    }
                    $item->restore();
                    Activity::log('trash.restore', $item, null, ['type' => $in['type']], self::label($item));

                    return ['restored' => ['type' => $in['type'], 'id' => $item->id, 'label' => self::label($item)]];
                }, writes: true),

            new SiteTool('purge_item', 'Delete something in the trash for good (cannot be undone; an image\'s files are removed too). Needs confirm: true.',
                fn (JsonSchema $s) => ['type' => $s->string()->enum(array_keys(self::TRASHABLE))->required(), 'id' => $s->integer()->required(), 'confirm' => $s->boolean()->required()],
                ['type' => 'required|in:'.implode(',', array_keys(self::TRASHABLE)), 'id' => 'required|integer', 'confirm' => 'required|boolean'],
                function (array $in) {
                    if ($in['confirm'] !== true) {
                        throw new ToolError('Purging cannot be undone. Pass confirm: true to go ahead.');
                    }
                    $item = self::TRASHABLE[$in['type']]::onlyTrashed()->find($in['id']) ?? throw new ToolError("No {$in['type']} {$in['id']} in the trash (only trashed items can be purged).");
                    $label = self::label($item);
                    if ($item instanceof Media) {
                        app(MediaManager::class)->deleteFiles($item);
                    }
                    if ($item instanceof Page || $item instanceof Post || $item instanceof Category) {
                        $item->seo()->withTrashed()->forceDelete();
                        $item->schemaBlocks()->withTrashed()->forceDelete();
                    }
                    $item->forceDelete();
                    Activity::log('trash.purge', null, ['type' => $in['type'], 'id' => $in['id']], null, $label);

                    return ['purged' => ['type' => $in['type'], 'id' => $in['id'], 'label' => $label]];
                }, writes: true),

            new SiteTool('activity_log', 'What was changed, by whom and when, with the values before and after. Newest first; filter by action (page.update, section.update, post.create ...), item or date.',
                fn (JsonSchema $s) => ['action' => $s->string(), 'item' => $s->string()->description('Slug, address or setting key'), 'since' => $s->string()->description('ISO date'), 'limit' => $s->integer()->description('Default 50'), 'with_values' => $s->boolean()],
                ['action' => 'nullable|string', 'item' => 'nullable|string', 'since' => 'nullable|date', 'limit' => 'nullable|integer|min:1|max:500', 'with_values' => 'nullable|boolean'],
                fn (array $in) => ['entries' => ActivityLog::query()
                    ->when($in['action'] ?? null, fn ($q, $v) => $q->where('action', 'like', "$v%"))
                    ->when($in['item'] ?? null, fn ($q, $v) => $q->where('subject_label', 'like', "%$v%"))
                    ->when($in['since'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
                    ->latest('id')->limit($in['limit'] ?? 50)->get()
                    ->map(fn ($e) => ['when' => $e->created_at->toIso8601String(), 'who' => $e->actor, 'action' => $e->action,
                        'item' => trim(($e->subject_type ? $e->subject_type.' ' : '').($e->subject_label ?? ($e->subject_id ? '#'.$e->subject_id : '')))]
                        + (! empty($in['with_values']) ? ['before' => $e->before, 'after' => $e->after] : []))->all()]),

            new SiteTool('clear_cache', 'Clear the site\'s caches: rendered pages and sitemaps, compiled views, the combined stylesheets, config and routes (rebuilt straight away), and Cloudflare when it is set up. Normally not needed: every change refreshes what it touches.',
                fn (JsonSchema $s) => [],
                [],
                function () {
                    ContentVersion::bump();
                    $done = ['pages and sitemaps'];
                    Artisan::call('view:clear');
                    $done[] = 'views';
                    foreach (glob(public_path('css/bundle/*.css')) as $file) {
                        @unlink($file);
                    }
                    $done[] = 'combined stylesheets';
                    if (app()->configurationIsCached()) {
                        Artisan::call('config:cache');
                        $done[] = 'config (rebuilt)';
                    }
                    if (app()->routesAreCached()) {
                        Artisan::call('route:cache');
                        $done[] = 'routes (rebuilt)';
                    }
                    $zone = config('services.cloudflare.zone_id');
                    $token = config('services.cloudflare.api_token');
                    if ($zone && $token) {
                        $cf = Http::withToken($token)->timeout(15)->post("https://api.cloudflare.com/client/v4/zones/$zone/purge_cache", ['purge_everything' => true]);
                        $done[] = $cf->successful() ? 'Cloudflare' : 'Cloudflare FAILED: '.$cf->status();
                    }
                    Activity::log('cache.clear', null, null, ['cleared' => $done]);

                    return ['cleared' => $done];
                }, writes: true),

            new SiteTool('audit_performance', 'Page weight of one address or the whole site: HTML, stylesheet, script and image bytes, the heaviest images, images without loading="lazy" below the first screen, and images with no width/height.',
                fn (JsonSchema $s) => ['path' => $s->string()],
                ['path' => 'nullable|string'],
                fn (array $in) => self::performance($in['path'] ?? null)),

            new SiteTool('get_site_info', 'An overview: counts of pages, sections, posts, categories, tags, images, redirects; the latest changes; versions of PHP, Laravel and this MCP server.',
                fn (JsonSchema $s) => [],
                [],
                fn () => [
                    'site' => rtrim(config('app.canonical_url'), '/'),
                    'counts' => [
                        'pages' => Page::count(), 'pages_by_status' => Page::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
                        'sections' => PageSection::count(), 'posts_published' => Post::live()->count(),
                        'posts_draft' => Post::where('status', 'draft')->count(), 'posts_scheduled' => Post::where('status', 'published')->where('published_at', '>', now())->count(),
                        'categories' => Category::count(), 'tags' => Tag::count(), 'images' => Media::count(), 'redirects' => Redirect::count(),
                        'in_trash' => array_sum(array_map(fn ($c) => $c::onlyTrashed()->count(), self::TRASHABLE)),
                    ],
                    'last_page_update' => Page::max('updated_at'), 'last_post_update' => Post::max('updated_at'),
                    'latest_activity' => ActivityLog::latest('id')->limit(5)->get(['created_at', 'action', 'subject_label'])->toArray(),
                    'versions' => ['php' => PHP_VERSION, 'laravel' => app()->version(), 'mcp_server' => '1.0.0'],
                    'content_version' => ContentVersion::get(),
                ]),
        ];
    }

    private static function label($item): string
    {
        return match (true) {
            $item instanceof Page => $item->path.' '.$item->title,
            $item instanceof PageSection => ($item->page?->path ?? '?').'#'.$item->key,
            $item instanceof Post => $item->url().' '.$item->title,
            $item instanceof Category => $item->url().' '.$item->name,
            $item instanceof Tag => $item->name,
            $item instanceof Media => $item->path,
            $item instanceof Redirect => $item->from_path.' -> '.$item->to_path,
            $item instanceof SchemaBlock => $item->type.' on '.($item->schemable?->url() ?? '?'),
            default => (string) $item->getKey(),
        };
    }

    private static function performance(?string $only): array
    {
        $pages = $only ? [SiteRenderer::normalize($only) => (app(SiteRenderer::class)->render($only)['html'] ?? throw new ToolError("Nothing lives at $only."))] : SeoTools::livePages();
        $size = fn (string $path) => is_file($f = public_path(ltrim(parse_url($path, PHP_URL_PATH) ?: '', '/'))) ? filesize($f) : 0;
        $report = [];
        foreach ($pages as $path => $html) {
            [$head, $body] = array_pad(explode('</head>', $html, 2), 2, '');
            preg_match_all("#<link[^>]+rel=['\"]stylesheet['\"][^>]*href=['\"]([^'\"]+)#", $head, $css);
            preg_match_all('#<script\b[^>]*\bsrc="([^"]+)"#', $html, $js);
            preg_match_all('#<img\b[^>]*>#i', $body, $imgs);
            $images = [];
            $lazyMissing = [];
            $noSize = [];
            foreach ($imgs[0] as $i => $img) {
                $src = preg_match('#\ssrc="([^"]+)"#', $img, $s) ? $s[1] : null;
                if (! $src) {
                    continue;
                }
                $images[$src] = $size($src);
                if ($i >= 3 && ! preg_match('#loading="lazy"#', $img)) {
                    $lazyMissing[] = $src;
                }
                if (! preg_match('#\swidth="#', $img) || ! preg_match('#\sheight="#', $img)) {
                    $noSize[] = $src;
                }
            }
            arsort($images);
            $report[$path] = [
                'html_kb' => round(strlen($html) / 1024),
                'css_kb' => round(array_sum(array_map($size, $css[1])) / 1024),
                'js_kb' => round(array_sum(array_map($size, $js[1])) / 1024),
                'images' => count($images), 'images_kb' => round(array_sum($images) / 1024),
                'heaviest_images' => array_map(fn ($b) => round($b / 1024).' KB', array_slice($images, 0, 3, true)),
                'not_lazy' => array_values(array_unique($lazyMissing)), 'no_width_height' => array_values(array_unique($noSize)),
            ];
        }
        uasort($report, fn ($a, $b) => ($b['html_kb'] + $b['images_kb']) <=> ($a['html_kb'] + $a['images_kb']));

        return ['pages' => $report];
    }
}
