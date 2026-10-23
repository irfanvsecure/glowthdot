<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SiteController extends Controller
{
    public function show(?string $path = null): Response
    {
        $path = trim((string) $path, '/');
        $path = str_replace('\\', '/', $path);

        if ($path !== '' && (str_contains($path, '..') || str_contains($path, "\0"))) {
            abort(404);
        }

        $slug = $path === '' ? 'home' : str_replace('/', '-', $path);
        $file = storage_path('app/live-html/'.$slug.'.html');

        if (! is_file($file)) {
            abort(404);
        }

        $base = str_replace('\\', '/', realpath(storage_path('app/live-html')) ?: '');
        $real = str_replace('\\', '/', realpath($file) ?: '');

        if ($base === '' || $real === '' || ! str_starts_with($real, $base)) {
            abort(404);
        }

        $html = file_get_contents($real) ?: '';

        $injectHead = '';
        // Site-wide: align sections with header left/right gutters
        if (! str_contains($html, 'site-align.css')) {
            $injectHead .= '<link rel="stylesheet" href="/css/site-align.css" />';
        }
        // Home only: richer UI polish (content unchanged)
        if ($slug === 'home' && ! str_contains($html, 'home-polish.css')) {
            $injectHead .= '<link rel="stylesheet" href="/css/home-polish.css" />';
        }
        if ($injectHead !== '') {
            $html = preg_replace('/<\/head>/i', $injectHead.'</head>', $html, 1) ?? $html;
        }
        if ($slug === 'home' && ! str_contains($html, 'home-polish.js')) {
            $html = preg_replace(
                '/<\/body>/i',
                '<script src="/js/home-polish.js" defer></script></body>',
                $html,
                1
            ) ?? $html;
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
