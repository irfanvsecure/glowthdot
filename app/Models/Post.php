<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasSeo, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['assets' => 'array', 'published_at' => 'datetime', 'modified_at' => 'datetime'];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->orderBy('categories.name');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** Published and due: what visitors see. A published post with a future date is scheduled. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function isLive(): bool
    {
        return $this->status === 'published' && $this->published_at && $this->published_at->lte(now());
    }

    public function url(): string
    {
        return '/'.$this->slug.'/';
    }

    /** The number in the post's CSS classes and anchors (entry-1310) */
    public function entryId(): int
    {
        return $this->legacy_id ?: 100000 + $this->id;
    }

    public function image(): ?Media
    {
        return $this->featured_image ? Media::where('path', $this->featured_image)->first() : null;
    }
}
