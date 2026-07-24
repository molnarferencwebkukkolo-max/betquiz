<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; //

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'category_id',
        'title',
        'description',
        'status',
        'rejection_reason',
    ];


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
