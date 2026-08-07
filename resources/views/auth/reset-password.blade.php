<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Új jelszó - BetQuiz</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="auth-wrapper">
<div class="auth-card">
    <h1 class="auth-title">Új jelszó beállítása</h1>
    <p class="auth-subtitle">Adj meg egy új, biztonságos jelszót a fiókodhoz.</p>

    @if ($errors->any())
        <div class="auth-error-summary" role="alert">
            <ul>
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="form-group">
            <label for="email" class="form-label">E-mail-cím</label>
            <input id="email" class="form-input" type="email" name="email"
                   value="{{ old('email', $request->email) }}" required autocomplete="email">
        </div>
        <div class="form-group">
            <label for="password" class="form-label">Új jelszó</label>
            <input id="password" class="form-input" type="password" name="password" required autocomplete="new-password">
        </div>
        <div class="form-group-lg">
            <label for="password_confirmation" class="form-label">Új jelszó megerősítése</label>
            <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button class="btn-auth-submit auth-submit-full" type="submit">Jelszó mentése</button>
    </form>
</div>
</body>
</html>
