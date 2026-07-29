<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag) {
            if ($tag->isDirty('name') || empty($tag->slug)) {
                $tag->slug = static::makeUniqueSlug($tag->name, $tag->id);
            }
        });
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class)->withTimestamps();
    }

    private static function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'tag';
        $slug = $baseSlug;
        $counter = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
