<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageSection extends Model
{
    use SoftDeletes;

    /** html: the section's own markup; posts_grid: markup with the latest posts at POSTS_MARKER */
    public const TYPES = ['html', 'posts_grid'];

    public const POSTS_MARKER = '<!--posts-->';

    protected $guarded = ['id'];

    protected $casts = ['content' => 'array', 'is_visible' => 'boolean'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class)->withTrashed();
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SectionRevision::class)->latest('id');
    }

    public function html(): string
    {
        return (string) ($this->content['html'] ?? '');
    }

    /** Keeps the current state, so it can be restored later */
    public function saveRevision(?string $note = null): SectionRevision
    {
        return $this->revisions()->create([
            'key' => $this->key,
            'type' => $this->type,
            'sort_order' => $this->sort_order,
            'content' => $this->content,
            'is_visible' => $this->is_visible,
            'note' => $note,
        ]);
    }
}
