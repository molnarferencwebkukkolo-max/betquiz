<!DOCTYPE html>
<html lang="hu">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Kvízek - KwizzGo</title><link rel="stylesheet" href="{{ asset('css/app-custom.css') }}"></head>
<body class="quiz-catalog-page">
@include('layouts.navigation')
@php
    $hasActiveFilters = request()->filled('search') || (request()->filled('category_id') && request('category_id') !== 'all') || request('sort') === 'oldest';
@endphp

<main class="catalog-shell">
    <header class="catalog-hero">
        <div><span class="catalog-eyebrow">Fedezd fel a KwizzGo világát</span><h1>Válassz kvízt és <em>játssz!</em></h1><p>Teszteld a tudásod, vállalj kihívásokat és gyűjts minél több pontot.</p></div>
        <div class="catalog-balance"><span>◆</span><p>Egyenleged<strong>{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</strong></p></div>
    </header>

    <section class="catalog-filter-panel">
        <form action="{{ route('quizzes.index') }}" method="GET">
            <label class="catalog-search"><span>⌕</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Keress cím, leírás vagy címke alapján…"></label>
            <label><span>Kategória</span><select name="category_id"><option value="all">Minden kategória</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->icon }} {{ $category->translated_name }}</option>@endforeach</select></label>
            <label><span>Rendezés</span><select name="sort"><option value="latest" @selected(request('sort') !== 'oldest')>Legújabbak elöl</option><option value="oldest" @selected(request('sort') === 'oldest')>Legrégebbiek elöl</option></select></label>
            <button type="submit" class="catalog-filter-button">Szűrés</button>
            @if($hasActiveFilters)<a class="catalog-clear-button" href="{{ route('quizzes.index') }}" title="Szűrők törlése">×</a>@endif
        </form>
    </section>

    <div class="catalog-heading-row"><div><h2>Elérhető kvízek</h2><p>{{ $quizzes->total() }} találat</p></div>@if($hasActiveFilters)<span>Aktív szűrés</span>@endif</div>

    @if($quizzes->isEmpty())
        <section class="catalog-empty"><span>⌕</span><h2>Nem találtunk kvízt</h2><p>Próbálj másik keresést vagy töröld a beállított szűrőket.</p>@if($hasActiveFilters)<a href="{{ route('quizzes.index') }}">Összes kvíz megtekintése</a>@endif</section>
    @else
        <section class="catalog-quiz-grid">
            @foreach($quizzes as $quiz)
                <article class="catalog-quiz-card">
                    <a href="{{ route('quiz.setup', $quiz) }}" class="catalog-card-cover {{ $quiz->cover_image ? 'has-image' : '' }}" @if($quiz->cover_image) style="background-image:url('{{ asset('storage/'.$quiz->cover_image) }}')" @endif>
                        <span class="catalog-cover-glow"></span>
                        @unless($quiz->cover_image)<strong>?</strong>@endunless
                        <span class="catalog-category-badge">{{ $quiz->category->icon }} {{ $quiz->category->translated_name ?? 'Általános' }}</span>
                        <span class="catalog-question-badge">{{ $quiz->questions_count }} kérdés</span>
                    </a>
                    <div class="catalog-card-content">
                        <div class="catalog-card-author"><span>{{ mb_strtoupper(mb_substr($quiz->creator->username ?? $quiz->creator->name ?? 'R', 0, 1)) }}</span><p>{{ $quiz->creator->username ?? $quiz->creator->name ?? 'Rendszer' }}</p></div>
                        <h3><a href="{{ route('quiz.setup', $quiz) }}">{{ $quiz->title }}</a></h3>
                        <p>{{ $quiz->description ?? 'Nincs külön leírás megadva ehhez a kvízhez.' }}</p>
                        @if($quiz->tags->isNotEmpty())<div class="catalog-tags">@foreach($quiz->tags->take(3) as $tag)<span>#{{ $tag->name }}</span>@endforeach</div>@endif
                    </div>
                    <footer>
                        <div class="catalog-card-stats"><span title="Összes válasz">▤ {{ number_format($quiz->totalAnswersCount(), 0, ',', ' ') }}</span><span title="Helyes válaszok">✓ {{ number_format($quiz->correctAnswersCount(), 0, ',', ' ') }}</span></div>
                        <a href="{{ route('quiz.setup', $quiz) }}">Játék indítása <span>→</span></a>
                    </footer>
                </article>
            @endforeach
        </section>
        <div class="catalog-pagination">{{ $quizzes->links() }}</div>
    @endif
</main>
</body></html>
