<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmergencyAdminAuthenticator
{
    /**
     * Az ENV-ben tárolt vészhelyzeti azonosítót ellenőrzi. A felhasználói
     * rekordot csak sikeres hitelesítés után hozza létre/helyre, és abban
     * szándékosan nem tárolja el a vészhelyzeti jelszó hashét.
     */
    public function attempt(string $email, string $password): ?User
    {
        if (! config('emergency_admin.enabled')) {
            return null;
        }

        $configuredEmail = mb_strtolower(trim((string) config('emergency_admin.email')));
        $configuredHash = (string) config('emergency_admin.password_hash');

        if ($configuredEmail === '' || $configuredHash === '' || mb_strtolower(trim($email)) !== $configuredEmail) {
            return null;
        }

        if (! Hash::check($password, $configuredHash)) {
            return null;
        }

        $user = User::query()->firstOrNew(['email' => $configuredEmail]);
        if (! $user->exists) {
            // Ismeretlen, véletlen jelszó kerül az adatbázisba, így ez a fiók
            // kizárólag az ENV-ben lévő break-glass hitelesítővel használható.
            $user->password = Str::password(64);
            $user->points = 0;
        }

        $user->forceFill([
            // A rekordhoz kapcsolt kvízek és tartalmak egységesen a nyilvános
            // márkanév alatt jelennek meg minden szerzői felületen.
            'name' => 'KwizzGo',
            'username' => 'KwizzGo',
            'role' => 'hostadmin',
            'is_active' => true,
            'is_banned' => false,
            'is_ad_free' => true,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        return $user;
    }
}
