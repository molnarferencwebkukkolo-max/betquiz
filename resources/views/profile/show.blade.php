<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilom - KwizzGo</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="profile-page">
@include('layouts.navigation')

<main class="profile-shell">
    <header class="profile-hero">
        <div>
            <h1><span aria-hidden="true">👤</span> Profilom</h1>
            <p>Kezeld adataidat, beállításaidat és kövesd nyomon az eredményeidet.</p>
        </div>
        <div class="profile-hero-trophy" aria-hidden="true">🏆</div>
    </header>

    @if(session('success'))
        <div class="profile-alert success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="profile-alert error" role="alert">
            <strong>A mentés nem sikerült.</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="profile-dashboard">
        <aside class="profile-sidebar">
            <section class="profile-panel profile-identity">
                <div class="profile-avatar">{{ mb_strtoupper(mb_substr($user->username ?: $user->name, 0, 1)) }}</div>
                <h2>{{ $user->username ?: $user->name }} <span class="verified-mark" title="Regisztrált felhasználó">◆</span></h2>
                <p>{{ $user->email }}</p>
                <span class="role-badge {{ $user->isHostadmin() ? 'role-badge-hostadmin' : ($user->isUseradmin() ? 'role-badge-useradmin' : 'role-badge-user') }}">{{ $user->role ?? 'user' }}</span>
                <a href="{{ route('profile.edit') }}" class="profile-secondary-button">✎ &nbsp; Alapadatok szerkesztése</a>

                <div class="profile-side-stats">
                    <div><span>🏆</span><p>Összes pont<strong>{{ number_format($user->points, 0, ',', ' ') }} PT</strong></p></div>
                    <div><span>⚡</span><p>Kitöltött kvízek<strong>{{ number_format($profileStats['games'], 0, ',', ' ') }}</strong></p></div>
                    <div><span>🎯</span><p>Megválaszolt kérdések<strong>{{ number_format($profileStats['answers'], 0, ',', ' ') }}</strong></p></div>
                </div>
                <a href="{{ route('profile.results') }}" class="profile-text-link">Részletes statisztikák →</a>
            </section>

            <section class="profile-panel compact-panel">
                <h3>🎮 Játékélmény</h3>
                <form action="{{ route('profile.game-experience') }}" method="POST">
                    @csrf
                    <p class="panel-description">Időutazás segítség:</p>
                    <div class="profile-radio-grid">
                        <label><input type="radio" name="time_travel_theme" value="back_to_future" @checked(($user->time_travel_theme ?? 'back_to_future') === 'back_to_future')> Vissza a jövőbe</label>
                        <label><input type="radio" name="time_travel_theme" value="harry_potter" @checked(($user->time_travel_theme ?? 'back_to_future') === 'harry_potter')> Harry Potter</label>
                    </div>
                    <button class="profile-primary-button" type="submit">Mentés</button>
                </form>
            </section>

            <section class="profile-panel compact-panel profile-quick-links">
                <h3>🧩 Gyors hivatkozások</h3>
                <a href="{{ route('my-quizzes.index') }}">▣ Saját kvízeim <span>›</span></a>
                <a href="{{ route('questions.index') }}">▤ Kérdésbank <span>›</span></a>
                <a href="{{ route('pages.points') }}">◉ Szerezz pontot <span>›</span></a>
                @if($user->isUseradmin())
                    <a href="{{ route('admin.users.index') }}">♟ Felhasználók <span>›</span></a>
                    <a href="{{ route('admin.categories.index') }}">⚙ Kategóriák <span>›</span></a>
                @endif
            </section>
        </aside>

        <div class="profile-main-column">
            <section class="profile-panel results-overview">
                <div class="panel-heading-row">
                    <div><h3>🏆 Eredményeim</h3><p class="panel-description">Nézd meg az összesített találati arányodat és teljesítményedet.</p></div>
                    <a href="{{ route('profile.results') }}" class="profile-small-button">Kimutatás megnyitása →</a>
                </div>
                <div class="profile-result-cards">
                    <article><span>🎯</span><small>Találati arány</small><strong>{{ $profileStats['accuracy'] }}%</strong></article>
                    <article><span>⭐</span><small>Összes pont</small><strong>{{ number_format($user->points, 0, ',', ' ') }} PT</strong></article>
                    <article><span>🔥</span><small>Kitöltött kvízek</small><strong>{{ $profileStats['games'] }}</strong></article>
                    <article><span>📈</span><small>Megválaszolt kérdések</small><strong>{{ $profileStats['answers'] }}</strong></article>
                </div>
            </section>

            <section class="profile-panel private-details-panel">
                <h3>🎁 Adj meg pár adatot</h3>
                <p class="panel-description">Ezek az adatok privátak, sehol nem jelenítjük meg őket. A kedvenc kategóriád alapján személyre szabjuk majd a főoldali ajánlásokat.</p>
                @if(!$user->profile_details_rewarded_at)
                    <p class="profile-reward-copy">Tölts ki minden mezőt, és kapsz +2 000 PT ajándékot!</p>
                @else
                    <p class="profile-reward-copy completed">✓ A kitöltési jutalmat már megkaptad.</p>
                @endif
                <form action="{{ route('profile.private-details') }}" method="POST" class="profile-details-form">
                    @csrf @method('PATCH')
                    <label><span>Születési idő</span><input type="date" name="birth_date" max="{{ now()->subDay()->toDateString() }}" value="{{ old('birth_date', $user->birth_date?->toDateString()) }}" required></label>
                    <label><span>Nem</span><select name="gender" required><option value="">Válassz…</option><option value="male" @selected(old('gender', $user->gender) === 'male')>Férfi</option><option value="female" @selected(old('gender', $user->gender) === 'female')>Nő</option><option value="other" @selected(old('gender', $user->gender) === 'other')>Egyéb</option><option value="prefer_not_to_say" @selected(old('gender', $user->gender) === 'prefer_not_to_say')>Nem mondom meg</option></select></label>
                    <label><span>Ország</span><input type="text" name="country" maxlength="100" value="{{ old('country', $user->country) }}" required></label>
                    <label><span>Megye / régió</span><input type="text" name="county" maxlength="100" value="{{ old('county', $user->county) }}" required></label>
                    <label><span>Kedvenc kategória</span><select name="favorite_category_id" required><option value="">Válassz…</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) old('favorite_category_id', $user->favorite_category_id) === $category->id)>{{ $category->icon }} {{ $category->translated_name }}</option>@endforeach</select></label>
                    <label><span>Családi állapot</span><select name="relationship_status" required><option value="">Válassz…</option><option value="single" @selected(old('relationship_status', $user->relationship_status) === 'single')>Egyedül</option><option value="relationship" @selected(old('relationship_status', $user->relationship_status) === 'relationship')>Kapcsolatban</option><option value="married" @selected(old('relationship_status', $user->relationship_status) === 'married')>Házas</option></select></label>
                    <label><span>Gyerekek száma</span><input type="number" name="children_count" min="0" max="30" value="{{ old('children_count', $user->children_count) }}" required></label>
                    <div class="profile-form-action"><button class="profile-primary-button" type="submit">▣ &nbsp; Privát adatok mentése</button></div>
                </form>
            </section>

            @if(app()->environment('local'))
                <section class="profile-devtool">
                    <h3>🛠 DevTool – Szerepkör váltás</h3>
                    <p>Jelenlegi szerepköröd: <strong>{{ $user->role ?? 'user' }}</strong></p>
                    <div class="profile-role-actions">
                        @foreach(['user' => '👤 Sima User', 'useradmin' => '🛡 UserAdmin', 'hostadmin' => '♛ HostAdmin'] as $role => $label)
                            <form action="{{ route('profile.switch-role') }}" method="POST">@csrf<input type="hidden" name="role" value="{{ $role }}"><button class="{{ $user->role === $role ? 'active' : '' }}" type="submit">{{ $label }}</button></form>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="profile-panel notification-panel">
                <h3>🔔 Értesítések</h3>
                <p class="panel-description">Eseményenként kiválaszthatod, hogy az alkalmazásban, e-mailben, mindkét csatornán vagy egyiken sem kérsz értesítést.</p>
                <form action="{{ route('profile.notification-preferences') }}" method="POST">@csrf @method('PATCH')
                    <div class="notification-preference-table">
                        <div class="notification-preference-header"><span>Esemény</span><span>Belső</span><span>E-mail</span></div>
                        @foreach($notificationEvents as $event => $label)
                            @php($preference = $notificationPreferences->get($event))
                            <div class="notification-preference-row">
                                <div><strong>{{ $label }}</strong>@if($event === 'weekly_report')<small>Hetenként összefoglalót kapsz az előző heti eredményeidről.</small>@elseif($event === 'marketing')<small>Kampányok, ajánlatok és ajándék PT-lehetőségek. A kampányoldalak megtekintéséért ajándék PT járhat.</small>@endif</div>
                                <input type="hidden" name="preferences[{{ $event }}][event]" value="{{ $event }}">
                                @if(\App\Models\NotificationPreference::supportsChannel($event, 'database'))
                                    <label class="notification-channel-toggle"><input type="checkbox" name="preferences[{{ $event }}][database]" value="1" @checked(old("preferences.{$event}.database", $preference?->database_enabled ?? true))><span class="sr-only">{{ $label }} – belső</span></label>
                                @else <span class="unavailable-channel">—</span> @endif
                                <label class="notification-channel-toggle"><input type="checkbox" name="preferences[{{ $event }}][email]" value="1" @checked(old("preferences.{$event}.email", $preference?->email_enabled ?? false))><span class="sr-only">{{ $label }} – e-mail</span></label>
                            </div>
                        @endforeach
                    </div>
                    <div class="notification-save"><button class="profile-primary-button" type="submit">▣ &nbsp; Értesítési beállítások mentése</button></div>
                </form>
            </section>
        </div>
    </div>
</main>
</body>
</html>
