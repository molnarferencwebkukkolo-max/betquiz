<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

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
}
