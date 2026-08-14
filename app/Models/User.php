<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'google_id', 'email_verified_at', 'password', 'role', 'is_banned', 'is_active', 'is_ad_free', 'points', 'time_travel_theme', 'birth_date', 'gender', 'country', 'county', 'favorite_category_id', 'relationship_status', 'children_count', 'profile_details_rewarded_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Az adatbázis-defaultokat modelloldalon is tükrözzük, hogy egy frissen
     * létrehozott példány már az első refresh előtt is aktívnak számítson.
     */
    protected $attributes = [
        'is_banned' => false,
        'is_active' => true,
        'is_ad_free' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points' => 'integer', // <-- Ezt is beteheted, hogy a Laravel mindig számként kezelje
            'is_banned' => 'boolean',
            'is_active' => 'boolean',
            'is_ad_free' => 'boolean',
            'birth_date' => 'date',
            'children_count' => 'integer',
            'profile_details_rewarded_at' => 'datetime',
        ];
    }

    /**
     * Ellenőrzi, hogy a felhasználó Hostadmin-e.
     */
    public function isHostadmin(): bool
    {
        return $this->role === 'hostadmin';
    }

    /**
     * Ellenőrzi, hogy a felhasználó Useradmin (Moderátor) vagy Hostadmin-e.
     */
    public function isUseradmin(): bool
    {
        return in_array($this->role, ['hostadmin', 'useradmin']);
    }

    /** Az ENV-ből helyreállítható hostadmin azonosítása. */
    public function isEmergencyAdmin(): bool
    {
        $email = mb_strtolower(trim((string) config('emergency_admin.email')));

        return config('emergency_admin.enabled')
            && $email !== ''
            && hash_equals($email, mb_strtolower($this->email));
    }

    /** A reklámmentességet minden megjelenítési pont ugyaninnen ellenőrzi. */
    public function isAdFree(): bool
    {
        return $this->is_ad_free;
    }

    public function favorites()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_user_favorites')->withTimestamps();
    }

    public function dislikes()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_user_dislikes')->withTimestamps();
    }

    /**
     * A felhasználó által létrehozott kvízek.
     *
     * Az admin felhasználólistában ebből számoljuk a tartalmi aktivitást.
     */
    public function createdQuizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'creator_id');
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function favoriteCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'favorite_category_id');
    }

    /**
     * Beállítás hiányában megőrizzük a jelenlegi viselkedést:
     * belső értesítés igen, e-mail csak kifejezett engedéllyel.
     */
    public function wantsNotification(string $event, string $channel): bool
    {
        if (! NotificationPreference::supportsChannel($event, $channel)) {
            return false;
        }

        $preference = $this->notificationPreferences()
            ->where('event', $event)
            ->first();

        if (! $preference) {
            return $channel === 'database';
        }

        return match ($channel) {
            'database' => $preference->database_enabled,
            'mail' => $preference->email_enabled,
            default => false,
        };
    }
}
