<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class Question extends Model
{
    protected $fillable = [
        'category_id',
        'difficulty',
        'question_text',
        'image_path',
        'is_approved',
        'is_active',
        'creator_id',
    ];

    protected $casts = [
        'question_text' => 'array',
        'is_approved'   => 'boolean',
        'is_active'     => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function getTranslatedTextAttribute(): string
    {
        $locale = App::getLocale();

        if (is_array($this->question_text)) {
            return $this->question_text[$locale] ?? $this->question_text['hu'] ?? reset($this->question_text);
        }

        return $this->question_text ?? '';
    }
}
