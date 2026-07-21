<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class Category extends Model
{
    /**
     * A tömegesen módosítható mezők.
     */
    protected $fillable = ['name', 'slug', 'icon', 'is_active'];

    /**
     * Típuskényszerítés: A 'name' tömböt automatikusan JSON-ná alakítja elmentéskor!
     */
    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Dinamikus nyelvi válasz a névhez ($category->translated_name)
     */
    public function getTranslatedNameAttribute(): string
    {
        $locale = App::getLocale();
        return $this->name[$locale] ?? $this->name['hu'] ?? '';
    }

    /**
     * Kapcsolat: Egy kategóriához sok kérdés tartozik.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
