<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilom - BetQuiz</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body style="padding-bottom: 3rem;">

@include('layouts.navigation')

<div class="profile-container">

    <h1 class="profile-title">👤 Profilom</h1>

    @if(session('success'))
        <div class="alert-success-custom">
            {{ session('success') }}
        </div>
    @endif

    <div class="profile-grid">

        <!-- Bal oldal: Felhasználói kártya -->
        <div class="profile-user-card">
            <div class="profile-avatar">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <h2 class="profile-user-name">{{ $user->name }}</h2>
            <p class="profile-user-email">{{ $user->email }}</p>

            <div class="role-badge {{ $user->isHostadmin() ? 'role-badge-hostadmin' : ($user->isUseradmin() ? 'role-badge-useradmin' : 'role-badge-user') }}">
                {{ $user->role ?? 'user' }}
            </div>
        </div>

        <!-- Jobb oldal: Műveletek -->
        <div>

            <!-- 🛠️ DEVTOOL: Szerepkör Váltó (Teszteléshez) -->
            <div class="dev-tool-box">
                <h3>🛠️ DevTool - Szerepkör váltás</h3>
                <p>Jelenlegi szerepköröd: <strong>{{ auth()->user()->role ?? 'user' }}</strong></p>

                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                    <!-- Sima User gomb -->
                    <form action="{{ route('profile.switch-role') }}" method="POST">
                        @csrf
                        <input type="hidden" name="role" value="user">
                        <button type="submit" class="btn-secondary-gray">
                            👤 Sima User
                        </button>
                    </form>

                    <!-- UserAdmin gomb -->
                    <form action="{{ route('profile.switch-role') }}" method="POST">
                        @csrf
                        <input type="hidden" name="role" value="useradmin">
                        <button type="submit" class="btn-primary-purple">
                            🛡️ UserAdmin
                        </button>
                    </form>

                    <!-- HostAdmin gomb -->
                    <form action="{{ route('profile.switch-role') }}" method="POST">
                        @csrf
                        <input type="hidden" name="role" value="hostadmin">
                        <button type="submit" class="btn-primary-purple" style="background-color: #dc2626;">
                            👑 HostAdmin
                        </button>
                    </form>
                </div>
            </div>

            <!-- Játékélmény beállítások -->
            <div class="password-card">
                <h3 class="password-card-title">🎮 Játékélmény</h3>

                <form action="{{ route('profile.game-experience') }}" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                    @csrf

                    <div>
                        <label class="form-label">Időutazás segítség:</label>

                        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem;">
                            <label style="display: block; cursor: pointer;">
                                <input type="radio" name="time_travel_theme" value="back_to_future" {{ ($user->time_travel_theme ?? 'back_to_future') === 'back_to_future' ? 'checked' : '' }} style="margin-right: 0.4rem;">
                                <strong>Vissza a jövőbe</strong>
                            </label>

                            <label style="display: block; cursor: pointer;">
                                <input type="radio" name="time_travel_theme" value="harry_potter" {{ ($user->time_travel_theme ?? 'back_to_future') === 'harry_potter' ? 'checked' : '' }} style="margin-right: 0.4rem;">
                                <strong>Harry Potter</strong>
                            </label>
                        </div>

                        @error('time_travel_theme') <span style="font-size: 0.75rem; color: #dc2626;">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <button type="submit" class="btn-save-password">
                            Mentés
                        </button>
                    </div>
                </form>
            </div>

            <!-- Értesítési beállítások -->
            <div class="password-card">
                <h3 class="password-card-title">🔔 Értesítések</h3>
                <p style="color: #64748b; font-size: 0.875rem; line-height: 1.5; margin-bottom: 1.25rem;">
                    Eseményenként kiválaszthatod, hogy az alkalmazásban, e-mailben, mindkét csatornán vagy egyiken sem kérsz értesítést.
                </p>

                <form action="{{ route('profile.notification-preferences') }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="notification-preference-table">
                        <div class="notification-preference-header">
                            <span>Esemény</span>
                            <span>Belső</span>
                            <span>E-mail</span>
                        </div>

                        @foreach($notificationEvents as $event => $label)
                            @php($preference = $notificationPreferences->get($event))
                            <div class="notification-preference-row">
                                <div>
                                    <strong>{{ $label }}</strong>
                                    @if($event === 'weekly_report')
                                        <small>A heti jelentésküldő funkció indulásakor lép életbe.</small>
                                    @endif
                                </div>
                                <label class="notification-channel-toggle">
                                    <input type="hidden" name="preferences[{{ $event }}][event]" value="{{ $event }}">
                                    <input type="checkbox" name="preferences[{{ $event }}][database]" value="1"
                                           @checked(old("preferences.{$event}.database", $preference?->database_enabled ?? true))>
                                    <span class="sr-only">{{ $label }} – belső értesítés</span>
                                </label>
                                <label class="notification-channel-toggle">
                                    <input type="checkbox" name="preferences[{{ $event }}][email]" value="1"
                                           @checked(old("preferences.{$event}.email", $preference?->email_enabled ?? false))>
                                    <span class="sr-only">{{ $label }} – e-mail</span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    @error('preferences')
                        <p style="color: #dc2626; font-size: 0.8rem; font-weight: 700; margin-top: 0.75rem;">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="btn-save-password" style="margin-top: 1.25rem;">
                        Értesítési beállítások mentése
                    </button>
                </form>
            </div>

            <!-- Jelszó módosítása -->
            <div class="password-card">
                <h3 class="password-card-title">🔑 Jelszó Módosítása</h3>

                <form action="{{ route('profile.password') }}" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                    @csrf
                    <div>
                        <label class="form-label">Jelenlegi jelszó:</label>
                        <input type="password" name="current_password" required class="form-input-profile">
                        @error('current_password') <span style="font-size: 0.75rem; color: #dc2626;">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="form-label">Új jelszó:</label>
                        <input type="password" name="password" required class="form-input-profile">
                        @error('password') <span style="font-size: 0.75rem; color: #dc2626;">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="form-label">Új jelszó megerősítése:</label>
                        <input type="password" name="password_confirmation" required class="form-input-profile">
                    </div>

                    <div>
                        <button type="submit" class="btn-save-password">
                            Mentés
                        </button>
                    </div>
                </form>
            </div>

        </div>

    </div>

</div>

</body>
</html>
