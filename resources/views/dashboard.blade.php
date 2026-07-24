<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Műszerfal - BetQuiz</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body style="padding-bottom: 3rem;">

@include('layouts.navigation')

<div class="nav-container" style="padding-top: 2rem; padding-bottom: 2rem;">

    <!-- ==========================================================================
       1. ÜDVÖZLŐ FEJLÉC & EGYENLEG
       ========================================================================== -->
    <div class="purple-banner" style="margin-bottom: 2rem; display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 900; margin-bottom: 0.25rem;">Üdv újra, {{ $user->name }}! 👋</h1>
            <p style="font-size: 0.875rem; color: #e0e7ff; margin: 0; font-weight: 500;">Készen állsz egy újabb kvízre vagy saját kérdések feltöltésére?</p>
        </div>

        <div style="background-color: rgba(255, 255, 255, 0.1); backdrop-filter: blur(8px); padding: 1rem 1.5rem; border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.2); display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 1.875rem;">🪙</span>
            <div>
                <p style="font-size: 0.75rem; font-weight: 700; color: #c7d2fe; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Jelenlegi Egyenleged</p>
                <p style="font-size: 1.5rem; font-weight: 900; color: #ffffff; margin: 0;">{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</p>
            </div>
        </div>
    </div>

    <!-- ==========================================================================
       2. 🛡️ ADMIN BÍRÁLATI SZEKCIÓ (Csak Adminoknak, ha van elbírálandó kvíz)
       ========================================================================== -->
    @if(($user->isUseradmin() || $user->isHostadmin()) && isset($pendingQuizzes) && $pendingQuizzes->isNotEmpty())
        <div class="admin-review-box" style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.5rem;">⏳</span>
                    <div>
                        <h2 style="font-size: 1.125rem; font-weight: 800; color: #78350f; margin: 0;">Bírálatra Váró Kvízek ({{ $pendingQuizzes->count() }} db)</h2>
                        <p style="font-size: 0.75rem; color: #b45309; margin: 0;">Más játékosok által beküldött kvízek, amik jóváhagyásra várnak.</p>
                    </div>
                </div>
            </div>

            <div class="admin-review-grid">
                @foreach ($pendingQuizzes as $pendingQuiz)
                    <div style="padding: 1rem; border-radius: 1rem; background-color: #ffffff; border: 1px solid #fde68a; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <span style="font-weight: 800; font-size: 1rem; color: #1f2937;">{{ $pendingQuiz->title }}</span>
                            <span style="font-size: 0.75rem; color: #6b7280; margin-left: 0.5rem;">({{ is_array($pendingQuiz->category->name ?? null) ? ($pendingQuiz->category->name['hu'] ?? reset($pendingQuiz->category->name)) : ($pendingQuiz->category->name ?? 'Általános') }})</span>
                            <p style="font-size: 0.75rem; color: #4b5563; margin-top: 0.25rem; margin-bottom: 0;">
                                Készítette: <strong>{{ $pendingQuiz->creator->name ?? 'Anonim' }}</strong> • {{ $pendingQuiz->questions_count }} kérdés
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <!-- Approve Form -->
                            <form action="{{ route('admin.quizzes.approve', $pendingQuiz) }}" method="POST" style="margin: 0;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="status-badge-approved" style="border: none; cursor: pointer; padding: 0.5rem 0.75rem;">
                                    Jóváhagyás
                                </button>
                            </form>

                            <!-- Reject Form -->
                            <form action="{{ route('admin.quizzes.reject', $pendingQuiz) }}" method="POST" style="margin: 0;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="status-badge-rejected" style="border: none; cursor: pointer; padding: 0.5rem 0.75rem;">
                                    Elutasítás
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- ==========================================================================
       3. 🚀 GYORS AKCIÓK (Quick Actions)
       ========================================================================== -->
    <div class="quick-action-grid" style="margin-bottom: 2.5rem;">

        <!-- 🎮 JÁTÉK -->
        <a href="{{ route('quizzes.index') }}" class="quick-action-card">
            <div class="quick-action-icon icon-bg-indigo">
                🎮
            </div>
            <div>
                <h3 style="font-weight: 800; color: #1f2937; font-size: 1.125rem; margin: 0;">Játék Indítása</h3>
                <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 500; margin-top: 0.125rem; margin-bottom: 0;">Válassz a legnépszerűbb kvízek közül!</p>
            </div>
        </a>

        <!-- ➕ ÚJ KVÍZ -->
        <a href="{{ route('my-quizzes.create') }}" class="quick-action-card">
            <div class="quick-action-icon icon-bg-amber">
                ➕
            </div>
            <div>
                <h3 style="font-weight: 800; color: #1f2937; font-size: 1.125rem; margin: 0;">Kvíz Nyitása</h3>
                <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 500; margin-top: 0.125rem; margin-bottom: 0;">Hozz létre saját kvízt 50.000 PT-ért!</p>
            </div>
        </a>

        <!-- 📑 KÉRDÉSEIM / KVÍZEIM -->
        <a href="{{ route('my-quizzes.index') }}" class="quick-action-card">
            <div class="quick-action-icon icon-bg-emerald">
                📑
            </div>
            <div>
                <h3 style="font-weight: 800; color: #1f2937; font-size: 1.125rem; margin: 0;">Tartalmaim</h3>
                <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 500; margin-top: 0.125rem; margin-bottom: 0;">Kérdéseid és kvízeid kezelése</p>
            </div>
        </a>

    </div>

    <!-- ==========================================================================
       4. SAJÁT KVÍZEID ÁLLAPOTA (Külön felső szekció az Alkotói áttekintéshez)
       ========================================================================== -->
    <div style="margin-bottom: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 style="font-size: 1.25rem; font-weight: 800; color: #1f2937; margin: 0;">📌 Saját Kvízeid Állapota</h2>
            <a href="{{ route('my-quizzes.index') }}" style="font-size: 0.75rem; font-weight: 700; color: #4f46e5; text-decoration: none;">Saját Kvízek Kezelése →</a>
        </div>

        <div class="card-white" style="padding: 1.5rem;">
            @if($myQuizzes->isEmpty())
                <div style="text-align: center; padding: 1.5rem 0; color: #9ca3af; font-size: 0.875rem;">
                    Még nem nyitottál saját kvízt. Hozz létre egyet! 🚀
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
                                    <span class="status-badge-approved">🟢 Publikus</span>
                                @elseif($q->status === 'approved')
                                    <span class="status-badge-pending">🔵 Kérdésgyűjtés ({{ $q->questions_count }}/100)</span>
                                @elseif($q->status === 'pending')
                                    <span class="status-badge-pending">⏳ Elbírálásra vár</span>
                                @else
                                    <span class="status-badge-rejected">❌ Elutasítva</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 2.5rem;">

    <!-- ==========================================================================
       5. NETFLIX-STÍLUSÚ KVÍZ SÁVOK (7 KÜLÖNBÖZŐ BLOKK VAGY ÜRES ÁLLAPOT)
       ========================================================================== -->
    @php
        $hasAnyActiveQuiz = (
            (isset($featuredQuizzes) && $featuredQuizzes->isNotEmpty()) ||
            (isset($latestQuizzes) && $latestQuizzes->isNotEmpty()) ||
            (isset($favoriteQuizzes) && $favoriteQuizzes->isNotEmpty()) ||
            (isset($hardestQuizzes) && $hardestQuizzes->isNotEmpty()) ||
            (isset($unplayedQuizzes) && $unplayedQuizzes->isNotEmpty()) ||
            (isset($categoryFavoriteQuizzes) && $categoryFavoriteQuizzes->isNotEmpty()) ||
            (isset($popularQuizzes) && $popularQuizzes->isNotEmpty())
        );
    @endphp

    @if(!$hasAnyActiveQuiz)
        <!-- 🎈 ÜRES ÁLLAPOT VISSZAJELZÉS (Ha még nincs 100+ kérdéses publikált kvíz) -->
        <div style="text-align: center; padding: 3.5rem 1.5rem; background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 1.25rem; margin: 1rem 0;">
            <div style="font-size: 3.5rem; margin-bottom: 1rem;">🎯</div>
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #1e293b; margin-bottom: 0.5rem;">
                Jelenleg nincs elérhető aktív kvíz a rendszerben.
            </h3>
            <p style="color: #64748b; max-width: 520px; margin: 0 auto 1.5rem auto; font-size: 0.875rem; line-height: 1.5;">
                A beküldött kvízek jelenleg elbírálás alatt állnak, vagy a készítők még a kérdésgyűjtési fázisban (100 kérdésig) járnak. Hozz létre egy saját kvízt, vagy nézz vissza később!
            </p>
            <a href="{{ route('my-quizzes.create') }}" class="btn-primary-purple" style="display: inline-block; padding: 0.75rem 1.5rem; text-decoration: none; font-weight: 800; font-size: 0.875rem; border-radius: 0.75rem;">
                ➕ Új Kvíz Létrehozása (50.000 PT)
            </a>
        </div>
    @else

        <!-- 1. KIEMELT KVÍZEK -->
        @if(isset($featuredQuizzes) && $featuredQuizzes->isNotEmpty())
            <div class="netflix-row-container">
                <h3 class="row-title">⭐ Kiemelt Kvízek</h3>
                <button class="scroll-btn scroll-btn-left" onclick="scrollRow('row-featured', -1)">❮</button>
                <div class="netflix-slider" id="row-featured">
                    @foreach($featuredQuizzes as $quiz)
                        <div class="netflix-card-item">
                            @include('partials.quiz-card', ['quiz' => $quiz])
                        </div>
                    @endforeach
                </div>
                <button class="scroll-btn scroll-btn-right" onclick="scrollRow('row-featured', 1)">❯</button>
            </div>
        @endif

        <!-- 2. LEGÚJABB KVÍZEK -->
        @if(isset($latestQuizzes) && $latestQuizzes->isNotEmpty())
            <div class="netflix-row-container">
                <h3 class="row-title">🔥 Legújabb Kvízek</h3>
                <button class="scroll-btn scroll-btn-left" onclick="scrollRow('row-latest', -1)">❮</button>
                <div class="netflix-slider" id="row-latest">
                    @foreach($latestQuizzes as $quiz)
                        <div class="netflix-card-item">
                            @include('partials.quiz-card', ['quiz' => $quiz])
                        </div>
                    @endforeach
                </div>
                <button class="scroll-btn scroll-btn-right" onclick="scrollRow('row-latest', 1)">❯</button>
            </div>
        @endif

        <!-- 3. KEDVENC KVÍZEK -->
        @if(isset($favoriteQuizzes) && $favoriteQuizzes->isNotEmpty())
            <div class="netflix-row-container">
                <h3 class="row-title">❤️ Kedvenc Kvízeid</h3>
                <button class="scroll-btn scroll-btn-left" onclick="scrollRow('row-favorites', -1)">❮</button>
                <div class="netflix-slider" id="row-favorites">
                    @foreach($favoriteQuizzes as $quiz)
                        <div class="netflix-card-item">
                            @include('partials.quiz-card', ['quiz' => $quiz])
                        </div>
                    @endforeach
                </div>
                <button class="scroll-btn scroll-btn-right" onclick="scrollRow('row-favorites', 1)">❯</button>
            </div>
        @endif

        <!-- 4. LEGNEHEZEBB KVÍZEK -->
        @if(isset($hardestQuizzes) && $hardestQuizzes->isNotEmpty())
            <div class="netflix-row-container">
                <h3 class="row-title">💀 Legnehezebb Kvízek (Ahol a legtöbben elvéreztek)</h3>
                <button class="scroll-btn scroll-btn-left" onclick="scrollRow('row-hardest', -1)">❮</button>
                <div class="netflix-slider" id="row-hardest">
                    @foreach($hardestQuizzes as $quiz)
                        <div class="netflix-card-item">
                            @include('partials.quiz-card', ['quiz' => $quiz])
                        </div>
                    @endforeach
                </div>
                <button class="scroll-btn scroll-btn-right" onclick="scrollRow('row-hardest', 1)">❯</button>
            </div>
        @endif

        <!-- 5. EZZEL MÉG NEM JÁTSZOTTÁL -->
        @if(isset($unplayedQuizzes) && $unplayedQuizzes->isNotEmpty())
            <div class="netflix-row-container">
                <h3 class="row-title">🎯 Ezzel még nem játszottál</h3>
                <button class="scroll-btn scroll-btn-left" onclick="scrollRow('row-unplayed', -1)">❮</button>
                <div class="netflix-slider" id="row-unplayed">
                    @foreach($unplayedQuizzes as $quiz)
                        <div class="netflix-card-item">
                            @include('partials.quiz-card', ['quiz' => $quiz])
                        </div>
                    @endforeach
                </div>
                <button class="scroll-btn scroll-btn-right" onclick="scrollRow('row-unplayed', 1)">❯</button>
            </div>
        @endif

        <!-- 6. KATEGÓRIA FAVORIT -->
        @if(isset($categoryFavoriteQuizzes) && $categoryFavoriteQuizzes->isNotEmpty())
            <div class="netflix-row-container">
                <h3 class="row-title">🏷️ Kategória Favoritok</h3>
                <button class="scroll-btn scroll-btn-left" onclick="scrollRow('row-category', -1)">❮</button>
                <div class="netflix-slider" id="row-category">
                    @foreach($categoryFavoriteQuizzes as $quiz)
                        <div class="netflix-card-item">
                            @include('partials.quiz-card', ['quiz' => $quiz])
                        </div>
                    @endforeach
                </div>
                <button class="scroll-btn scroll-btn-right" onclick="scrollRow('row-category', 1)">❯</button>
            </div>
        @endif

        <!-- 7. MÁSOK SZERINT NÉPSZERŰ -->
        @if(isset($popularQuizzes) && $popularQuizzes->isNotEmpty())
            <div class="netflix-row-container">
                <h3 class="row-title">👑 Mások szerint népszerű</h3>
                <button class="scroll-btn scroll-btn-left" onclick="scrollRow('row-popular', -1)">❮</button>
                <div class="netflix-slider" id="row-popular">
                    @foreach($popularQuizzes as $quiz)
                        <div class="netflix-card-item">
                            @include('partials.quiz-card', ['quiz' => $quiz])
                        </div>
                    @endforeach
                </div>
                <button class="scroll-btn scroll-btn-right" onclick="scrollRow('row-popular', 1)">❯</button>
            </div>
        @endif

    @endif

</div>

<!-- JavaScript a nyilak kattintására történő sima gördítéshez -->
<script>
    function scrollRow(rowId, direction) {
        const container = document.getElementById(rowId);
        if (!container) return;

        const scrollAmount = container.clientWidth * 0.75;

        container.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }
</script>

</body>
</html>
