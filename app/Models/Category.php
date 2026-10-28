<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasSeo, SoftDeletes;

    public const PER_PAGE = 10;

    protected $guarded = ['id'];

    protected $casts = ['assets' => 'array'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function url(int $page = 1): string
    {
        return '/category/'.$this->slug.'/'.($page > 1 ? 'page/'.$page.'/' : '');
    }
}
