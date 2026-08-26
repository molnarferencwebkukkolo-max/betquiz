<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Advertisement extends Model
{
    protected $fillable = [
        'created_by', 'name', 'type', 'image_path', 'target_url', 'alt_text',
        'adsense_code', 'weight', 'is_active', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'weight' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function placements(): BelongsToMany
    {
        return $this->belongsToMany(AdPlacement::class, 'advertisement_placement');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'advertisement_category');
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'advertisement_quiz');
    }

    /**
     * Célzás nélkül a hirdetés globális. Célzás esetén csak akkor
     * jogosult, ha a jelenlegi kategória VAGY konkrét kvíz ki van jelölve.
     */
    public function scopeForContext(Builder $query, ?int $categoryId, ?int $quizId): Builder
    {
        return $query->where(function (Builder $targeting) use ($categoryId, $quizId) {
            $targeting->where(function (Builder $global) {
                $global->whereDoesntHave('categories')->whereDoesntHave('quizzes');
            });

            if ($categoryId) {
                $targeting->orWhereHas('categories', fn (Builder $categories) => $categories->whereKey($categoryId));
            }

            if ($quizId) {
                $targeting->orWhereHas('quizzes', fn (Builder $quizzes) => $quizzes->whereKey($quizId));
            }
        });
    }

    /** Csak a jelen pillanatban ténylegesen kiszolgálható kreatívokat tartjuk meg. */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
