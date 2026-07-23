<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Regisztráció</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="auth-wrapper">

<div class="auth-card">
    <h2 class="auth-title">🎯 BetQuiz</h2>
    <p class="auth-subtitle-bonus">🎁 Regisztrációért 1 000 PT kezdőtőke jár!</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="form-group">
            <label for="name" class="form-label">Felhasználónév</label>
            <input id="name" class="form-input" type="text" name="name" value="{{ old('name') }}" required autofocus />
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
