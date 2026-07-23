<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Bejelentkezés</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="auth-wrapper">

<div class="auth-card">
    <h2 class="auth-title">🎯 BetQuiz</h2>
    <p class="auth-subtitle">Jelentkezz be a játék folytatásához!</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="form-group">
            <label for="email" class="form-label">E-mail cím</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus />
        </div>

        <!-- Password -->
        <div class="form-group-lg">
            <label for="password" class="form-label">Jelszó</label>
            <input id="password" class="form-input" type="password" name="password" required />
        </div>

        <div class="auth-actions">
            <a class="auth-link" href="{{ route('register') }}">
                Regisztráció ➔
            </a>

            <button type="submit" class="btn-auth-submit">
                Bejelentkezés
            </button>
        </div>
    </form>
</div>

</body>
</html>
