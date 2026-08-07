<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'KwizzGo') }}</title>

    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="welcome-wrapper">

<!-- Fejléc / Navigáció -->
<header class="welcome-nav">
    <a href="{{ url('/') }}" class="nav-brand">
        🎲 KwizzGo
    </a>

    @if (Route::has('login'))
        <nav style="display: flex; gap: 0.75rem; align-items: center;">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary-purple" style="text-decoration: none; font-size: 0.875rem;">
                    Vezérlőpult ➔
                </a>
            @else
                <a href="{{ route('login') }}" class="nav-link-item">
                    Bejelentkezés
                </a>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn-primary-purple" style="text-decoration: none; font-size: 0.875rem;">
                        Regisztráció
                    </a>
                @endif
            @endauth
        </nav>
    @endif
</header>

<!-- Fő Bemutató Szekció (Hero) -->
<main class="welcome-hero-container">
    <div class="welcome-hero-card">
        <div style="font-size: 4rem; margin-bottom: 1rem;">🎯</div>
        <h1 class="welcome-title">Teszteld a tudásod, tegyél tétet és nyerj!</h1>
        <p class="welcome-subtitle">
            A KwizzGo egy interaktív kvízplatform, ahol nemcsak a tudásodat teheted próbára, de zsetonokat kockáztatva versenyezhetsz a legmagasabb pontszámokért.
        </p>

        <div class="welcome-btn-group">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-hero-primary">
                    Irány a Műszerfal 🚀
                </a>
            @else
                <a href="{{ route('register') }}" class="btn-hero-primary">
                    Kezdd el most (1 000 PT Bónusz) 🎁
                </a>
                <a href="{{ route('login') }}" class="btn-hero-secondary">
                    Már van fiókom
                </a>
            @endauth
        </div>
    </div>
</main>

<!-- Lábléc -->
<footer class="welcome-footer">
    &copy; {{ date('Y') }} KwizzGo. Minden jog fenntartva.
</footer>

</body>
</html>
