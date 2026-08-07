<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jelszó megerősítése - KwizzGo</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="auth-wrapper">
<div class="auth-card">
    <h1 class="auth-title">Jelszó megerősítése</h1>
    <p class="auth-subtitle">Ez egy védett művelet. A folytatáshoz add meg ismét a jelszavadat.</p>
    @error('password')<div class="auth-error-summary" role="alert">{{ $message }}</div>@enderror
    <form method="POST" action="{{ url('/confirm-password') }}">
        @csrf
        <div class="form-group-lg">
            <label for="password" class="form-label">Jelszó</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn-auth-submit auth-submit-full" type="submit">Megerősítés</button>
    </form>
</div>
</body>
</html>
