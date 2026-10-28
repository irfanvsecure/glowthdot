<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $guarded = ['id'];

    protected $casts = ['sizes' => 'array'];

    /** Every file of this image: the original, its resized copies and the WebP copy */
    public function paths(): array
    {
        return array_values(array_unique(array_filter(array_merge(
            [$this->path, $this->webp_path],
            array_column($this->sizes ?? [], 'path')
        ))));
    }

    /** The smallest copy at least $width wide, or the original */
    public function sized(int $width): array
    {
        $best = ['path' => $this->path, 'width' => $this->width, 'height' => $this->height];
        foreach ($this->sizes ?? [] as $size) {
            if ($size['width'] >= $width && $size['width'] < ($best['width'] ?? PHP_INT_MAX)) {
                $best = $size;
            }
        }

        return $best;
    }

    /** srcset value: the given copy first, then the others */
    public function srcset(?string $first = null): string
    {
        $all = array_merge([['path' => $this->path, 'width' => $this->width]], $this->sizes ?? []);
        usort($all, fn ($a, $b) => ($b['path'] === $first) <=> ($a['path'] === $first));

        return implode(', ', array_map(fn ($s) => $s['path'].' '.$s['width'].'w', $all));
    }
}
