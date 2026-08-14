<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KwizzGo - Bejelentkezés</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    @if(config('recaptcha.enabled') && config('recaptcha.site_key'))
        <script src="https://www.google.com/recaptcha/api.js?render={{ urlencode(config('recaptcha.site_key')) }}&hl=hu" defer></script>
    @endif
</head>
<body class="auth-wrapper auth-dark-page">

<div class="auth-card auth-brand-card">
    <h2 class="auth-title">🎯 KwizzGo</h2>
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

    <a href="{{ route('auth.google.redirect') }}" class="btn-auth-submit" style="display: block; box-sizing: border-box; margin-bottom: 1.25rem; text-align: center; text-decoration: none; background: #fff; color: #334155; border: 1px solid #cbd5e1;">
        Bejelentkezés Google-lel
    </a>

    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; color: #94a3b8; font-size: 0.8rem;">
        <span style="height: 1px; flex: 1; background: #e2e8f0;"></span><span>vagy e-maillel</span><span style="height: 1px; flex: 1; background: #e2e8f0;"></span>
    </div>

    <form method="POST" action="{{ route('login') }}" data-recaptcha-v3-form data-recaptcha-action="login">
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

        @if(config('recaptcha.enabled') && config('recaptcha.site_key'))
            <input type="hidden" name="g-recaptcha-response" value="">
            @error('g-recaptcha-response')
                <p class="form-error auth-recaptcha-error">{{ $message }}</p>
            @enderror
        @endif

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

@if(config('recaptcha.enabled') && config('recaptcha.site_key'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-recaptcha-v3-form]');
            let verifiedSubmission = false;

            form?.addEventListener('submit', (event) => {
                if (verifiedSubmission) return;

                event.preventDefault();
                grecaptcha.ready(() => {
                    grecaptcha.execute(@json(config('recaptcha.site_key')), { action: form.dataset.recaptchaAction })
                        .then((token) => {
                            form.elements['g-recaptcha-response'].value = token;
                            verifiedSubmission = true;
                            form.requestSubmit();
                        });
                });
            });
        });
    </script>
@endif

</body>
</html>
