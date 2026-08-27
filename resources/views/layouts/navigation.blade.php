@php($unreadNotificationCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0)
<header class="nav-header" data-mobile-navigation>
    <div class="nav-container">
        <div class="nav-wrapper">
            <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="nav-brand">
                <span>Kwizz<span class="brand-accent">Go</span></span>
            </a>

            <button type="button" class="nav-menu-toggle" aria-expanded="false"
                    aria-controls="kwizzgo-navigation" aria-label="Menü megnyitása">
                <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
            </button>

            <div id="kwizzgo-navigation" class="nav-menu-panel" aria-hidden="false">
                <div class="nav-mobile-heading">
                    <span>Menü</span>
                    <button type="button" class="nav-menu-close" aria-label="Menü bezárása">×</button>
                </div>

                <nav class="nav-primary-links" aria-label="Fő navigáció">
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
                        <button type="button" class="nav-link-item nav-link-button" onclick="typeof openGuestAuthPrompt === 'function' ? openGuestAuthPrompt() : window.location.assign('{{ route('login') }}')">
                            Játék
                        </button>
                    @endauth

                    @auth
                        <a href="{{ route('my-quizzes.index') }}"
                           class="nav-link-item {{ request()->routeIs('my-quizzes.*') ? 'active' : '' }}">
                            Kvízeim
                        </a>
                        <a href="{{ route('pages.points') }}" class="nav-btn-points">Szerezz pontot</a>
                    @endauth

                    @if(auth()->check() && auth()->user()->isUseradmin())
                        @php($adminNavigationActive = request()->routeIs('questions.*', 'admin.users.*', 'admin.contents.*', 'admin.email-templates.*', 'admin.advertisements.*', 'admin.categories.*'))
                        <details class="nav-admin-dropdown" @if($adminNavigationActive) data-active @endif>
                            <summary class="nav-link-item nav-admin-trigger {{ $adminNavigationActive ? 'active' : '' }}">Adminisztráció <span aria-hidden="true">⌄</span></summary>
                            <div class="nav-admin-dropdown-panel">
                                <span class="nav-group-label">Adminisztráció</span>
                                <a href="{{ route('questions.index') }}" class="nav-link-item nav-link-purple {{ request()->routeIs('questions.*') ? 'active' : '' }}">Kérdésbank</a>
                                <a href="{{ route('admin.users.index') }}" class="nav-link-item nav-link-purple {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Felhasználók</a>
                                @if(auth()->user()->isHostadmin())
                                    <a href="{{ route('admin.email-templates.index') }}" class="nav-link-item nav-link-purple {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}">E-mail sablonok</a>
                                    <a href="{{ route('admin.contents.index') }}" class="nav-link-item nav-link-purple {{ request()->routeIs('admin.contents.*') ? 'active' : '' }}">Tartalomkezelő</a>
                                    <a href="{{ route('admin.advertisements.index') }}" class="nav-link-item nav-link-purple {{ request()->routeIs('admin.advertisements.*') ? 'active' : '' }}">Hirdetések</a>
                                    <a href="{{ route('admin.categories.index') }}" class="nav-link-item nav-link-purple {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Kategóriák</a>
                                @endif
                            </div>
                        </details>
                    @endif
                </nav>

                <div class="nav-account-actions">
                    @auth
                        <a href="{{ route('notifications.index') }}"
                           class="nav-notification-bell {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
                           aria-label="Értesítések{{ $unreadNotificationCount ? ', '.$unreadNotificationCount.' olvasatlan' : '' }}"
                           title="Értesítések">
                            <span aria-hidden="true">🔔</span>
                            <span class="nav-mobile-action-label">Értesítések</span>
                            @if($unreadNotificationCount > 0)
                                <span class="nav-notification-count">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                            @endif
                        </a>
                        <div class="nav-badge-tokens"><span>{{ number_format(auth()->user()->points ?? 0) }} PT</span></div>
                        <a href="{{ route('profile.show') }}" class="nav-link-item nav-profile-link">
                            {{ Auth::user()->username ?? Auth::user()->name }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="nav-logout-form">
                            @csrf
                            <button type="submit" class="nav-btn-logout">Kijelentkezés</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="nav-link-item">Bejelentkezés</a>
                        <a href="{{ route('register') }}" class="btn-primary-purple nav-register-link">Regisztráció</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <button type="button" class="nav-menu-overlay" aria-label="Menü bezárása" tabindex="-1"></button>
</header>

<script>
    (() => {
        const root = document.currentScript.previousElementSibling;
        if (!root || root.dataset.mobileNavigationReady === 'true') return;

        root.dataset.mobileNavigationReady = 'true';
        const panel = root.querySelector('.nav-menu-panel');
        const toggle = root.querySelector('.nav-menu-toggle');
        const closeButton = root.querySelector('.nav-menu-close');
        const overlay = root.querySelector('.nav-menu-overlay');
        const mobileQuery = window.matchMedia('(max-width: 900px)');
        let previouslyFocused = null;
        const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

        function setOpen(open, restoreFocus = true) {
            if (!mobileQuery.matches) open = false;
            root.classList.toggle('is-menu-open', open);
            document.body.classList.toggle('nav-menu-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Menü bezárása' : 'Menü megnyitása');
            panel.setAttribute('aria-hidden', mobileQuery.matches && !open ? 'true' : 'false');
            panel.inert = mobileQuery.matches && !open;

            if (open) {
                previouslyFocused = document.activeElement;
                requestAnimationFrame(() => closeButton.focus());
            } else if (restoreFocus && previouslyFocused && mobileQuery.matches) {
                previouslyFocused.focus();
                previouslyFocused = null;
            }
        }

        toggle.addEventListener('click', () => setOpen(!root.classList.contains('is-menu-open')));
        closeButton.addEventListener('click', () => setOpen(false));
        overlay.addEventListener('click', () => setOpen(false));
        panel.addEventListener('click', event => {
            if (event.target.closest('a, button.nav-link-button')) setOpen(false, false);
        });

        root.addEventListener('keydown', event => {
            if (!root.classList.contains('is-menu-open')) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                setOpen(false);
                return;
            }
            if (event.key !== 'Tab') return;

            const focusable = [...panel.querySelectorAll(focusableSelector)].filter(element => !element.inert);
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        mobileQuery.addEventListener('change', () => setOpen(false, false));
        setOpen(false, false);
    })();
</script>
<x-cookie-consent />
