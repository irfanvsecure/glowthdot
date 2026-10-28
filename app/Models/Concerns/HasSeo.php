<?php

namespace App\Models\Concerns;

use App\Models\SchemaBlock;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Pages, posts and categories: their SEO tags and JSON-LD blocks.
 */
trait HasSeo
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function schemaBlocks(): MorphMany
    {
        return $this->morphMany(SchemaBlock::class, 'schemable')->whereNull('variant')->orderBy('sort_order')->orderBy('id');
    }

    /** A category archive's JSON-LD for page 2 and on */
    public function pagedSchemaBlocks(): MorphMany
    {
        return $this->morphMany(SchemaBlock::class, 'schemable')->where('variant', 'paged')->orderBy('sort_order')->orderBy('id');
    }
}
