<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Site-wide settings (contact details, menu, footer, robots.txt ...), read once per content version.
 */
class Settings
{
    /** The settings the site needs, with what each one holds */
    public const KEYS = [
        'site_name' => 'Site name',
        'phone' => 'Phone number, as dialled: +971588979925',
        'whatsapp' => 'WhatsApp number, digits only: 971588979925',
        'email' => 'Contact email',
        'address' => 'Office address',
        'social_links' => 'List of {"label", "url"}',
        'topbar_html' => 'HTML of the strip above the header',
        'header_cta' => 'Header button: {"label", "url"}',
        'whatsapp_button_text' => 'Text on the floating WhatsApp button',
        'nav_menu' => 'Header menu: list of {"label", "url", "children"?, "class"?, "mobile"?: false}',
        'footer_tagline' => 'Line under the footer logo',
        'footer_columns' => 'Footer link columns: list of {"title", "links": [{"label", "url"}]}',
        'copyright' => 'Footer copyright line',
        'google_site_verification' => 'Google Search Console verification code',
        'robots_default' => 'robots meta of every indexable page',
        'robots_txt' => 'Contents of /robots.txt',
        'og_locale' => 'og:locale',
        'twitter_card' => 'Default twitter:card',
        'default_author' => 'Author of new blog posts',
    ];

    private static ?array $values = null;

    public static function all(): array
    {
        if (self::$values !== null) {
            return self::$values;
        }
        if (! Schema::hasTable('settings')) {
            return self::$values = [];
        }

        return self::$values = Cache::rememberForever('settings:'.ContentVersion::get(),
            fn () => Setting::pluck('value', 'key')->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::withTrashed()->updateOrCreate(['key' => $key], ['value' => $value, 'deleted_at' => null]);
        self::forget();
    }

    public static function forget(): void
    {
        self::$values = null;
    }
}
