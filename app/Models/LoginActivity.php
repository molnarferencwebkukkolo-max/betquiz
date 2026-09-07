<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class LoginActivity extends Model
{
    protected $fillable = ['user_id', 'method', 'ip_address', 'user_agent', 'logged_in_at'];

    protected function casts(): array
    {
        return ['logged_in_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** A sikeres belépés technikai adatait rövid, adminisztrációs naplóba írja. */
    public static function record(User $user, Request $request, string $method): void
    {
        static::create([
            'user_id' => $user->id,
            'method' => $method,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'logged_in_at' => now(),
        ]);
    }
}
