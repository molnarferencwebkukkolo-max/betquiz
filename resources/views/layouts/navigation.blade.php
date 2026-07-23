<nav class="main-navbar">
    <div class="main-navbar-container">
        <!-- Logo / Főoldal link -->
        <a href="{{ route('dashboard') }}" class="nav-brand">
            🎯 BetQuiz
        </a>

        <!-- Menüpontok csoportja -->
        <div class="nav-links-group">

            <!-- 1. Minden bejelentkezett felhasználó által látható menüpontok -->
            <a href="{{ route('quiz.bet') }}" class="nav-link-item {{ request()->routeIs('quiz.*') ? 'active' : '' }}">
                🎮 JÁTÉK
            </a>

            <a href="{{ route('quizzes.index') }}" class="nav-link-item {{ request()->routeIs('quizzes.*') ? 'active' : '' }}">
                📋 Kvízeim
            </a>

            <!-- 2. USERADMIN & HOSTADMIN által látható menüpontok (Kérdésbank) -->
            @if(auth()->check() && (auth()->user()->isUseradmin() || auth()->user()->isHostadmin()))
                <a href="{{ route('questions.index') }}" class="nav-link-item {{ request()->routeIs('questions.*') ? 'active' : '' }}" style="color: #d97706;">
                    ⚙️ Kérdésbank
                </a>
            @endif

            <!-- 3. KIZÁRÓLAG HOSTADMIN által látható menüpont (Felhasználók - Coming Soon) -->
            @if(auth()->check() && auth()->user()->isHostadmin())
                <a href="#" onclick="alert('Felhasználók kezelése: Coming Soon!'); return false;" class="nav-link-item" style="color: #dc2626; opacity: 0.8;" title="Hamarosan érkezik">
                    🛡️ Felhasználók <span style="font-size: 0.65rem; background-color: #ef4444; color: white; padding: 2px 6px; border-radius: 9999px; vertical-align: middle; margin-left: 2px;">SOON</span>
                </a>
            @endif

            <!-- 4. Profilom (Minden bejelentkezett felhasználónak) -->
            <a href="{{ route('profile.show') }}" class="nav-link-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                👤 Profilom
            </a>

            <!-- 5. Egyenleg megjelenítése -->
            <span class="nav-badge-points">
                🪙 {{ number_format(auth()->user()->points ?? 0, 0, ',', ' ') }} PT
            </span>

            <!-- 6. Kijelentkezési űrlap -->
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn-nav-logout">
                    Kijelentkezés
                </button>
            </form>
        </div>
    </div>
</nav>
