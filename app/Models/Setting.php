<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    protected $casts = [
        'description' => 'array', // A Laravel automatikusan tömbbé alakítja a JSON-t
    ];

    /**
     * Dinamikusan adja vissza a leírást az aktuális nyelv alapján.
     * Használata a Blade-ben: $setting->translated_description
     */
    public function getTranslatedDescriptionAttribute(): string
    {
        $locale = App::getLocale(); // Lekéri az aktuális nyelvet (pl. 'hu' vagy 'en')

        return $this->description[$locale] ?? $this->description['hu'] ?? '';
    }

    /**
     * Központi lekérő helper
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
