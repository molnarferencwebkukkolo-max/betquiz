<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KwizzGo – A kvízek világa</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="home-page">

@include('layouts.navigation')

<main>
    <section class="home-hero">
        <div class="home-orb home-orb-one"></div>
        <div class="home-orb home-orb-two"></div>
        <div class="home-hero-inner">
            <div class="home-hero-copy">
                <span class="home-eyebrow"><span>⚡</span> Tanulj. Játssz. Versenyezz.</span>
                @auth
                    <p class="home-welcome">Szia, {{ $user->name }}! 👋</p>
                @endauth
                <h1>Kvízek világa.<br>Indulj! <span>KwizzGo!</span></h1>
                <p class="home-lead">Hozz létre saját kvízeket, játssz izgalmas témákkal, gyűjts pontokat és bizonyítsd a tudásod!</p>
                <div class="home-hero-actions">
                    <a href="{{ auth()->check() ? route('quizzes.index') : route('register') }}" class="home-btn-primary">
                        {{ auth()->check() ? 'Kezdj játszani' : 'Kezdj ingyen' }} <span>→</span>
                    </a>
                    <a href="{{ auth()->check() ? route('quizzes.index') : route('login') }}" class="home-btn-secondary">
                        {{ auth()->check() ? 'Fedezd fel a kvízeket' : 'Már van fiókom' }}
                    </a>
                </div>
                <div class="home-stats">
                    <div><span class="home-stat-icon purple">◆</span><strong>{{ number_format($homeStats['quizzes'], 0, ',', ' ') }}+</strong><small>Aktív kvíz</small></div>
                    <div><span class="home-stat-icon gold">👥</span><strong>{{ number_format($homeStats['players'], 0, ',', ' ') }}+</strong><small>Játékos</small></div>
                    <div><span class="home-stat-icon green">🏆</span><strong>{{ number_format($homeStats['answers'], 0, ',', ' ') }}+</strong><small>Megadott válasz</small></div>
                </div>
            </div>

            <div class="home-app-preview" aria-label="KwizzGo alkalmazás előnézete">
                <div class="home-preview-topbar">
                    <span class="home-preview-logo">Kwizz<span>Go</span></span>
                    <div><strong>{{ auth()->check() ? 'Szia, '.$user->name.'!' : 'Szia, Játékos!' }}</strong><small>Készen állsz egy új kihívásra?</small></div>
                    <div class="home-preview-points">⭐ {{ number_format($user->points ?? 1000, 0, ',', ' ') }} PT</div>
                </div>
                <div class="home-preview-actions">
                    <a href="{{ auth()->check() ? route('quizzes.index') : route('register') }}"><i class="violet">?</i><span><strong>Napi kihívás</strong><small>Tedd próbára magad</small></span></a>
                    <a href="{{ auth()->check() ? route('quizzes.index') : route('login') }}"><i class="blue">⚡</i><span><strong>Gyors játék</strong><small>Válassz egy kvízt</small></span></a>
                    <a href="{{ auth()->check() ? route('my-quizzes.create') : route('register') }}"><i class="green">+</i><span><strong>Kvíz létrehozása</strong><small>Mutasd meg a tudásod</small></span></a>
                </div>
                <div class="home-preview-heading"><strong>Ajánlott kvízek</strong><a href="{{ auth()->check() ? route('quizzes.index') : route('register') }}">Összes</a></div>
                <div class="home-preview-quizzes">
                    @forelse(($latestQuizzes ?? collect())->take(3) as $quiz)
                        <a href="{{ auth()->check() ? route('quiz.setup', $quiz) : route('register') }}">
                            <span class="home-preview-cover" @if($quiz->cover_image) style="background-image:url('{{ asset('storage/'.$quiz->cover_image) }}')" @endif></span>
                            <strong>{{ Str::limit($quiz->title, 24) }}</strong>
                            <small>{{ $quiz->questions_count }} kérdés · ⭐ {{ number_format($quiz->totalAnswersCount()) }}</small>
                        </a>
                    @empty
                        @foreach(['Általános tudás', 'Filmek világa', 'Sport és történelem'] as $fallback)
                            <div><span class="home-preview-cover"></span><strong>{{ $fallback }}</strong><small>Hamarosan indul</small></div>
                        @endforeach
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="home-light-section">
        <div class="home-content-shell">
            <h2>Miért válaszd a <span>KwizzGo</span>-t?</h2>
            <div class="home-benefit-grid">
                <article><i class="purple">🎮</i><h3>Játssz</h3><p>Válassz sokféle témából, és tedd próbára magad.</p></article>
                <article><i class="gold">🏆</i><h3>Versenyezz</h3><p>Gyűjts pontokat, fejlődj, és kerülj egyre feljebb.</p></article>
                <article><i class="green">✎</i><h3>Hozz létre</h3><p>Készíts saját kvízeket, és oszd meg a tudásod.</p></article>
                <article><i class="blue">👥</i><h3>Kapcsolódj</h3><p>Csatlakozz a közösséghez, és fedezz fel új kihívásokat.</p></article>
            </div>

            <div class="home-how">
                <h2>Hogyan működik?</h2>
                <div class="home-steps">
                    <div><b>1</b><i>🎯</i><h3>Válassz</h3><p>Találd meg a neked való kvízt.</p></div>
                    <span>→</span>
                    <div><b>2</b><i>🧠</i><h3>Játssz</h3><p>Válaszolj és gyűjts pontokat.</p></div>
                    <span>→</span>
                    <div><b>3</b><i>📈</i><h3>Fejlődj</h3><p>Tanulj minden egyes játékból.</p></div>
                    <span>→</span>
                    <div><b>4</b><i>🏆</i><h3>Nyerd meg</h3><p>Kerülj a legjobbak közé.</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-dynamic-section">
        <div class="home-content-shell">
            @if(auth()->check() && ($user->isUseradmin() || $user->isHostadmin()) && isset($pendingQuizzes) && $pendingQuizzes->isNotEmpty())
                <div class="admin-review-box home-admin-review">
                    <h2>Bírálatra váró kvízek ({{ $pendingQuizzes->count() }} db)</h2>
                    <div class="admin-review-grid">
                        @foreach ($pendingQuizzes as $pendingQuiz)
                            <article class="home-review-card">
                                <div><strong>{{ $pendingQuiz->title }}</strong><small>{{ $pendingQuiz->creator->name ?? 'Anonim' }} · {{ $pendingQuiz->questions_count }} kérdés</small></div>
                                <form action="{{ route('admin.quizzes.approve', $pendingQuiz) }}" method="POST">@csrf<button class="status-badge-approved">Jóváhagyás</button></form>
                                <form action="{{ route('admin.quizzes.reject', $pendingQuiz) }}" method="POST" class="home-reject-form">
                                    @csrf
                                    <select class="moderation-reason-preset"><option value="">Elutasítási indok…</option><option value="A kvíz leírása nem elég részletes.">Hiányos leírás</option><option value="A kérdések vagy válaszok minősége további javítást igényel.">Minőségi javítás</option></select>
                                    <textarea name="moderation_reason" required class="moderation-reason-input" placeholder="Szerkeszthető indok…"></textarea>
                                    <button class="status-badge-rejected">Elutasítás</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @auth
                <div class="home-own-quizzes">
                    <div class="home-section-heading"><h2>Saját kvízeid</h2><a href="{{ route('my-quizzes.index') }}">Kezelés →</a></div>
                    <div class="home-own-grid">
                        @forelse($myQuizzes->take(4) as $q)
                            <a href="{{ route('my-quizzes.show', $q) }}"><strong>{{ $q->title }}</strong><small>{{ $q->questions_count }} kérdés · {{ ucfirst($q->status) }}</small></a>
                        @empty
                            <a href="{{ route('my-quizzes.create') }}" class="home-empty-own"><strong>Hozd létre az első kvízed!</strong><small>Az induláshoz kattints ide.</small></a>
                        @endforelse
                    </div>
                </div>
            @endauth

            @php
                $quizRows = [
                    'row-featured' => ['title' => 'Kiemelt kvízek', 'items' => $featuredQuizzes ?? collect()],
                    'row-latest' => ['title' => 'Legújabb kvízek', 'items' => $latestQuizzes ?? collect()],
                    'row-favorites' => ['title' => 'Kedvenc kvízeid', 'items' => $favoriteQuizzes ?? collect()],
                    'row-hardest' => ['title' => 'Legnehezebb kvízek', 'items' => $hardestQuizzes ?? collect()],
                    'row-unplayed' => ['title' => auth()->check() ? 'Ezzel még nem játszottál' : 'Felfedezésre ajánljuk', 'items' => $unplayedQuizzes ?? collect()],
                    'row-popular' => ['title' => 'Népszerű kvízek', 'items' => $popularQuizzes ?? collect()],
                ];
                $hasQuizzes = collect($quizRows)->contains(fn ($row) => $row['items']->isNotEmpty());
            @endphp

            @if(!$hasQuizzes)
                <div class="home-empty-state"><span>?</span><h2>Hamarosan érkeznek az első aktív kvízek</h2><p>Addig is készítsd el a saját kihívásodat!</p><a href="{{ auth()->check() ? route('my-quizzes.create') : route('register') }}">Kvíz létrehozása</a></div>
            @else
                @foreach($quizRows as $rowId => $row)
                    @if($row['items']->isNotEmpty())
                        <div class="netflix-row-container home-quiz-row">
                            <h3 class="row-title">{{ $row['title'] }}</h3>
                            <button class="scroll-btn scroll-btn-left" onclick="scrollRow('{{ $rowId }}', -1)">&lt;</button>
                            <div class="netflix-slider" id="{{ $rowId }}">
                                @foreach($row['items'] as $quiz)<div class="netflix-card-item">@include('partials.quiz-card', ['quiz' => $quiz])</div>@endforeach
                            </div>
                            <button class="scroll-btn scroll-btn-right" onclick="scrollRow('{{ $rowId }}', 1)">&gt;</button>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </section>
