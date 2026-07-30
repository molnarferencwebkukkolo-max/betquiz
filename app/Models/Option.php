<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;

class Option extends Model
{
    protected $fillable = [
        'question_id',
        'option_text',
        'image_path',
        'is_correct',
    ];

    protected $casts = [
        'option_text' => 'array',  // <-- EZ HIÁNYZOTT! Emiatt alakítja át automatikusan JSON-né
        'is_correct'  => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Dinamikus többnyelvű válaszminta (getter)
     */
    public function getTranslatedTextAttribute(): string
    {
        $locale = App::getLocale();

        if (is_array($this->option_text)) {
            return $this->option_text[$locale] ?? $this->option_text['hu'] ?? reset($this->option_text);
        }

        return $this->option_text ?? '';
    }
}
