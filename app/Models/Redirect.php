<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Redirect extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = ['last_hit_at' => 'datetime'];

    /** "/old-page/" for "old-page", "/old-page" or "https://glowthdot.com/old-page/" */
    public static function normalize(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        $path = '/'.trim($path, '/');

        return $path === '/' ? '/' : $path.'/';
    }
}