</main>

@guest
    <div id="guestAuthPrompt" class="guest-auth-modal" aria-hidden="true">
        <div class="guest-auth-backdrop" onclick="closeGuestAuthPrompt()"></div>
        <div class="guest-auth-dialog" role="dialog" aria-modal="true" aria-labelledby="guestAuthTitle">
            <button type="button" class="guest-auth-close" onclick="closeGuestAuthPrompt()" aria-label="Bezárás">×</button>
            <h2 id="guestAuthTitle" class="guest-auth-title">Lépj be vagy regisztrálj ingyen</h2>
            <p class="guest-auth-text">Regisztráció után azonnal kapsz 1 000 pontot, és már indulhat is a játék.</p>
            <div class="guest-auth-actions"><a href="{{ route('login') }}" class="btn-primary-purple">Bejelentkezés</a><a href="{{ route('register') }}" class="btn-hero-secondary">Regisztráció</a></div>
        </div>
    </div>
@endguest

<script>
    function scrollRow(id, direction) { const row = document.getElementById(id); row?.scrollBy({ left: direction * row.clientWidth * .75, behavior: 'smooth' }); }
    function openGuestAuthPrompt() { const modal = document.getElementById('guestAuthPrompt'); modal?.classList.add('is-open'); modal?.setAttribute('aria-hidden', 'false'); }
    function closeGuestAuthPrompt() { const modal = document.getElementById('guestAuthPrompt'); modal?.classList.remove('is-open'); modal?.setAttribute('aria-hidden', 'true'); }
    document.querySelectorAll('.moderation-reason-preset').forEach((preset) => preset.addEventListener('change', () => { const input = preset.closest('form')?.querySelector('.moderation-reason-input'); if (preset.value && input) input.value = preset.value; }));
</script>
</body>
</html>
