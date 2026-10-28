<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class Sitemap
{
    public const FILES = ['sitemap_index', 'sitemap', 'post-sitemap', 'page-sitemap', 'category-sitemap'];

    public function xml(string $name): ?string
    {
        if (! in_array($name, self::FILES, true)) {
            return null;
        }

        return Cache::rememberForever('sitemap:'.ContentVersion::get().':'.$name, fn () => $this->build($name));
    }

    /** @return array<string, list<array{loc: string, lastmod: ?string}>> the URLs of each sitemap */
    public function urls(): array
    {
        $date = fn ($d) => $d?->copy()->utc()->format('Y-m-d\TH:i:sP');

        $pages = Page::where('status', 'published')->orderBy('slug')->get()
            ->map(fn ($p) => ['loc' => $p->path, 'lastmod' => $date($p->updated_at)])->all();
        $categories = Category::orderBy('slug')->get()
            // An archive changes when one of its posts does
            ->map(fn ($c) => ['loc' => $c->url(), 'lastmod' => $date($c->posts()->live()->get()->pluck('modified_at')->push($c->updated_at)->filter()->max())])->all();
        $posts = Post::live()->orderBy('slug')->get()
            ->map(fn ($p) => ['loc' => $p->url(), 'lastmod' => $date($p->modified_at ?? $p->updated_at)])->all();

        return [
            'post-sitemap' => $posts,
            // As before: the category archives are listed with the pages too
            'page-sitemap' => array_merge($pages, $categories),
            'category-sitemap' => $categories,
        ];
    }

    private function build(string $name): string
    {
        $site = rtrim(config('app.canonical_url'), '/');
        $urls = $this->urls();
        $e = fn ($s) => htmlspecialchars((string) $s, ENT_XML1);

        if (in_array($name, ['sitemap_index', 'sitemap'], true)) {
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
            foreach ($urls as $file => $list) {
                $last = collect($list)->pluck('lastmod')->filter()->max();
                $xml .= "\t<sitemap>\n\t\t<loc>".$e($site.'/'.$file.'.xml')."</loc>\n".($last ? "\t\t<lastmod>".$e($last)."</lastmod>\n" : '')."\t</sitemap>\n";
            }

            return $xml."</sitemapindex>\n";
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($urls[$name] as $url) {
            $xml .= "\t<url>\n\t\t<loc>".$e($site.$url['loc'])."</loc>\n".($url['lastmod'] ? "\t\t<lastmod>".$e($url['lastmod'])."</lastmod>\n" : '')."\t</url>\n";
        }

        return $xml."</urlset>\n";
    }
}
