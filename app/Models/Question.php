<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /**
     * A tömegesen tömöríthető (fillable) mezők listája.
     * Ezeket a mezőket engedi a Laravel biztonságosan elmenteni kérésből.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'difficulty',
        'question_text',
        'image_path',
        'creator_id',
        'is_approved'
    ];

    /**
     * Az adatbázis attribútumok típuskényszerítése (Casting).
     */
    protected $casts = [
        'is_approved' => 'boolean',
    ];

    /**
     * Kapcsolat: Egy kérdéshez sok válaszlehetőség (Option) tartozik.
     * A játékmenet során ebből választunk majd ki 1 jót és 3 rosszat.
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    /**
     * Kapcsolat: A kérdés egy konkrét témakörhöz (Category) tartozik.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Kapcsolat: Ki a kérdés szerzője (User)?
     * Ha null, akkor a rendszer (Hostadmin) generálta,
     * ha van ID, akkor egy játékos küldte be a GamerAdmin felületről.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
