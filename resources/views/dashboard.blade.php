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

    <!-- Üdvözlő Fejléc & Egyenleg -->
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

    <!-- 🛡️ ADMIN BÍRÁLATI SZEKCIÓ (Csak Adminoknak jelenik meg, ha van elbírálandó kvíz) -->
    @if($user->isUseradmin() && $pendingQuizzes->isNotEmpty())
        <div class="admin-review-box">
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
                    <div class="flex items-center justify-between border-b pb-4">
                        <div>
                            <span class="font-semibold text-lg">{{ $pendingQuiz->title }}</span>
                            <span class="text-sm text-gray-500 ml-2">({{ $pendingQuiz->category->name[app()->getLocale()] ?? $pendingQuiz->category->name['hu'] }})</span>
                            <p class="text-sm text-gray-600">{{ __('Készítette:') }} {{ $pendingQuiz->creator->name }}</p>
                        </div>
                        <div class="flex space-x-2">
                            <!-- Approve Form -->
                            <form action="{{ route('admin.quizzes.approve', $pendingQuiz) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-sm">
                                    {{ __('Jóváhagyás') }}
                                </button>
                            </form>

                            <!-- Reject Form -->
                            <form action="{{ route('admin.quizzes.reject', $pendingQuiz) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm">
                                    {{ __('Elutasítás') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 🚀 GYORS AKCIÓK (Quick Actions) -->
    <div class="quick-action-grid">

        <!-- 🎮 JÁTÉK -->
        <a href="{{ route('quiz.bet') }}" class="quick-action-card">
            <div class="quick-action-icon icon-bg-indigo">
                🎮
            </div>
            <div>
                <h3 style="font-weight: 800; color: #1f2937; font-size: 1.125rem; margin: 0;">Játék Indítása</h3>
                <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 500; margin-top: 0.125rem; margin-bottom: 0;">Válassz a legnépszerűbb kvízek közül!</p>
            </div>
        </a>

        <!-- ➕ ÚJ KVÍZ -->
        <a href="{{ route('quizzes.create') }}" class="quick-action-card">
            <div class="quick-action-icon icon-bg-amber">
                ➕
            </div>
            <div>
                <h3 style="font-weight: 800; color: #1f2937; font-size: 1.125rem; margin: 0;">Kvíz Nyitása</h3>
                <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 500; margin-top: 0.125rem; margin-bottom: 0;">Hozz létre saját kvízt 50.000 PT-ért!</p>
            </div>
        </a>

        <!-- 📑 KÉRDÉSEIM / KVÍZEIM -->
        <a href="{{ route('questions.index') }}" class="quick-action-card">
            <div class="quick-action-icon icon-bg-emerald">
                📑
            </div>
            <div>
                <h3 style="font-weight: 800; color: #1f2937; font-size: 1.125rem; margin: 0;">Tartalmaim</h3>
                <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 500; margin-top: 0.125rem; margin-bottom: 0;">Kérdéseid és kvízeid kezelése</p>
            </div>
        </a>

    </div>

    <div class="dashboard-main-grid">

        <!-- 🌟 KIEMELT / LEGÚJABB KVÍZEK (2 oszlop) -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #1f2937; margin: 0;">🔥 Kiemelt Kvízek</h2>
                <a href="{{ route('quiz.bet') }}" style="font-size: 0.75rem; font-weight: 700; color: #4f46e5; text-decoration: none;">Összes Kvíz →</a>
            </div>

            @if($featuredQuizzes->isEmpty())
                <div class="card-white" style="text-align: center; color: #9ca3af;">
                    Még nincsenek elérhető kvízek. Legyél te az első, aki nyit egyet! 🚀
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem;">
                    @foreach($featuredQuizzes as $quiz)
                        @php
                            $cName = is_array($quiz->category->name ?? null)
                                ? ($quiz->category->name['hu'] ?? reset($quiz->category->name))
                                : ($quiz->category->name ?? 'Általános');
                        @endphp
                        <div class="quiz-card">
                            <div>
                                <div class="quiz-card-cover" style="height: 8rem;">
                                    @if($quiz->cover_image)
                                        <img src="{{ asset('storage/' . $quiz->cover_image) }}" alt="{{ $quiz->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @endif
                                    <span class="badge-category-float">
                                        {{ $cName }}
                                    </span>
                                </div>
                                <div class="quiz-card-body">
                                    <h3 class="quiz-card-title" style="font-size: 1rem;">{{ $quiz->title }}</h3>
                                    <p class="quiz-card-desc">{{ $quiz->description ?? 'Nincs leírás.' }}</p>
                                </div>
                            </div>
                            <div class="quiz-card-footer" style="display: flex; align-items: center; justify-content: space-between; padding-top: 0.75rem;">
                                <span style="font-size: 0.75rem; color: #9ca3af; font-weight: 700;">❓ {{ $quiz->questions_count }} kérdés</span>
                                <a href="{{ route('quiz.setup', $quiz->id) }}" class="btn-primary-purple" style="padding: 0.5rem 1rem; font-size: 0.75rem; text-decoration: none;">
                                    🎮 Játék Indítása
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 🛠️ SAJÁT KVÍZEID ÁLLAPOTA (1 oszlop) -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #1f2937; margin: 0;">📌 Saját Kvízeid</h2>
                <a href="{{ route('questions.index') }}" style="font-size: 0.75rem; font-weight: 700; color: #4f46e5; text-decoration: none;">Kezelés →</a>
            </div>

            <div class="card-white" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                @if($myQuizzes->isEmpty())
                    <div style="text-align: center; padding: 1.5rem 0; color: #9ca3af; font-size: 0.875rem;">
                        Még nem nyitottál saját kvízt.
                    </div>
                @else
                    @foreach($myQuizzes as $q)
                        <div style="padding: 1rem; border-radius: 1rem; background-color: #f9fafb; border: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <h4 style="font-weight: 800; color: #1f2937; font-size: 0.875rem; margin: 0;">{{ $q->title }}</h4>
                                <p style="font-size: 0.75rem; color: #9ca3af; font-weight: 700; margin-top: 0.125rem; margin-bottom: 0;">
                                    {{ $q->questions_count }}/100 kérdés
                                </p>
                            </div>

                            <div>
                                @if($q->status === 'approved')
                                    <span class="status-badge-approved">🟢 Publikus</span>
                                @elseif($q->status === 'pending')
                                    <span class="status-badge-pending">🔵 Gyűjtés</span>
                                @else
                                    <span class="status-badge-rejected">❌ Elutasítva</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif

                <a href="{{ route('quizzes.create') }}" class="btn-secondary-gray" style="text-align: center; font-size: 0.75rem; margin-top: 0.5rem;">
                    ➕ Új Kvíz Nyitása (50.000 PT)
                </a>
            </div>
        </div>

    </div>

</div>

</body>
</html>
