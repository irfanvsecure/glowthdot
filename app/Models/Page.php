<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasSeo, SoftDeletes;

    /** hidden: still served, but noindex, and links to it go to the free audit */
    public const STATUSES = ['published', 'draft', 'hidden'];

    /** sections: the page's own sections; blog_index: sections, one of them a posts_grid */
    public const TEMPLATES = ['sections', 'blog_index'];

    protected $guarded = ['id'];

    protected $casts = ['assets' => 'array'];

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function url(): string
    {
        return $this->path;
    }

    /** "/" for the home page, "/{slug}/" for the others */
    public static function pathFor(string $slug): string
    {
        return $slug === 'home' ? '/' : '/'.trim($slug, '/').'/';
    }
}
