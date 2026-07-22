<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Kérdések reláció
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Dinamikus többnyelvű név (getter)
     */
    public function getTranslatedNameAttribute(): string
    {
        $locale = App::getLocale();

        if (is_array($this->name)) {
            return $this->name[$locale] ?? $this->name['hu'] ?? reset($this->name);
        }

        return $this->name ?? '';
    }
}
