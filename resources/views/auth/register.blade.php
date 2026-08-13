<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KwizzGo - Regisztráció</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="auth-wrapper auth-dark-page">

<div class="auth-card auth-brand-card">
    <h2 class="auth-title">🎯 KwizzGo</h2>
    <p class="auth-subtitle-bonus">🎁 Regisztrációért 1 000 PT kezdőtőke jár!</p>

    <a href="{{ route('auth.google.redirect') }}" class="btn-auth-submit" style="display: block; box-sizing: border-box; margin-bottom: 1.25rem; text-align: center; text-decoration: none; background: #fff; color: #334155; border: 1px solid #cbd5e1;">
        Regisztráció Google-lel
    </a>

    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; color: #94a3b8; font-size: 0.8rem;">
        <span style="height: 1px; flex: 1; background: #e2e8f0;"></span><span>vagy e-maillel</span><span style="height: 1px; flex: 1; background: #e2e8f0;"></span>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="form-group">
            <label for="username" class="form-label">Felhasználónév</label>
            <input id="username" class="form-input" type="text" name="username" value="{{ old('username') }}" minlength="3" maxlength="30" required autofocus autocomplete="username" />
            @error('username') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <!-- Email Address -->
        <div class="form-group">
            <label for="email" class="form-label">E-mail cím</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required />
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="form-label">Jelszó</label>
            <input id="password" class="form-input" type="password" name="password" required />
        </div>

        <!-- Confirm Password -->
        <div class="form-group-lg">
            <label for="password_confirmation" class="form-label">Jelszó megerősítése</label>
            <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required />
        </div>

        <div class="auth-actions">
            <a class="auth-link" href="{{ route('login') }}">
                Már van fiókom ➔
            </a>

            <button type="submit" class="btn-auth-submit">
                Regisztráció
            </button>
        </div>
    </form>
</div>

</body>
</html>
