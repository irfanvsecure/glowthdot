<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'activity_log';

    protected $guarded = ['id'];

    protected $casts = ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
}
