<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchemaBlock extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function schemable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
