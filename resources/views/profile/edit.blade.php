<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilom - KwizzGo</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body>

@include('layouts.navigation')

<div class="profile-edit-container">
    <div class="profile-edit-card">
        <div class="profile-edit-header">
            <h2 class="profile-edit-title">👤 Profil beállítások</h2>
        </div>

        @if (session('status') === 'profile-updated')
            <div class="alert-success-custom">
                A profiladataid és a felhasználói szinted sikeresen frissült!
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('patch')

            <!-- Név -->
            <div class="form-group">
                <label for="name" class="form-label">Név</label>
                <input type="text" name="name" id="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                @error('name') <span style="font-size: 0.75rem; color: #dc2626;">{{ $message }}</span> @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email" class="form-label">E-mail cím</label>
                <input type="email" name="email" id="email" class="form-control-custom w-100" value="{{ old('email', $user->email) }}" required>
                @error('email') <span style="font-size: 0.75rem; color: #dc2626;">{{ $message }}</span> @enderror
            </div>

            <!-- FELHASZNÁLÓI SZINT / SZEREPKÖR (User, Moderator, Admin) -->
            <div class="form-group">
                <label for="role" class="form-label" style="color: #d97706; font-weight: 700;">🔑 Felhasználói szint (Szerepkör)</label>
                <select name="role" id="role" class="form-select-custom form-select-role w-100">
                    <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>User (Játékos)</option>
                    <option value="moderator" {{ old('role', $user->role) === 'moderator' ? 'selected' : '' }}>Moderator (Moderátor)</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin (Adminisztrátor)</option>
                </select>
                <small style="font-size: 0.75rem; color: #6b7280; display: block; margin-top: 0.25rem;">Tesztelési céllal itt válthatsz a jogosultsági szintek között.</small>
                @error('role') <span style="font-size: 0.75rem; color: #dc2626;">{{ $message }}</span> @enderror
            </div>

            <!-- Egyenleg megjelenítése -->
            <div class="balance-info-box">
                <span class="balance-info-label">Jelenlegi egyenleg:</span>
                <span class="balance-info-value">{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</span>
            </div>

            <button type="submit" class="btn-save-profile">Mentés és Szintváltás 💾</button>
        </form>
    </div>
</div>

</body>
</html>
