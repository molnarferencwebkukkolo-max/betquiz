<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_banned', 'is_active', 'points', 'time_travel_theme'])]
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

    /**
     * Beállítás hiányában megőrizzük a jelenlegi viselkedést:
     * belső értesítés igen, e-mail csak kifejezett engedéllyel.
     */
    public function wantsNotification(string $event, string $channel): bool
    {
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
