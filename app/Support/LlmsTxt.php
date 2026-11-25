<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;

/**
 * /llms.txt (llmstxt.org): a plain-text map of the site for AI assistants. The llms_txt setting
 * replaces it when filled in; otherwise it is built from the published pages and posts.
 */
class LlmsTxt
{
    public function text(): string
    {
        $custom = trim((string) setting('llms_txt', ''));
        if ($custom !== '') {
            return $custom."\n";
        }

        return Cache::rememberForever('llms:'.ContentVersion::get(), fn () => $this->build());
    }

    private function build(): string
    {
        $site = rtrim(config('app.canonical_url'), '/');
        $line = fn ($title, $path, $desc) => '- ['.$this->clean($title).']('.$site.$path.')'.($desc ? ': '.$this->clean($desc) : '');

        $pages = Page::where('status', 'published')->with('seo')->orderBy('slug')->get();
        $home = $pages->firstWhere('slug', 'home');

        $out = '# '.setting('site_name', 'Glowth')."\n\n";
        $out .= '> '.$this->clean($home?->seo?->description ?: (string) setting('footer_tagline'))."\n\n";
        $out .= 'Contact: '.setting('phone').' (phone and WhatsApp), '.setting('email').'. Office: '.setting('address').".\n";
        $out .= 'Free growth audit: '.$site."/get-a-free-audit/\n\n";

        $out .= "## Pages\n\n";
        foreach ($pages as $p) {
            $out .= $line($p->seo?->title ?: $p->title, $p->path, $p->seo?->description)."\n";
        }

        $posts = Post::live()->with('seo')->orderByDesc('published_at')->get();
        if ($posts->isNotEmpty()) {
            $out .= "\n## Blog\n\n";
            foreach ($posts as $p) {
                $out .= $line($p->seo?->title ?: $p->title, $p->url(), $p->seo?->description ?: $p->excerpt)."\n";
            }
        }

        return $out;
    }

    private function clean(?string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $s), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
