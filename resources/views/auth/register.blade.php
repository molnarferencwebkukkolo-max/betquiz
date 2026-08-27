<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KwizzGo - Regisztráció</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    @if(config('recaptcha.enabled') && config('recaptcha.site_key'))
        <script src="https://www.google.com/recaptcha/api.js?render={{ urlencode(config('recaptcha.site_key')) }}&hl=hu" defer></script>
    @endif
</head>
<body class="auth-wrapper auth-dark-page">

<div class="auth-card auth-brand-card">
    <h2 class="auth-title">🎯 KwizzGo</h2>
    <p class="auth-subtitle-bonus">🎁 Regisztrációért 1 000 PT kezdőtőke jár!</p>

    <form method="POST" action="{{ route('register') }}" data-recaptcha-v3-form data-recaptcha-action="register">
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

        <div class="auth-legal-consents">
            <label><input type="checkbox" name="accept_terms" value="1" @checked(old('accept_terms')) required><span>Elolvastam és elfogadom az <a href="{{ route('content.aszf') }}" target="_blank" rel="noopener">Általános Szerződési Feltételeket</a>.</span></label>
            @error('accept_terms') <span class="form-error">{{ $message }}</span> @enderror
            <label><input type="checkbox" name="accept_privacy" value="1" @checked(old('accept_privacy')) required><span>Elolvastam és elfogadom az <a href="{{ route('content.privacy') }}" target="_blank" rel="noopener">Adatkezelési szabályzatot</a>.</span></label>
            @error('accept_privacy') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <button type="submit" formaction="{{ route('auth.google.register') }}" formmethod="POST" formnovalidate class="btn-auth-submit auth-google-register">
            Regisztráció Google-lel
        </button>

        <div class="auth-divider"><span></span><b>vagy e-maillel</b><span></span></div>

        @if(config('recaptcha.enabled') && config('recaptcha.site_key'))
            <input type="hidden" name="g-recaptcha-response" value="">
            @error('g-recaptcha-response')
                <p class="form-error auth-recaptcha-error">{{ $message }}</p>
            @enderror
        @endif

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

<x-cookie-consent />

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
