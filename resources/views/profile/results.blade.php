<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eredményeim - KwizzGo</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="results-page">
@include('layouts.navigation')

<main class="results-shell">
    <header class="results-hero">
        <div>
            <a href="{{ route('profile.show') }}" class="results-back-link">← Vissza a profilhoz</a>
            <h1><span aria-hidden="true">🏆</span> Eredményeim</h1>
            <p>Személyes KwizzGo-teljesítményed az eddig rögzített első válaszaid alapján.</p>
        </div>
        <div class="results-hero-art" aria-hidden="true">📊</div>
    </header>

    <section class="results-summary-grid" aria-label="Összesített eredmények">
        <article><span class="results-metric-icon purple">◎</span><small>Megválaszolt kérdések</small><strong>{{ number_format($answerCount, 0, ',', ' ') }}</strong><em>Összes rögzített válasz</em></article>
        <article><span class="results-metric-icon green">✓</span><small>Helyes válaszok</small><strong>{{ number_format($correctAnswerCount, 0, ',', ' ') }}</strong><em>Jól megválaszolt kérdések</em></article>
        <article><span class="results-metric-icon blue">↗</span><small>Találati arány</small><strong>{{ $accuracy }}%</strong><em>Teljes játékidőszak</em></article>
        <article><span class="results-metric-icon amber">ϟ</span><small>Kitöltött kvízek</small><strong>{{ (int) ($totals->quiz_count ?? 0) }}</strong><em>Különböző kvízek</em></article>
    </section>

    <section class="results-panel creator-results-panel">
        <div class="results-section-heading split-heading">
            <div>
                <span class="results-eyebrow">✨ Alkotói eredmények</span>
                <h2>Mások ennyi kérdésedre válaszoltak</h2>
                <p>Minden más felhasználótól rögzített első kérdésválasz után 1 PT alkotói jutalom jár. A saját játékaid nem számítanak bele.</p>
            </div>
            <div class="creator-metric-grid">
                <article><small>Kitöltött kérdések</small><strong>{{ number_format($creatorAnsweredQuestions, 0, ',', ' ') }}</strong></article>
                <article><small>Alkotói jutalom</small><strong>+{{ number_format($creatorRewardPoints, 0, ',', ' ') }} PT</strong></article>
            </div>
        </div>

        @if($creatorResults->isNotEmpty())
            <div class="results-table-wrap">
                <table class="results-table">
                    <thead><tr><th>Saját kvíz</th><th>Kitöltött kérdések</th><th>Játékosok</th><th>Jutalom</th></tr></thead>
                    <tbody>
                    @foreach($creatorResults as $creatorResult)
                        <tr><td><strong>{{ $creatorResult->title }}</strong></td><td>{{ $creatorResult->answered_questions }}</td><td>{{ $creatorResult->players }}</td><td class="creator-reward-value">+{{ $creatorResult->answered_questions }} PT</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="results-empty compact">Ha mások válaszolnak a saját kvízeid kérdéseire, az alkotói eredmények itt jelennek meg.</div>
        @endif
    </section>

    <section class="results-panel weekly-panel">
        <div class="results-section-heading">
            <span class="results-eyebrow">📅 Heti aktivitás</span>
            <h2>Utolsó 12 hét</h2>
            <p>A hetek hétfőtől vasárnapig tartanak.</p>
        </div>
        <div class="weekly-bars" aria-label="Heti válaszok grafikonja">
            @php($maxWeeklyAnswers = max(1, (int) $weeklyResults->max('answers')))
            @foreach($weeklyResults->reverse()->values() as $week)
                <div class="weekly-bar-item" title="{{ $week['answers'] }} válasz, {{ $week['accuracy'] }}% találati arány">
                    <span>{{ $week['answers'] }}</span>
                    <div class="weekly-bar-track"><i style="height: {{ max(5, (int) round(($week['answers'] / $maxWeeklyAnswers) * 100)) }}%"></i></div>
                    <small>{{ $week['week_start']->format('m.d.') }}</small>
                </div>
            @endforeach
        </div>
        <div class="results-table-wrap">
            <table class="results-table">
                <thead><tr><th>Időszak</th><th>Válaszok</th><th>Helyes</th><th>Találati arány</th></tr></thead>
                <tbody>
                @foreach($weeklyResults as $week)
                    <tr><td><strong>{{ $week['week_start']->format('Y. m. d.') }} – {{ $week['week_end']->format('m. d.') }}</strong></td><td>{{ $week['answers'] }}</td><td>{{ $week['correct_answers'] }}</td><td><span class="accuracy-pill {{ $week['answers'] === 0 ? 'muted' : '' }}">{{ $week['accuracy'] }}%</span></td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="results-panel quiz-results-panel">
        <div class="results-section-heading">
            <span class="results-eyebrow">🎮 Részletes teljesítmény</span>
            <h2>Eredmények kvízenként</h2>
            <p>A legutóbb játszott kvízek kerülnek előre.</p>
        </div>
        @if($quizResults->isEmpty())
            <div class="results-empty"><span>🎯</span><strong>Még nincs rögzített eredményed</strong><p>Játssz egy kvízt, és itt megjelenik a teljesítményed!</p><a href="{{ route('quizzes.index') }}">Kvízek böngészése →</a></div>
        @else
            <div class="results-table-wrap flush">
                <table class="results-table">
                    <thead><tr><th>Kvíz</th><th>Válaszok</th><th>Helyes</th><th>Találati arány</th><th></th></tr></thead>
                    <tbody>
                    @foreach($quizResults as $result)
                        @php($quizAccuracy = $result->answers > 0 ? (int) round(($result->correct_answers / $result->answers) * 100) : 0)
                        <tr><td><strong>{{ $result->title }}</strong></td><td>{{ $result->answers }}</td><td>{{ $result->correct_answers }}</td><td><span class="accuracy-pill">{{ $quizAccuracy }}%</span></td><td class="results-action-cell"><a href="{{ route('quiz.setup', ['quiz' => $result->slug ?: $result->id]) }}">Játék →</a></td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="results-pagination">{{ $quizResults->links() }}</div>
        @endif
    </section>
</main>
</body>
</html>
