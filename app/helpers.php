<?php

use App\Support\Settings;

if (! function_exists('versioned')) {
    /**
     * A public file's URL with its modified time, so the long browser cache in public/.htaccess
     * never serves an old copy: versioned('/js/site.js') -> /js/site.js?v=1793108300
     */
    function versioned(string $path): string
    {
        $mtime = @filemtime(public_path($path));

        return $mtime ? $path.'?v='.$mtime : $path;
    }
}

if (! function_exists('setting')) {
    /**
     * A site setting (contact details, menu, footer ...): see App\Support\Settings::KEYS
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}
