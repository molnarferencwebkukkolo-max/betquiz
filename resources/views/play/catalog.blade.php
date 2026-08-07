<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Játék indítása - KwizzGo</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body style="padding-bottom: 3rem;">

@include('layouts.navigation')

<div class="nav-container" style="padding-top: 2rem; padding-bottom: 2rem;">
    @php
        $hasActiveFilters = request()->filled('search')
            || (request()->filled('category_id') && request('category_id') !== 'all')
            || request('sort') === 'oldest';
    @endphp

    <div style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <div>
            <h1 class="q-title">Válassz kvízt és játssz!</h1>
            <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.25rem;">Teszteld a tudásod, tegyél meg tétet és gyűjts pontokat.</p>
        </div>

        <div class="balance-card-header">
            <span style="font-size: 1.5rem;">PT</span>
            <div>
                <p style="font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin: 0;">Egyenleged</p>
                <p style="font-size: 1.25rem; font-weight: 800; color: #4f46e5; margin: 0;">{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</p>
            </div>
        </div>
    </div>

    <div class="filter-card">
        <form action="{{ route('quizzes.index') }}" method="GET">
            <div class="filter-grid">
                <div>
                    <label class="form-label-uppercase">Keresés</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Keresés kvíz címe vagy leírása alapján..."
                           class="form-control-custom w-100" style="font-size: 0.875rem;">
                </div>

                <div>
                    <label class="form-label-uppercase">Kategória</label>
                    <select name="category_id" class="form-select-custom w-100" style="font-size: 0.875rem;">
                        <option value="all">Minden kategória</option>
                        @foreach($categories as $cat)
                            @php
                                $cName = is_array($cat->name) ? ($cat->name['hu'] ?? reset($cat->name)) : $cat->name;
                            @endphp
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
                    <div style="width: 100%;">
                        <label class="form-label-uppercase">Rendezés</label>
                        <select name="sort" class="form-select-custom w-100" style="font-size: 0.875rem;">
                            <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Legújabbak</option>
                            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Legrégebbiek</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary-purple" style="padding: 0.75rem 1.25rem; font-size: 0.875rem;">
                        Szűrés
                    </button>
                    @if($hasActiveFilters)
                        <a href="{{ route('quizzes.index') }}"
                           title="Szűrés törlése"
                           aria-label="Szűrés törlése"
                           style="height: 2.85rem; width: 2.85rem; flex: 0 0 2.85rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; border: 1px solid #e5e7eb; background: #ffffff; color: #6b7280; text-decoration: none; font-size: 1.25rem; font-weight: 900; box-shadow: 0 8px 20px rgba(15,23,42,0.06); transition: all 0.15s ease;">
                            ×
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    @if($quizzes->isEmpty())
        <div class="quiz-empty-card">
            <h3 style="font-size: 1.125rem; font-weight: 800; color: #1f2937; margin-bottom: 0.25rem;">Nem található kvíz</h3>
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Próbálj meg más keresési feltételt megadni.</p>
        </div>
    @else
        <div class="quiz-grid">
            @foreach($quizzes as $quiz)
                @php
                    $catName = is_array($quiz->category->name ?? null)
                        ? ($quiz->category->name['hu'] ?? reset($quiz->category->name))
                        : ($quiz->category->name ?? 'Általános');
                @endphp

                <div class="quiz-card">
                    <div>
                        <div class="quiz-card-cover">
                            @if($quiz->cover_image)
                                <img src="{{ asset('storage/' . $quiz->cover_image) }}" alt="{{ $quiz->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.2); font-weight: 900; font-size: 2.5rem; user-select: none;">
                                    KWIZZGO
                                </div>
                            @endif

                            <span class="badge-category-float">{{ $catName }}</span>
                        </div>

                        <div class="quiz-card-body">
                            <h3 class="quiz-card-title">{{ $quiz->title }}</h3>
                            <p class="quiz-card-desc">{{ $quiz->description ?? 'Nincs külön leírás megadva ehhez a kvízhez.' }}</p>

                            @if($quiz->tags->isNotEmpty())
                                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; margin-top: 0.75rem;">
                                    @foreach($quiz->tags->take(5) as $tag)
                                        <span style="font-size: 0.7rem; font-weight: 800; color: #4f46e5; background: #eef2ff; border-radius: 9999px; padding: 0.2rem 0.5rem;">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="quiz-card-footer">
                        <div class="quiz-card-meta">
                            <span>{{ $quiz->questions_count }} kérdés</span>
                            <span>{{ $quiz->creator->name ?? 'Rendszer' }}</span>
                            <span>{{ number_format($quiz->totalAnswersCount(), 0, ',', ' ') }} válasz / {{ number_format($quiz->correctAnswersCount(), 0, ',', ' ') }} helyes</span>
                        </div>

                        <a href="{{ route('quiz.setup', $quiz) }}" class="btn-start-quiz">
                            Játék indítása
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top: 2rem;">
            {{ $quizzes->links() }}
        </div>
    @endif
</div>

</body>
</html>
