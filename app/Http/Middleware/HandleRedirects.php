<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use App\Support\ContentVersion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Old addresses from the redirects table (save_redirect, or a changed slug) go to their new page.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $map = Cache::rememberForever('redirects:'.ContentVersion::get(), fn () => Schema::hasTable('redirects')
            ? Redirect::get(['id', 'from_path', 'to_path', 'status_code'])->keyBy('from_path')->map->only(['id', 'to_path', 'status_code'])->all()
            : []);

        $path = Redirect::normalize($request->getPathInfo());
        if (! isset($map[$path])) {
            return $next($request);
        }

        $rule = $map[$path];
        Redirect::whereKey($rule['id'])->update(['hits' => \DB::raw('hits + 1'), 'last_hit_at' => now()]);
        $to = $rule['to_path'];
        $target = preg_match('#^https?://#', $to) ? $to : url($to).(str_ends_with($to, '/') && $to !== '/' ? '/' : '');
        $query = $request->getQueryString();

        return redirect()->away($target.($query ? (str_contains($target, '?') ? '&' : '?').$query : ''), $rule['status_code']);
    }
}
