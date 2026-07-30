<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quiz_id', // <-- FONTOS: benne kell lennie a fillable tömbben!
        'category_id',
        'difficulty',
        'question_text',
        'image_path',
        'is_approved',
        'is_active',
        'times_answered', // <-- ÚJ
        'times_correct',  // <-- ÚJ
    ];


    /**
     * Helyes válaszok aránya százalékban
     */
    public function successRate(): int
    {
        if ($this->times_answered === 0) {
            return 0;
        }

        return round(($this->times_correct / $this->times_answered) * 100);
    }

    public function rebalanceDifficultyIfNeeded(): bool
    {
        if ($this->times_answered < 100) {
            return false;
        }

        $difficultyLevels = ['easy', 'medium', 'hard'];
        $currentIndex = array_search($this->difficulty, $difficultyLevels, true);

        if ($currentIndex === false) {
            return false;
        }

        $newIndex = $currentIndex;
        $successRate = $this->successRate();

        if ($successRate > 80) {
            $newIndex = max(0, $currentIndex - 1);
        } elseif ($successRate < 20) {
            $newIndex = min(count($difficultyLevels) - 1, $currentIndex + 1);
        }

        if ($newIndex === $currentIndex) {
            return false;
        }

        $this->forceFill([
            'difficulty' => $difficultyLevels[$newIndex],
            'times_answered' => 0,
            'times_correct' => 0,
        ])->save();

        return true;
    }

    protected $casts = [
        'question_text' => 'array',
    ];

    // 🎯 Ez a kapcsolat köti össze a Kérdést a Kvízzel:
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

}
