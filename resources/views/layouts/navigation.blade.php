<header class="nav-header">
    <div class="nav-container">
        <div class="nav-wrapper">
            <div style="display: flex; align-items: center; gap: 2rem;">
                <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="nav-brand">
                    <span>BetQuiz</span>
                </a>

                <nav style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ auth()->check() ? route('dashboard') : url('/') }}"
                       class="nav-link-item {{ request()->routeIs('dashboard') || request()->is('/') ? 'active' : '' }}">
                        Dashboard
                    </a>

                    @auth
                        <a href="{{ route('quizzes.index') }}"
                           class="nav-link-item {{ request()->routeIs('quizzes.*') || request()->routeIs('quiz.*') ? 'active' : '' }}">
                            Játék
                        </a>
                    @else
                        <button type="button" class="nav-link-item nav-link-button" onclick="openGuestAuthPrompt()">
                            Játék
                        </button>
                    @endauth

                    @auth
                        <a href="{{ route('my-quizzes.index') }}"
                           class="nav-link-item {{ request()->routeIs('my-quizzes.*') ? 'active' : '' }}">
                            Kvízeim
                        </a>

                        <a href="{{ route('pages.points') }}" class="nav-btn-points">
                            Szerezz pontot
                        </a>
                    @endauth

                    @if(auth()->check() && (auth()->user()->isUseradmin() || auth()->user()->isHostadmin()))
                        <a href="{{ route('questions.index') }}"
                           class="nav-link-item nav-link-purple {{ request()->routeIs('questions.*') ? 'active' : '' }}">
                            Kérdésbank
                        </a>

                        <span class="badge-no-quiz" style="cursor: not-allowed; opacity: 0.7;" title="Hamarosan érkezik">
                            Felhasználók
                        </span>
                    @endif
                </nav>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                @auth
                    <div class="nav-badge-tokens">
                        <span>{{ number_format(auth()->user()->points ?? 0) }} PT</span>
                    </div>

                    <a href="{{ route('profile.show') }}" class="nav-link-item">
                        {{ Auth::user()->name }}
                    </a>

                    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="nav-btn-logout">
                            Kijelentkezés
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-link-item">Bejelentkezés</a>
                    <a href="{{ route('register') }}" class="btn-primary-purple" style="padding: 0.625rem 1rem; font-size: 0.875rem; text-decoration: none;">Regisztráció</a>
                @endauth
            </div>
        </div>
    </div>
</header>
