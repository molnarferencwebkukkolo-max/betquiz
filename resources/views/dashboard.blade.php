<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Műszerfal - BetQuiz</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body style="padding-bottom: 3rem;">

@include('layouts.navigation')

<div class="nav-container" style="padding-top: 2rem; padding-bottom: 2rem;">
    <div class="purple-banner" style="margin-bottom: 2rem; display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
        <div>
            @auth
                <h1 style="font-size: 1.875rem; font-weight: 900; margin-bottom: 0.25rem;">Üdv újra, {{ $user->name }}!</h1>
                <p style="font-size: 0.875rem; color: #e0e7ff; margin: 0; font-weight: 500;">Készen állsz egy újabb kvízre vagy saját kérdések feltöltésére?</p>
            @else
                <h1 style="font-size: 1.875rem; font-weight: 900; margin-bottom: 0.25rem;">BetQuiz kvízek egy helyen</h1>
                <p style="font-size: 0.875rem; color: #e0e7ff; margin: 0; font-weight: 500;">Böngészd a kvízeket. A játék indításához jelentkezz be vagy hozz létre fiókot.</p>
            @endauth
        </div>

        @auth
            <div style="background-color: rgba(255, 255, 255, 0.1); backdrop-filter: blur(8px); padding: 1rem 1.5rem; border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.2); display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 1.875rem;">PT</span>
                <div>
                    <p style="font-size: 0.75rem; font-weight: 700; color: #c7d2fe; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Jelenlegi egyenleged</p>
                    <p style="font-size: 1.5rem; font-weight: 900; color: #ffffff; margin: 0;">{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</p>
                </div>
            </div>
        @else
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="{{ route('login') }}" class="btn-primary-purple" style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.35); padding: 0.75rem 1.25rem; text-decoration: none;">Bejelentkezés</a>
                <a href="{{ route('register') }}" class="btn-primary-purple" style="background: #ffffff; color: #4f46e5; padding: 0.75rem 1.25rem; text-decoration: none;">Regisztráció</a>
            </div>
        @endauth
    </div>

    @if(auth()->check() && ($user->isUseradmin() || $user->isHostadmin()) && isset($pendingQuizzes) && $pendingQuizzes->isNotEmpty())
        <div class="admin-review-box" style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.125rem; font-weight: 800; color: #78350f; margin: 0 0 1rem 0;">Bírálatra váró kvízek ({{ $pendingQuizzes->count() }} db)</h2>
            <div class="admin-review-grid">
                @foreach ($pendingQuizzes as $pendingQuiz)
                    <div style="padding: 1rem; border-radius: 1rem; background-color: #ffffff; border: 1px solid #fde68a; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <span style="font-weight: 800; font-size: 1rem; color: #1f2937;">{{ $pendingQuiz->title }}</span>
                            <p style="font-size: 0.75rem; color: #4b5563; margin-top: 0.25rem; margin-bottom: 0;">
                                Készítette: <strong>{{ $pendingQuiz->creator->name ?? 'Anonim' }}</strong> - {{ $pendingQuiz->questions_count }} kérdés
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <form action="{{ route('admin.quizzes.approve', $pendingQuiz) }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="submit" class="status-badge-approved" style="border: none; cursor: pointer; padding: 0.5rem 0.75rem;">Jóváhagyás</button>
                            </form>

                            <form action="{{ route('admin.quizzes.reject', $pendingQuiz) }}" method="POST" style="margin: 0; min-width: 16rem;">
                                @csrf
                                <select class="moderation-reason-preset" style="width: 100%; margin-bottom: 0.5rem; padding: 0.5rem; border: 1px solid #fecaca; border-radius: 0.75rem;">
                                    <option value="">Gyakori indok…</option>
                                    <option value="A kvíz leírása nem elég részletes.">Hiányos leírás</option>
                                    <option value="A kvíz tartalma vagy témája nem felel meg a közzétételi irányelveknek.">Nem megfelelő tartalom</option>
                                    <option value="A kérdések vagy válaszok minősége további javítást igényel.">Minőségi javítás</option>
                                    <option value="A kvíz duplikált vagy jelentősen átfed egy már meglévő kvízzel.">Duplikált tartalom</option>
                                </select>
                                <textarea name="moderation_reason" rows="2" maxlength="2000" required class="moderation-reason-input"
                                          placeholder="Szerkeszthető indok…"
                                          style="width: 100%; margin-bottom: 0.5rem; padding: 0.5rem; border: 1px solid #fecaca; border-radius: 0.75rem;"></textarea>
                                <button type="submit" class="status-badge-rejected" style="border: none; cursor: pointer; padding: 0.5rem 0.75rem;">Elutasítás</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <script>
        document.querySelectorAll('.moderation-reason-preset').forEach((preset) => {
            preset.addEventListener('change', () => {
                const input = preset.closest('form')?.querySelector('.moderation-reason-input');
                if (preset.value && input) {
                    input.value = preset.value;
                    input.focus();
                }
            });
        });
    </script>

    <div class="quick-action-grid" style="margin-bottom: 2.5rem;">
        <a href="{{ auth()->check() ? route('quizzes.index') : '#' }}" class="quick-action-card" @guest onclick="event.preventDefault(); openGuestAuthPrompt();" @endguest>
            <div class="quick-action-icon icon-bg-indigo">Play</div>
            <div>
                <h3 style="font-weight: 800; color: #1f2937; font-size: 1.125rem; margin: 0;">Játék indítása</h3>
                <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 500; margin-top: 0.125rem; margin-bottom: 0;">Válassz a legnépszerűbb kvízek közül.</p>
            </div>
        </a>

        <a href="{{ auth()->check() ? route('my-quizzes.create') : '#' }}" class="quick-action-card" @guest onclick="event.preventDefault(); openGuestAuthPrompt();" @endguest>
            <div class="quick-action-icon icon-bg-amber">+</div>
            <div>
                <h3 style="font-weight: 800; color: #1f2937; font-size: 1.125rem; margin: 0;">Kvíz nyitása</h3>
                <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 500; margin-top: 0.125rem; margin-bottom: 0;">
                    @auth
                        Hozz létre saját kvízt 50.000 PT-ért.
                    @else
                        Jelentkezz be, és indíts saját kvízt.
                    @endauth
                </p>
            </div>
        </a>

        <a href="{{ auth()->check() ? route('my-quizzes.index') : '#' }}" class="quick-action-card" @guest onclick="event.preventDefault(); openGuestAuthPrompt();" @endguest>
            <div class="quick-action-icon icon-bg-emerald">Doc</div>
            <div>
                <h3 style="font-weight: 800; color: #1f2937; font-size: 1.125rem; margin: 0;">
                    @auth
                        Tartalmaim
                    @else
                        Csatlakozás
                    @endauth
                </h3>
                <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 500; margin-top: 0.125rem; margin-bottom: 0;">
                    @auth
                        Kérdéseid és kvízeid kezelése.
                    @else
                        Hozz létre fiókot, és mentsd az eredményeidet.
                    @endauth
                </p>
            </div>
        </a>
    </div>

    @auth
        <div style="margin-bottom: 3rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #1f2937; margin: 0;">Saját kvízeid állapota</h2>
                <a href="{{ route('my-quizzes.index') }}" style="font-size: 0.75rem; font-weight: 700; color: #4f46e5; text-decoration: none;">Saját kvízek kezelése -></a>
            </div>

            <div class="card-white" style="padding: 1.5rem;">
                @if($myQuizzes->isEmpty())
                    <div style="text-align: center; padding: 1.5rem 0; color: #9ca3af; font-size: 0.875rem;">
                        Még nem nyitottál saját kvízt. Hozz létre egyet!
                    </div>
                @else
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                        @foreach($myQuizzes as $q)
                            <div style="padding: 1rem; border-radius: 1rem; background-color: #f9fafb; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <h4 style="font-weight: 800; color: #1f2937; font-size: 0.875rem; margin: 0;">{{ $q->title }}</h4>
                                    <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 700; margin-top: 0.125rem; margin-bottom: 0;">
                                        {{ $q->questions_count }} / 100 kérdés feltöltve
                                    </p>
                                </div>

                                <div>
                                    @if($q->status === 'published')
                                        <span class="status-badge-approved">Publikus</span>
                                    @elseif($q->status === 'approved')
                                        <span class="status-badge-pending">Kérdésgyűjtés ({{ $q->questions_count }}/100)</span>
                                    @elseif($q->status === 'pending')
                                        <span class="status-badge-pending">Elbírálásra vár</span>
                                    @else
                                        <span class="status-badge-rejected">Elutasítva</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endauth

    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 2.5rem;">

    @php
        $quizRows = [
            'row-featured' => ['title' => 'Kiemelt kvízek', 'items' => $featuredQuizzes ?? collect()],
            'row-latest' => ['title' => 'Legújabb kvízek', 'items' => $latestQuizzes ?? collect()],
            'row-favorites' => ['title' => 'Kedvenc kvízeid', 'items' => $favoriteQuizzes ?? collect()],
            'row-hardest' => ['title' => 'Legnehezebb kvízek', 'items' => $hardestQuizzes ?? collect()],
            'row-unplayed' => ['title' => auth()->check() ? 'Ezzel még nem játszottál' : 'Felfedezésre ajánljuk', 'items' => $unplayedQuizzes ?? collect()],
            'row-category' => ['title' => 'Kategória favoritok', 'items' => $categoryFavoriteQuizzes ?? collect()],
            'row-popular' => ['title' => 'Mások szerint népszerű', 'items' => $popularQuizzes ?? collect()],
        ];

        $hasAnyActiveQuiz = collect($quizRows)->contains(fn($row) => $row['items']->isNotEmpty());
    @endphp

    @if(!$hasAnyActiveQuiz)
        <div style="text-align: center; padding: 3.5rem 1.5rem; background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 1.25rem; margin: 1rem 0;">
            <div style="font-size: 3.5rem; margin-bottom: 1rem;">?</div>
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #1e293b; margin-bottom: 0.5rem;">
                Jelenleg nincs elérhető aktív kvíz a rendszerben.
            </h3>
            <p style="color: #64748b; max-width: 520px; margin: 0 auto 1.5rem auto; font-size: 0.875rem; line-height: 1.5;">
                A beküldött kvízek jelenleg elbírálás alatt állnak, vagy a készítők még a kérdésgyűjtési fázisban járnak.
            </p>
            <a href="{{ auth()->check() ? route('my-quizzes.create') : route('register') }}" class="btn-primary-purple" style="display: inline-block; padding: 0.75rem 1.5rem; text-decoration: none; font-weight: 800; font-size: 0.875rem; border-radius: 0.75rem;">
                Új kvíz létrehozása
            </a>
        </div>
    @else
        @foreach($quizRows as $rowId => $row)
            @if($row['items']->isNotEmpty())
                <div class="netflix-row-container">
                    <h3 class="row-title">{{ $row['title'] }}</h3>
                    <button class="scroll-btn scroll-btn-left" onclick="scrollRow('{{ $rowId }}', -1)">&lt;</button>
                    <div class="netflix-slider" id="{{ $rowId }}">
                        @foreach($row['items'] as $quiz)
                            <div class="netflix-card-item">
                                @include('partials.quiz-card', ['quiz' => $quiz])
                            </div>
                        @endforeach
                    </div>
                    <button class="scroll-btn scroll-btn-right" onclick="scrollRow('{{ $rowId }}', 1)">&gt;</button>
                </div>
            @endif
        @endforeach
    @endif
