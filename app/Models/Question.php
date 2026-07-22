<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', // <-- FONTOS: benne kell lennie a fillable tömbben!
        'category_id',
        'creator_id',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
