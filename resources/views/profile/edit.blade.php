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
                A profiladataid sikeresen frissültek!
            </div>
        @elseif(session('status') === 'google-onboarding')
            <div class="alert-success-custom">
                Sikeres Google-belépés! Válassz egy egyedi felhasználónevet, majd mentsd el az alapadataidat.
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('patch')

            <div class="form-group">
                <label for="username" class="form-label">Felhasználónév</label>
                <input type="text" name="username" id="username" class="form-input" value="{{ old('username', $user->username) }}" minlength="3" maxlength="30" required autocomplete="username">
                <small style="font-size: 0.75rem; color: #6b7280; display: block; margin-top: 0.25rem;">Ez a név jelenik meg a KwizzGo felületein. 3–30 karakter; betű, szám, aláhúzás és kötőjel használható.</small>
                @error('username') <span style="font-size: 0.75rem; color: #dc2626;">{{ $message }}</span> @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email" class="form-label">E-mail cím</label>
                <input type="email" name="email" id="email" class="form-control-custom w-100" value="{{ old('email', $user->email) }}" required>
                @error('email') <span style="font-size: 0.75rem; color: #dc2626;">{{ $message }}</span> @enderror
            </div>

            <!-- Egyenleg megjelenítése -->
            <div class="balance-info-box">
                <span class="balance-info-label">Jelenlegi egyenleg:</span>
                <span class="balance-info-value">{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</span>
            </div>

            <button type="submit" class="btn-save-profile">Alapadatok mentése 💾</button>
        </form>

        <div style="margin-top: 2rem; border-top: 1px solid #e5e7eb; padding-top: 1.5rem;">
            <h3 class="password-card-title">🔑 Jelszó módosítása</h3>
            <form action="{{ route('profile.password') }}" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                @csrf
                @if(filled($user->getRawOriginal('password')))
                    <div><label class="form-label">Jelenlegi jelszó</label><input type="password" name="current_password" required class="form-input-profile">@error('current_password') <span class="form-error">{{ $message }}</span> @enderror</div>
                @else
                    <p style="font-size: 0.85rem; color: #64748b;">Google-fiókkal regisztráltál. Itt opcionálisan beállíthatsz egy jelszót az e-mailes belépéshez.</p>
                @endif
                <div><label class="form-label">Új jelszó</label><input type="password" name="password" required class="form-input-profile">@error('password') <span class="form-error">{{ $message }}</span> @enderror</div>
                <div><label class="form-label">Új jelszó megerősítése</label><input type="password" name="password_confirmation" required class="form-input-profile"></div>
                <button type="submit" class="btn-save-password">Jelszó mentése</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
