<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; //
use Illuminate\Support\Str;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'category_id',
        'title',
        'slug',
        'seo_title',
        'seo_description',
        'description',
        'cover_image',
        'status',
        'is_public',
        'rejection_reason',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Quiz $quiz) {
            if ($quiz->isDirty('title') || empty($quiz->slug)) {
                $quiz->slug = static::makeUniqueSlug($quiz->title, $quiz->id);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('slug', $value)
            ->when(is_numeric($value), fn($query) => $query->orWhere('id', (int) $value))
            ->firstOrFail();
    }

    private static function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title) ?: 'kviz';
        $slug = $baseSlug;
        $counter = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function getEffectiveSeoTitleAttribute(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function getEffectiveSeoDescriptionAttribute(): string
    {
        $description = trim((string) $this->description);

        return $this->seo_description ?: Str::limit(strip_tags($description), 160, '');
    }

    public function totalAnswersCount(): int
    {
        return (int) ($this->total_answers ?? $this->questions()->sum('times_answered'));
    }

    public function correctAnswersCount(): int
    {
        return (int) ($this->total_correct ?? $this->questions()->sum('times_correct'));
    }

    // app/Models/Quiz.php

    public function canBePublished(): bool
    {
        // Akkor publikálható, ha az admin már jóváhagyta a témát ÉS megvan a legalább 100 kérdés
        return $this->status === 'draft_approved' && $this->questions()->count() >= 100;
    }

    public function questionsCount(): int
    {
        return $this->questions()->count();
    }

    public function progressPercentage(): int
    {
        // Hány százaléknál jár a 100 kérdéses célhoz képest
        return min(100, round(($this->questionsCount() / 100) * 100));
    }

    public function getLevelAttribute(): int
    {
        // Aktív kérdések száma (ha a count fel van töltve withCount-tal)
        $count = $this->questions_count ?? $this->questions()->count();

        if ($count < 100) {
            return 0; // Még nem érte el a publikálási limitet
        }

        return (int) floor($count / 500) + 1;
    }

    /**
     * Százas mérföldkő plecsni (100+, 200+, 300+, stb.)
     */
    public function getBadgeAttribute(): string
    {
        $count = $this->questions_count ?? $this->questions()->count();

        if ($count < 100) {
            return '⏳ Gyűjtés alatt';
        }

        // Kerekítés lefelé a legközelebbi 100-asra (pl. 240 -> 200+)
        $milestone = (int) floor($count / 100) * 100;

        return $milestone . '+ Kérdés';
    }

}
