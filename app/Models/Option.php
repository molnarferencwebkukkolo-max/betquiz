<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    /**
     * A tömegesen tömöríthető (fillable) mezők listája.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question_id',
        'option_text',
        'image_path',
        'is_correct'
    ];

    /**
     * Az adatbázis attribútumok típuskényszerítése (Casting).
     */
    protected $casts = [
        'is_correct' => 'boolean',
    ];

    /**
     * Kapcsolat: A válaszlehetőség egy konkrét kérdéshez (Question) tartozik.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
