<header class="nav-header">
    <div class="nav-container">
        <div class="nav-wrapper">

            <!-- Bal oldal: Logo + Navigációs Linkek -->
            <div style="display: flex; align-items: center; gap: 2rem;">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="nav-brand">
                    <span>BetQuiz</span>
                </a>

                <!-- Főmenü linkek -->
                <nav style="display: flex; align-items: center; gap: 0.5rem;">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                       class="nav-link-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        Dashboard
                    </a>

                    <!-- Játék (Katalógus) -->
                    <a href="{{ route('quizzes.index') }}"
                       class="nav-link-item {{ request()->routeIs('quizzes.*') || request()->routeIs('quiz.*') ? 'active' : '' }}">
                        🎮 Játék
                    </a>

                    <!-- Kvízeim (Alkotói felület) -->
                    <a href="{{ route('my-quizzes.index') }}"
                       class="nav-link-item {{ request()->routeIs('my-quizzes.*') ? 'active' : '' }}">
                        📝 Kvízeim
                    </a>

                    <!-- Szerezz pontot -->
                    <a href="{{ route('pages.points') }}"
                       class="nav-btn-points">
                        ⭐ Szerezz pontot
                    </a>

                    <!-- ADMIN / HOSTADMIN Menüpontok -->
                    @if(auth()->check() && (auth()->user()->isUseradmin() || auth()->user()->isHostadmin()))
                        <a href="{{ route('questions.index') }}"
                           class="nav-link-item nav-link-purple {{ request()->routeIs('questions.*') ? 'active' : '' }}">
                            📚 Kérdésbank
                        </a>

                        <span class="badge-no-quiz" style="cursor: not-allowed; opacity: 0.7;" title="Hamarosan érkezik">
                            👥 Felhasználók
                        </span>
                    @endif
                </nav>
            </div>

            <!-- Jobb oldal: Egyenleg + Felhasználó + Kijelentkezés -->
            <div style="display: flex; align-items: center; gap: 1rem;">
                <!-- Zseton / Pontszám kijelző -->
                <div class="nav-badge-tokens">
                    <span>💰</span>
                    <span>{{ number_format(auth()->user()->points ?? 0) }} PT</span>
                </div>

                <!-- Profil link -->
                <a href="{{ route('profile.show') }}" class="nav-link-item">
                    👤 {{ Auth::user()->name }}
                </a>

                <!-- Kijelentkezés gomb -->
                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="nav-btn-logout">
                        🚪 Kijelentkezés
                    </button>
                </form>
            </div>

        </div>
    </div>
</header>
