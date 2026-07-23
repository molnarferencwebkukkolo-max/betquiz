<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'points'])] // <-- ITT ADD HOZZÁ A 'points'-ot
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    /**
     * Ellenőrzi, hogy a felhasználó egy adott kérdést kezelhet-e.
     * (Hostadmin és Useradmin mindent kezelhet, a sima user csak a sajátját).
     */
    public function canManageQuestion($question): bool
    {
        if ($this->isUseradmin()) {
            return true;
        }

        return $question->creator_id === $this->id;
    }
}
