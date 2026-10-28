<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeoMeta extends Model
{
    use SoftDeletes;

    protected $table = 'seo_meta';

    protected $guarded = ['id'];

    public const FIELDS = ['title', 'description', 'canonical', 'robots', 'og_type', 'og_title', 'og_description', 'og_url',
        'og_image', 'og_image_width', 'og_image_height', 'og_image_type', 'twitter_card', 'published_time', 'modified_time',
        'author', 'prev', 'next'];

    public function seoable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
