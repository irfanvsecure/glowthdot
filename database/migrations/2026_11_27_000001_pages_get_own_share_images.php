<?php

use App\Models\Category;
use App\Models\Page;
use App\Services\OgCards;
use App\Support\ContentVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pages and category archives are og:type "website" (posts stay "article"), and every page that
 * shared the one site photo as og:image gets a share card of its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('seo_meta')->whereIn('seoable_type', [Page::class, Category::class])
            ->update(['og_type' => 'website', 'published_time' => null, 'modified_time' => null]);

        app(OgCards::class)->refreshAll();
        ContentVersion::bump();
    }

    public function down(): void
    {
        // the cards stay; og:type "website" is right for these pages either way
    }
};
