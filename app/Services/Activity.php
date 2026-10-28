<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Who changed what, with the values before and after (activity_log tool).
 */
class Activity
{
    public static string $actor = 'system';

    public static function log(string $action, ?Model $subject = null, ?array $before = null, ?array $after = null, ?string $label = null): void
    {
        $strip = fn (?array $values) => $values === null ? null : array_map(
            fn ($v) => is_string($v) && strlen($v) > 4000 ? substr($v, 0, 4000).'… ['.strlen($v).' bytes]' : $v,
            array_diff_key($values, array_flip(['created_at', 'updated_at']))
        );

        ActivityLog::create([
            'actor' => self::$actor,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $label ?? ($subject ? ($subject->slug ?? $subject->key ?? $subject->path ?? $subject->from_path ?? null) : null),
            'before' => $strip($before),
            'after' => $strip($after),
            'ip' => request()?->ip(),
        ]);
    }
}
