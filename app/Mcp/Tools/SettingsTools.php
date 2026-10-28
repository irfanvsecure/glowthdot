<?php

namespace App\Mcp\Tools;

use App\Mcp\SiteTool;
use App\Mcp\ToolError;
use App\Services\Activity;
use App\Services\HtmlSanitizer;
use App\Support\Settings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;

class SettingsTools
{
    /** Validation of each setting's value */
    private const RULES = [
        'site_name' => ['value' => 'required|string|max:100'],
        'phone' => ['value' => ['required', 'string', 'regex:/^\+?[0-9 ]{7,20}$/']],
        'whatsapp' => ['value' => ['required', 'string', 'regex:/^[0-9]{7,15}$/']],
        'email' => ['value' => 'required|email'],
        'address' => ['value' => 'required|string|max:300'],
        'social_links' => ['value' => 'present|array', 'value.*.label' => 'required|string|max:50', 'value.*.url' => 'required|url'],
        'topbar_html' => ['value' => 'present|string|max:1000'],
        'header_cta' => ['value' => 'required|array', 'value.label' => 'required|string|max:60', 'value.url' => 'required|string|max:300'],
        'whatsapp_button_text' => ['value' => 'required|string|max:60'],
        'nav_menu' => ['value' => 'required|array|min:1', 'value.*.label' => 'required|string|max:60', 'value.*.url' => 'required|string|max:300',
            'value.*.class' => 'nullable|string|max:60', 'value.*.mobile' => 'nullable|boolean',
            'value.*.children' => 'nullable|array', 'value.*.children.*.label' => 'required|string|max:80', 'value.*.children.*.url' => 'required|string|max:300'],
        'footer_tagline' => ['value' => 'required|string|max:300'],
        'footer_columns' => ['value' => 'required|array|min:1|max:6', 'value.*.title' => 'required|string|max:60', 'value.*.links' => 'required|array',
            'value.*.links.*.label' => 'required|string|max:80', 'value.*.links.*.url' => 'required|string|max:300', 'value.*._classes' => 'nullable|array'],
        'copyright' => ['value' => 'required|string|max:300'],
        'google_site_verification' => ['value' => 'present|nullable|string|max:200'],
        'robots_default' => ['value' => 'required|string|max:200'],
        'robots_txt' => ['value' => 'required|string|max:5000'],
        'og_locale' => ['value' => ['required', 'regex:/^[a-z]{2}_[A-Z]{2}$/']],
        'twitter_card' => ['value' => 'required|in:summary,summary_large_image'],
        'default_author' => ['value' => 'required|string|max:100'],
    ];

    /** @return list<SiteTool> */
    public static function tools(): array
    {
        return [
            new SiteTool('get_settings', 'The site-wide settings: contact details (phone, WhatsApp, email, address), social links, header top bar and button, header menu, footer tagline, link columns and copyright, robots defaults. Each with what it holds.',
                fn (JsonSchema $s) => ['key' => $s->string()->description('Only this setting')],
                ['key' => 'nullable|string'],
                function (array $in) {
                    $all = Settings::all();
                    $keys = isset($in['key']) ? [$in['key']] : array_keys(self::RULES);
                    $out = [];
                    foreach ($keys as $key) {
                        if (! isset(self::RULES[$key])) {
                            throw new ToolError("Unknown setting \"$key\". Known: ".implode(', ', array_keys(self::RULES)));
                        }
                        $out[$key] = ['value' => $all[$key] ?? null, 'about' => Settings::KEYS[$key] ?? 'Default author of new blog posts'];
                    }

                    return ['settings' => $out];
                }),

            new SiteTool('update_settings', 'Change one or more site settings: {"settings": {"phone": "+971...", "header_cta": {"label": "...", "url": "/..."}}}. Lists (nav_menu, footer_columns, social_links) are replaced whole: read them with get_settings first. The header, footer and every page change at once.',
                fn (JsonSchema $s) => ['settings' => $s->object()->required()->description('Setting key => new value')],
                ['settings' => 'required|array|min:1'],
                function (array $in) {
                    $changed = [];
                    foreach ($in['settings'] as $key => $value) {
                        if (! isset(self::RULES[$key])) {
                            throw new ToolError("Unknown setting \"$key\". Known: ".implode(', ', array_keys(self::RULES)));
                        }
                        $v = Validator::make(['value' => $value], self::RULES[$key]);
                        if ($v->fails()) {
                            throw new ToolError("$key: ".implode(' ', $v->errors()->all()));
                        }
                        if ($key === 'topbar_html') {
                            $value = app(HtmlSanitizer::class)->clean($value);
                        }
                        if ($key === 'footer_columns') {
                            // New columns take the first column's classes, so they look the same
                            $classes = Settings::get('footer_columns', [])[0]['_classes'] ?? null;
                            $value = array_map(fn ($c) => $c + ($classes ? ['_classes' => $classes] : []), $value);
                        }
                        $before = Settings::get($key);
                        Settings::set($key, $value);
                        Activity::log('settings.update', null, [$key => $before], [$key => $value], $key);
                        $changed[] = $key;
                    }

                    return ['updated' => $changed];
                }, writes: true),
        ];
    }
}
