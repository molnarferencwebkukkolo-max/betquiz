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
     * Dinamikus többnyelvű név (getter: $category->translated_name)
     */
    public function getTranslatedNameAttribute(): string
    {
        $locale = App::getLocale();
        $names = $this->name;

        // Ha sztringként maradt volna a DB-ben
        if (is_string($names)) {
            $decoded = json_decode($names, true);
            $names = is_array($decoded) ? $decoded : ['hu' => $names];
        }

        if (is_array($names) && !empty($names)) {
            return $names[$locale] ?? $names['hu'] ?? (string) reset($names);
        }

        return 'Általános';
    }
}
