<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * A number that changes on every content write. Cached pages, settings and sitemaps are keyed
 * by it, so one bump makes all of them fresh without clearing the rest of the cache.
 */
class ContentVersion
{
    private const KEY = 'content-version';

    public static function get(): string
    {
        return (string) Cache::rememberForever(self::KEY, fn () => (string) microtime(true));
    }

    public static function bump(): void
    {
        Cache::forever(self::KEY, (string) microtime(true));
        Settings::forget();
    }
}
