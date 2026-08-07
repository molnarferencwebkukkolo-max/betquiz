<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elfelejtett jelszó - BetQuiz</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="auth-wrapper">
<div class="auth-card">
    <h1 class="auth-title">Jelszó visszaállítása</h1>
    <p class="auth-subtitle">Add meg az e-mail-címedet, és elküldjük a biztonságos visszaállító hivatkozást.</p>

    @if (session('status'))
        <div class="auth-success-message" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="auth-error-summary" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group-lg">
            <label for="email" class="form-label">E-mail-cím</label>
            <input id="email" class="form-input @error('email') form-input-invalid @enderror"
                   type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>
        <div class="auth-actions">
            <a class="auth-link" href="{{ route('login') }}">← Vissza a belépéshez</a>
            <button class="btn-auth-submit" type="submit">Visszaállító link kérése</button>
        </div>
    </form>
</div>
</body>
</html>
