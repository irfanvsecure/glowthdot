<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SectionRevision extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['content' => 'array', 'is_visible' => 'boolean'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(PageSection::class, 'page_section_id')->withTrashed();
    }
}
