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

    @if (session('status'))
        <div class="auth-success-message" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="auth-error-summary" role="alert" aria-live="polite">
            <strong>A bejelentkezés nem sikerült.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="form-group">
            <label for="email" class="form-label">E-mail cím</label>
            <input id="email" class="form-input @error('email') form-input-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror />
            @error('email')
                <p id="email-error" class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-group-lg">
            <label for="password" class="form-label">Jelszó</label>
            <input id="password" class="form-input @error('password') form-input-invalid @enderror" type="password" name="password" required autocomplete="current-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror />
            @error('password')
                <p id="password-error" class="form-error">{{ $message }}</p>
            @enderror
            <a class="auth-forgot-link" href="{{ route('password.request') }}">
                Elfelejtetted a jelszavad?
            </a>
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
