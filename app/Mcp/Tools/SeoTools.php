<?php

namespace App\Mcp\Tools;

use App\Mcp\SiteTool;
use App\Mcp\ToolError;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\SeoMeta;
use App\Services\Activity;
use App\Support\Settings;
use App\Support\Sitemap;
use App\Support\SiteRenderer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class SeoTools
{
    public const SCHEMA_TYPES = ['Organization', 'LocalBusiness', 'Service', 'FAQPage', 'BreadcrumbList', 'Article'];

    /** @return list<SiteTool> */
    public static function tools(): array
    {
        return [
            new SiteTool('get_seo', 'The SEO tags of a page, blog post or category: title, meta description, canonical, robots, Open Graph and Twitter tags.',
                fn (JsonSchema $s) => self::targetSchema($s),
                self::targetRules(),
                function (array $in) {
                    [$item, $kind] = self::target($in);

                    return ['item' => self::label($item, $kind), 'seo' => ($item->seo ?? new SeoMeta)->only(SeoMeta::FIELDS),
                        'robots_default' => setting('robots_default'), 'twitter_card_default' => setting('twitter_card')];
                }),

            new SiteTool('update_seo', 'Change SEO tags of a page, blog post or category. Send only the fields to change; an empty string clears one. robots: e.g. "noindex, follow" (empty = site default).',
                fn (JsonSchema $s) => self::targetSchema($s) + [
                    'title' => $s->string()->description('The <title>, ideally 30-60 characters'),
                    'description' => $s->string()->description('Meta description, ideally 70-160 characters'),
                    'canonical' => $s->string(), 'robots' => $s->string(),
                    'og_title' => $s->string(), 'og_description' => $s->string(), 'og_image' => $s->string(), 'og_type' => $s->string(),
                    'twitter_card' => $s->string()->enum(['summary', 'summary_large_image', '']),
                ],
                self::targetRules() + ['title' => 'nullable|string|max:300', 'description' => 'nullable|string|max:500', 'canonical' => 'nullable|string|max:300',
                    'robots' => ['nullable', 'string', 'regex:/^$|^(?:(?:index|noindex|follow|nofollow|noarchive|nosnippet|noimageindex|max-snippet:-?\d+|max-image-preview:(?:none|standard|large)|max-video-preview:-?\d+)(?:,\s*|$))+$/'],
                    'og_title' => 'nullable|string|max:300', 'og_description' => 'nullable|string|max:500', 'og_image' => 'nullable|string|max:300',
                    'og_type' => 'nullable|in:article,website,', 'twitter_card' => 'nullable|in:summary,summary_large_image,'],
                function (array $in) {
                    [$item, $kind] = self::target($in);
                    $seo = $item->seo ?? $item->seo()->create([]);
                    $before = $seo->only(SeoMeta::FIELDS);
                    $fields = array_intersect_key($in, array_flip(['title', 'description', 'canonical', 'robots', 'og_title', 'og_description', 'og_image', 'og_type', 'twitter_card']));
                    if (isset($fields['og_image']) && $fields['og_image'] !== '') {
                        $image = MediaTools::find(['image' => $fields['og_image']]);
                        $seo->og_image_width = $image->width;
                        $seo->og_image_height = $image->height;
                        $seo->og_image_type = $image->mime;
                        $fields['og_image'] = $image->path;
                    }
                    foreach ($fields as $k => $v) {
                        $seo->$k = $v === '' ? null : $v;
                    }
                    if (array_key_exists('og_image', $fields) && $fields['og_image'] === '') {
                        $seo->og_image_width = $seo->og_image_height = $seo->og_image_type = null;
                    }
                    $seo->save();
                    Activity::log('seo.update', $seo, $before, $seo->only(SeoMeta::FIELDS), $item->url());

                    return ['item' => self::label($item, $kind), 'seo' => $seo->only(SeoMeta::FIELDS), 'warnings' => self::lengthWarnings($seo)];
                }, writes: true),

            new SiteTool('audit_seo', 'Check the live site, or one address, for SEO problems: missing or duplicate titles and descriptions, lengths, no H1 or several, images without alt text, thin content (under 300 words), missing canonical or og:image, noindex pages in the sitemap.',
                fn (JsonSchema $s) => ['path' => $s->string()->description('One address; leave out for the whole site')],
                ['path' => 'nullable|string'],
                fn (array $in) => self::audit($in['path'] ?? null)),

            new SiteTool('get_schema', 'The JSON-LD blocks of a page, blog post or category (id, type, JSON).',
                fn (JsonSchema $s) => self::targetSchema($s),
                self::targetRules(),
                function (array $in) {
                    [$item, $kind] = self::target($in);

                    return ['item' => self::label($item, $kind), 'blocks' => $item->schemaBlocks->map(fn ($b) => ['id' => $b->id, 'type' => $b->type, 'json' => json_decode($b->json, true)])->all()];
                }),

            new SiteTool('update_schema', 'Add, replace or remove JSON-LD on a page, blog post or category: give json (an object with @context and @type) to add a block, block_id + json to replace one, or block_id + remove: true.',
                fn (JsonSchema $s) => self::targetSchema($s) + ['json' => $s->object()->description('The JSON-LD object'), 'block_id' => $s->integer(), 'remove' => $s->boolean()],
                self::targetRules() + ['json' => 'nullable|array', 'block_id' => 'nullable|integer', 'remove' => 'nullable|boolean'],
                function (array $in) {
                    [$item, $kind] = self::target($in);
                    $block = isset($in['block_id']) ? ($item->schemaBlocks()->find($in['block_id']) ?? throw new ToolError('No schema block '.$in['block_id'].' on this item.')) : null;
                    if (! empty($in['remove'])) {
                        $block ?? throw new ToolError('remove needs block_id.');
                        $block->delete();
                        Activity::log('schema.remove', $block, ['type' => $block->type, 'json' => $block->json], null, $item->url());

                        return ['removed' => $block->id];
                    }
                    $json = self::validJsonLd($in['json'] ?? throw new ToolError('Give json.'));
                    $type = $in['json']['@type'] ?? (isset($in['json']['@graph']) ? 'graph' : 'Thing');
                    $before = $block?->only(['type', 'json']);
                    $block ??= $item->schemaBlocks()->make(['sort_order' => ($item->schemaBlocks()->max('sort_order') ?? -1) + 1]);
                    $block->fill(['type' => is_array($type) ? implode(',', $type) : $type, 'json' => $json])->save();
                    Activity::log('schema.save', $block, $before, ['type' => $block->type, 'json' => $json], $item->url());

                    return ['saved' => ['id' => $block->id, 'type' => $block->type]];
                }, writes: true),

            new SiteTool('generate_schema', 'Build valid JSON-LD from the site\'s own data: Organization or LocalBusiness (settings), Service (a page), FAQPage (the page\'s question/answer blocks, or the faq list you give), BreadcrumbList (the address), Article (a blog post). save: true stores it on the item, replacing a block of the same type.',
                fn (JsonSchema $s) => self::targetSchema($s) + [
                    'type' => $s->string()->enum(self::SCHEMA_TYPES)->required(),
                    'faq' => $s->array()->items($s->object(['question' => $s->string(), 'answer' => $s->string()]))->description('FAQPage: the questions, when not taken from the page'),
                    'save' => $s->boolean(),
                ],
                self::targetRules() + ['type' => 'required|in:'.implode(',', self::SCHEMA_TYPES), 'faq' => 'nullable|array', 'faq.*.question' => 'required_with:faq|string', 'faq.*.answer' => 'required_with:faq|string', 'save' => 'nullable|boolean'],
                function (array $in) {
                    $item = null;
                    if (array_intersect_key($in, array_flip(['type_of', 'id', 'slug', 'path']))) {
                        [$item] = self::target($in);
                    }
                    if (! $item && ! in_array($in['type'], ['Organization', 'LocalBusiness'], true)) {
                        throw new ToolError($in['type'].' needs a page, post or category (path, or type_of + id/slug).');
                    }
                    $data = self::generate($in['type'], $item, $in['faq'] ?? null);
                    $saved = null;
                    if (! empty($in['save'])) {
                        $item ?? throw new ToolError('save needs an item to store the block on.');
                        $item->schemaBlocks()->where('type', $in['type'])->get()->each->delete();
                        $saved = $item->schemaBlocks()->create(['type' => $in['type'], 'json' => self::validJsonLd($data), 'sort_order' => ($item->schemaBlocks()->max('sort_order') ?? -1) + 1]);
                        Activity::log('schema.generate', $saved, null, ['type' => $in['type']], $item->url());
                    }

                    return ['json_ld' => $data] + ($saved ? ['saved' => $saved->id] : ['note' => 'Not saved. Pass save: true to store it.']);
                }, writes: true),

            new SiteTool('get_internal_links', 'Links between the site\'s own pages: for one address, the pages it links to (outbound) and the pages that link to it (inbound); for the whole site, each page\'s counts and the pages nothing links to. Header and footer links are left out unless include_navigation.',
                fn (JsonSchema $s) => ['path' => $s->string(), 'include_navigation' => $s->boolean()],
                ['path' => 'nullable|string', 'include_navigation' => 'nullable|boolean'],
                fn (array $in) => self::internalLinks($in['path'] ?? null, $in['include_navigation'] ?? false)),

            new SiteTool('check_broken_links', 'Find links that lead nowhere: every internal link on the live site is checked, and with include_external also links to other sites (HTTP status, up to 150 of them).',
                fn (JsonSchema $s) => ['path' => $s->string()->description('Only the links on this address'), 'include_external' => $s->boolean()],
                ['path' => 'nullable|string', 'include_external' => 'nullable|boolean'],
                fn (array $in) => self::brokenLinks($in['path'] ?? null, $in['include_external'] ?? false)),

            new SiteTool('list_redirects', 'Old addresses that send visitors on: from, to, 301 or 302, and how often each was used.',
                fn (JsonSchema $s) => ['search' => $s->string()],
                ['search' => 'nullable|string'],
                fn (array $in) => ['redirects' => Redirect::when($in['search'] ?? null, fn ($q, $v) => $q->where('from_path', 'like', "%$v%")->orWhere('to_path', 'like', "%$v%"))
                    ->orderBy('from_path')->get()->map(fn ($r) => ['id' => $r->id, 'from' => $r->from_path, 'to' => $r->to_path, 'status' => $r->status_code,
                        'hits' => $r->hits, 'last_hit_at' => $r->last_hit_at?->toIso8601String()])->all()]),

            new SiteTool('save_redirect', 'Send an old address to a new one (301 permanent by default, or 302 temporary). An existing redirect from the same address is replaced.',
                fn (JsonSchema $s) => ['from' => $s->string()->required()->description('Old address, e.g. /old-page/'), 'to' => $s->string()->required()->description('New address, /new-page/ or a full URL'), 'status' => $s->integer()->enum([301, 302])],
                ['from' => 'required|string|max:300', 'to' => 'required|string|max:500', 'status' => 'nullable|in:301,302'],
                function (array $in) {
                    $from = Redirect::normalize($in['from']);
                    $to = preg_match('#^https?://#', $in['to']) ? $in['to'] : Redirect::normalize($in['to']);
                    if ($from === $to) {
                        throw new ToolError('from and to are the same address.');
                    }
                    if (str_starts_with($from, '/api/') || str_starts_with($from, '/mcp')) {
                        throw new ToolError('That address is used by the site itself.');
                    }
                    if ($loop = Redirect::where('from_path', $to)->first()) {
                        throw new ToolError("$to redirects to {$loop->to_path} itself; point $from there directly.");
                    }
                    $live = SiteRenderer::resolve($from);
                    $redirect = Redirect::withTrashed()->updateOrCreate(['from_path' => $from], ['to_path' => $to, 'status_code' => $in['status'] ?? 301, 'deleted_at' => null]);
                    Activity::log('redirect.save', $redirect, null, $redirect->only(['from_path', 'to_path', 'status_code']));

                    return ['saved' => ['id' => $redirect->id, 'from' => $from, 'to' => $to, 'status' => $redirect->status_code]]
                        + ($live ? ['warning' => "$from is a live {$live[0]}; visitors now go to $to instead."] : []);
                }, writes: true),

            new SiteTool('delete_redirect', 'Stop redirecting an old address (by id or from address). It goes to the trash.',
                fn (JsonSchema $s) => ['id' => $s->integer(), 'from' => $s->string()],
                ['id' => 'nullable|integer', 'from' => 'nullable|string'],
                function (array $in) {
                    $redirect = isset($in['id']) ? Redirect::find($in['id']) : (isset($in['from']) ? Redirect::where('from_path', Redirect::normalize($in['from']))->first() : null);
                    $redirect ?? throw new ToolError('No such redirect. list_redirects shows them.');
                    $redirect->delete();
                    Activity::log('redirect.delete', $redirect, $redirect->only(['from_path', 'to_path', 'status_code']));

                    return ['deleted' => ['id' => $redirect->id, 'from' => $redirect->from_path]];
                }, writes: true),

            new SiteTool('validate_sitemap_robots', 'Check the sitemaps and robots.txt: every listed address loads and is indexable, published pages and posts are all listed, hidden and draft ones are not, and robots.txt points at the sitemap.',
                fn (JsonSchema $s) => [],
                [],
                fn () => self::validateSitemap()),

            new SiteTool('update_robots', 'Replace the contents of /robots.txt. Keep a "Sitemap: https://glowthdot.com/sitemap_index.xml" line.',
                fn (JsonSchema $s) => ['content' => $s->string()->required()],
                ['content' => 'required|string|max:5000'],
                function (array $in) {
                    $content = str_replace("\r\n", "\n", $in['content']);
                    if (! preg_match('/^\s*user-agent\s*:/im', $content)) {
                        throw new ToolError('robots.txt needs at least one "User-agent:" line.');
                    }
                    if (preg_match('/^\s*disallow\s*:\s*\/\s*$/im', $content) && ! preg_match('/^\s*allow\s*:/im', $content)) {
                        $warning = 'This blocks the whole site from search engines.';
                    }
                    $before = setting('robots_txt');
                    Settings::set('robots_txt', rtrim($content)."\n");
                    Activity::log('robots.update', null, ['robots_txt' => $before], ['robots_txt' => $content], 'robots.txt');

                    return ['robots_txt' => rtrim($content)."\n"] + (isset($warning) ? ['warning' => $warning] : [])
                        + (! preg_match('/^\s*sitemap\s*:/im', $content) ? ['note' => 'There is no Sitemap: line.'] : []);
                }, writes: true),
        ];
    }

    // ---------------------------------------------------------------- targets

    public static function targetSchema(JsonSchema $s): array
    {
        return [
            'path' => $s->string()->description('Address of the page, post or category archive, e.g. /about-us/'),
            'type_of' => $s->string()->enum(['page', 'post', 'category'])->description('With id or slug, instead of path'),
            'id' => $s->integer(),
            'slug' => $s->string(),
        ];
    }

    public static function targetRules(): array
    {
        return ['path' => 'nullable|string', 'type_of' => 'nullable|in:page,post,category', 'id' => 'nullable|integer', 'slug' => 'nullable|string'];
    }

    /** @return array{0: Page|Post|Category, 1: string} */
    public static function target(array $in): array
    {
        if (isset($in['path'])) {
            $found = SiteRenderer::resolve($in['path'], drafts: true) ?? throw new ToolError("Nothing lives at {$in['path']}.");

            return [$found[1], $found[0]];
        }
        $class = ['page' => Page::class, 'post' => Post::class, 'category' => Category::class][$in['type_of'] ?? ''] ?? throw new ToolError('Give path, or type_of (page, post, category) with id or slug.');
        $item = isset($in['id']) ? $class::find($in['id']) : (isset($in['slug']) ? $class::where('slug', $in['slug'])->first() : null);

        return [$item ?? throw new ToolError("No such {$in['type_of']}."), $in['type_of']];
    }

    private static function label(Model $item, string $kind): array
    {
        return ['kind' => $kind, 'id' => $item->id, 'url' => $item->url(), 'title' => $item->title ?? $item->name];
    }

    private static function lengthWarnings(SeoMeta $seo): array
    {
        $w = [];
        $t = mb_strlen((string) $seo->title);
        $d = mb_strlen((string) $seo->description);
        if (! $t) {
            $w[] = 'No title.';
        } elseif ($t < 30 || $t > 60) {
            $w[] = "Title is $t characters (30-60 shows best).";
        }
        if (! $d) {
            $w[] = 'No meta description.';
        } elseif ($d < 70 || $d > 160) {
            $w[] = "Description is $d characters (70-160 shows best).";
        }

        return $w;
    }

    private static function validJsonLd(array $data): string
    {
        if (! isset($data['@context']) && ! isset($data['@graph'])) {
            $data = ['@context' => 'https://schema.org'] + $data;
        }
        if (! isset($data['@type']) && ! isset($data['@graph'])) {
            throw new ToolError('JSON-LD needs an @type (or an @graph).');
        }

        return str_replace('</', '<\/', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    // ---------------------------------------------------------------- live pages

    /** @return array<string, string> address => rendered HTML of every indexable page, post and archive */
    public static function livePages(): array
    {
        $paths = collect(app(Sitemap::class)->urls())->flatten(1)->pluck('loc')->unique()->values();
        $out = [];
        foreach ($paths as $path) {
            if ($page = app(SiteRenderer::class)->render($path)) {
                $out[$path] = $page['html'];
            }
        }

        return $out;
    }

    private static function bodyOnly(string $html, bool $navigation): string
    {
        $body = substr($html, (int) stripos($html, '<body'));
        if (! $navigation) {
            $body = preg_replace('#<header\b[^>]*id="masthead".*?</header>|<footer\b.*?</footer>#s', '', $body) ?? $body;
        }

        return $body;
    }

    private static function audit(?string $only): array
    {
        $pages = $only ? [SiteRenderer::normalize($only) => (app(SiteRenderer::class)->render($only)['html'] ?? throw new ToolError("Nothing lives at $only."))] : self::livePages();
        $titles = [];
        $descriptions = [];
        $report = [];
        foreach ($pages as $path => $html) {
            [$head] = explode('</head>', $html, 2);
            $main = preg_match('#<div id="content" class="site-content">(.*)</div>\s*<footer#s', $html, $m) ? $m[1] : self::bodyOnly($html, false);
            $title = preg_match('#<title>(.*?)</title>#s', $head, $t) ? html_entity_decode($t[1], ENT_QUOTES | ENT_HTML5) : '';
            $desc = preg_match('#<meta name="description" content="([^"]*)"#', $head, $d) ? html_entity_decode($d[1], ENT_QUOTES | ENT_HTML5) : '';
            $issues = [];
            $tl = mb_strlen($title);
            $dl = mb_strlen($desc);
            if (! $tl) {
                $issues[] = 'missing title';
            } elseif ($tl < 30 || $tl > 60) {
                $issues[] = "title $tl chars";
            }
            if (! $dl) {
                $issues[] = 'missing meta description';
            } elseif ($dl < 70 || $dl > 160) {
                $issues[] = "description $dl chars";
            }
            $h1 = preg_match_all('#<h1\b#i', $main);
            if ($h1 !== 1) {
                $issues[] = $h1 ? "$h1 H1 headings" : 'no H1';
            }
            preg_match_all('#<img\b[^>]*>#i', $main, $imgs);
            $noAlt = array_filter($imgs[0], fn ($i) => ! preg_match('#\balt="[^"]+"#', $i));
            if ($noAlt) {
                $issues[] = count($noAlt).' images without alt text: '.implode(', ', array_slice(array_map(fn ($i) => preg_match('#src="([^"]+)"#', $i, $s) ? $s[1] : '?', $noAlt), 0, 5));
            }
            $words = str_word_count(strip_tags(preg_replace('#<(script|style|svg)\b.*?</\1>#is', ' ', $main)));
            if ($words < 300) {
                $issues[] = "thin content ($words words)";
            }
            if (! preg_match('#<link rel="canonical"#', $head)) {
                $issues[] = 'missing canonical';
            }
            if (! preg_match('#property="og:image"#', $head)) {
                $issues[] = 'missing og:image';
            }
            if (preg_match('#<meta name="robots" content="[^"]*noindex#', $head)) {
                $issues[] = 'noindex (but listed in the sitemap)';
            }
            $titles[$title][] = $path;
            $descriptions[$desc][] = $path;
            $report[$path] = ['title' => $title, 'words' => $words, 'issues' => $issues];
        }
        foreach ([$titles, $descriptions] as $i => $groups) {
            foreach ($groups as $value => $paths) {
                if ($value !== '' && count($paths) > 1) {
                    foreach ($paths as $p) {
                        $report[$p]['issues'][] = ($i ? 'description' : 'title').' shared with '.implode(', ', array_diff($paths, [$p]));
                    }
                }
            }
        }
        $withIssues = array_filter($report, fn ($r) => $r['issues']);

        return ['checked' => count($report), 'with_issues' => count($withIssues), 'pages' => $only ? $report : $withIssues];
    }

    /** Internal links of one page: [path => [href, ...]] */
    private static function linksOf(string $html, bool $navigation): array
    {
        preg_match_all('#<a\b[^>]*\bhref="([^"]+)"#i', self::bodyOnly($html, $navigation), $m);
        $site = preg_quote(rtrim(config('app.canonical_url'), '/'), '#');
        $out = [];
        foreach ($m[1] as $href) {
            $href = html_entity_decode($href);
            if (preg_match('#^(?:'.$site.')?(/[^\#?]*)#', $href, $p) && ! str_starts_with($href, '//')) {
                $out[] = $p[1];
            }
        }

        return $out;
    }

    private static function internalLinks(?string $path, bool $navigation): array
    {
        $pages = self::livePages();
        $out = [];
        $in = [];
        foreach ($pages as $from => $html) {
            $links = array_values(array_unique(array_map(fn ($l) => str_contains(basename($l), '.') ? $l : SiteRenderer::normalize($l), self::linksOf($html, $navigation))));
            $out[$from] = array_values(array_diff($links, [$from]));
            foreach ($out[$from] as $to) {
                $in[$to][] = $from;
            }
        }
        if ($path) {
            $path = SiteRenderer::normalize($path);
            isset($pages[$path]) || throw new ToolError("$path is not a live, indexable address.");

            return ['path' => $path, 'outbound' => $out[$path], 'inbound' => $in[$path] ?? []];
        }

        return ['pages' => collect($pages)->keys()->mapWithKeys(fn ($p) => [$p => ['outbound' => count($out[$p]), 'inbound' => count($in[$p] ?? [])]])->all(),
            'orphans' => collect($pages)->keys()->filter(fn ($p) => empty($in[$p]) && $p !== '/')->values()->all()];
    }

    private static function brokenLinks(?string $only, bool $external): array
    {
        $pages = $only ? [SiteRenderer::normalize($only) => (app(SiteRenderer::class)->render($only)['html'] ?? throw new ToolError("Nothing lives at $only."))] : self::livePages();
        $broken = [];
        $externalLinks = [];
        $checked = [];
        foreach ($pages as $from => $html) {
            foreach (array_unique(self::linksOf($html, true)) as $link) {
                $checked[$link] ??= self::internalOk($link);
                if (! $checked[$link]) {
                    $broken[] = ['on' => $from, 'link' => $link];
                }
            }
            if ($external) {
                preg_match_all('#<a\b[^>]*\bhref="(https?://[^"]+)"#i', $html, $m);
                foreach ($m[1] as $url) {
                    if (! str_starts_with($url, rtrim(config('app.canonical_url'), '/'))) {
                        $externalLinks[html_entity_decode($url)][] = $from;
                    }
                }
            }
        }
        $externalBroken = [];
        foreach (array_slice($externalLinks, 0, 150, true) as $url => $on) {
            try {
                $status = Http::timeout(8)->withOptions(['allow_redirects' => true])->head($url)->status();
                if ($status === 405 || $status === 403) {
                    $status = Http::timeout(8)->get($url)->status();
                }
            } catch (\Throwable $e) {
                $status = 0;
            }
            if ($status === 0 || $status >= 400) {
                $externalBroken[] = ['link' => $url, 'status' => $status ?: 'no answer', 'on' => array_values(array_unique($on))];
            }
        }

        return ['pages_checked' => count($pages), 'internal_links_checked' => count($checked), 'broken_internal' => $broken]
            + ($external ? ['external_links_checked' => min(150, count($externalLinks)), 'broken_external' => $externalBroken] : []);
    }

    private static function internalOk(string $path): bool
    {
        if ($path === '/' || is_file(public_path(ltrim(urldecode($path), '/')))) {
            return true;
        }
        if (preg_match('#^/(api/|robots\.txt$|(?:sitemap_index|sitemap|post-sitemap|page-sitemap|category-sitemap)\.xml$)#', $path)) {
            return true;
        }

        return SiteRenderer::resolve($path) !== null || Redirect::where('from_path', Redirect::normalize($path))->exists();
    }

    private static function validateSitemap(): array
    {
        $problems = [];
        $urls = app(Sitemap::class)->urls();
        $listed = collect($urls)->flatten(1)->pluck('loc')->unique();
        foreach ($listed as $loc) {
            $page = app(SiteRenderer::class)->render($loc);
            if (! $page) {
                $problems[] = "$loc is in the sitemap but does not load.";
            } elseif ($page['noindex']) {
                $problems[] = "$loc is in the sitemap but noindex.";
            }
        }
        foreach (Page::where('status', 'published')->get() as $p) {
            if (! $listed->contains($p->path)) {
                $problems[] = "Published page {$p->path} is missing from the sitemap.";
            }
        }
        foreach (Post::live()->get() as $p) {
            if (! $listed->contains($p->url())) {
                $problems[] = "Published post {$p->url()} is missing from the sitemap.";
            }
        }
        foreach (Page::whereIn('status', ['hidden', 'draft'])->get() as $p) {
            if ($listed->contains($p->path)) {
                $problems[] = "{$p->status} page {$p->path} is listed in the sitemap.";
            }
        }
        $robots = (string) setting('robots_txt');
        $sitemapUrl = rtrim(config('app.canonical_url'), '/').'/sitemap_index.xml';
        if (! str_contains($robots, $sitemapUrl)) {
            $problems[] = "robots.txt does not point at $sitemapUrl.";
        }
        if (preg_match('/^\s*disallow\s*:\s*\/\s*$/im', $robots)) {
            $problems[] = 'robots.txt blocks the whole site.';
        }

        return ['ok' => ! $problems, 'problems' => $problems, 'sitemaps' => array_map(fn ($list) => count($list).' addresses', $urls), 'robots_txt' => $robots];
    }

    // ---------------------------------------------------------------- schema

    private static function generate(string $type, ?Model $item, ?array $faq): array
    {
        $site = rtrim(config('app.canonical_url'), '/');
        $name = setting('site_name', 'Glowth');
        $org = ['@type' => 'Organization', '@id' => $site.'/#organization', 'name' => $name, 'url' => $site.'/',
            'logo' => $site.'/images/2026/07/cropped-glowth-logo-transparent.png',
            'email' => setting('email'), 'telephone' => setting('phone'),
            'sameAs' => array_values(array_filter(array_column((array) setting('social_links', []), 'url')))];
        $url = $item ? $site.$item->url() : $site.'/';
        $seo = $item?->seo;

        $data = match ($type) {
            'Organization' => $org,
            'LocalBusiness' => ['@type' => 'ProfessionalService', '@id' => $site.'/#localbusiness', 'name' => $name, 'url' => $site.'/',
                'image' => $org['logo'], 'telephone' => setting('phone'), 'email' => setting('email'),
                'address' => ['@type' => 'PostalAddress', 'streetAddress' => setting('address'), 'addressCountry' => 'AE'],
                'areaServed' => ['Dubai', 'Abu Dhabi', 'Sharjah', 'United Arab Emirates']],
            'Service' => ['@type' => 'Service', 'name' => $item->title ?? $item->name, 'description' => $seo?->description,
                'url' => $url, 'provider' => ['@id' => $site.'/#organization'], 'areaServed' => 'United Arab Emirates'],
            'FAQPage' => ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($q) => ['@type' => 'Question', 'name' => $q['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q['answer']]], $faq ?? self::faqOf($item))],
            'BreadcrumbList' => ['@type' => 'BreadcrumbList', 'itemListElement' => self::breadcrumbs($item, $site)],
            'Article' => $item instanceof Post ? ['@type' => 'Article', 'headline' => $item->title, 'description' => $item->excerpt,
                'url' => $url, 'mainEntityOfPage' => $url, 'datePublished' => $item->published_at?->toIso8601String(),
                'dateModified' => ($item->modified_at ?? $item->updated_at)?->toIso8601String(),
                'author' => ['@type' => 'Person', 'name' => $item->author_name], 'publisher' => ['@id' => $site.'/#organization'],
                'image' => $item->featured_image ? $site.$item->featured_image : null]
                : throw new ToolError('Article is for blog posts.'),
        };
        if ($type === 'FAQPage' && ! $data['mainEntity']) {
            throw new ToolError('No questions found on this page (<details><summary>…</summary>…</details>). Pass them in "faq".');
        }

        return ['@context' => 'https://schema.org'] + array_filter($data, fn ($v) => $v !== null && $v !== []);
    }

    private static function faqOf(?Model $item): array
    {
        $html = $item instanceof Page ? $item->sections()->where('is_visible', true)->get()->map->html()->implode("\n") : (string) ($item->content ?? '');
        preg_match_all('#<details\b[^>]*>\s*<summary\b[^>]*>(.*?)</summary>(.*?)</details>#s', $html, $m, PREG_SET_ORDER);
        $text = fn ($h) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5)));

        return array_map(fn ($q) => ['question' => $text($q[1]), 'answer' => $text($q[2])], $m);
    }

    private static function breadcrumbs(Model $item, string $site): array
    {
        $crumbs = [['name' => 'Home', 'url' => $site.'/']];
        if ($item instanceof Post) {
            $crumbs[] = ['name' => 'Our Blog', 'url' => $site.'/our-blog/'];
            if ($c = $item->categories->first()) {
                $crumbs[] = ['name' => $c->name, 'url' => $site.$c->url()];
            }
        }
        if (! ($item instanceof Page && $item->path === '/')) {
            $crumbs[] = ['name' => $item->title ?? $item->name, 'url' => $site.$item->url()];
        }

        return array_map(fn ($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url']], $crumbs, array_keys($crumbs));
    }
}