</div>

@guest
    <div id="guestAuthPrompt" class="guest-auth-modal" aria-hidden="true">
        <div class="guest-auth-backdrop" onclick="closeGuestAuthPrompt()"></div>
        <div class="guest-auth-dialog" role="dialog" aria-modal="true" aria-labelledby="guestAuthTitle">
            <button type="button" class="guest-auth-close" onclick="closeGuestAuthPrompt()" aria-label="Bezárás">x</button>
            <h2 id="guestAuthTitle" class="guest-auth-title">Lépj be vagy regisztrálj INGYEN</h2>
            <p class="guest-auth-text">Regisztráció után azonnal kapsz 1.000 zsetont ajándékba, és már indulhat is a játék.</p>
            <div class="guest-auth-actions">
                <a href="{{ route('login') }}" class="btn-primary-purple" style="text-decoration: none;">Bejelentkezés</a>
                <a href="{{ route('register') }}" class="btn-hero-secondary" style="font-size: 1rem; padding: 0.75rem 1.25rem;">Regisztráció</a>
            </div>
        </div>
    </div>
@endguest

<script>
    function scrollRow(rowId, direction) {
        const container = document.getElementById(rowId);
        if (!container) return;

        container.scrollBy({
            left: direction * container.clientWidth * 0.75,
            behavior: 'smooth'
        });
    }

    function openGuestAuthPrompt() {
        const modal = document.getElementById('guestAuthPrompt');
        if (!modal) return;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeGuestAuthPrompt() {
        const modal = document.getElementById('guestAuthPrompt');
        if (!modal) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }
</script>

</body>
</html>
