<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Összegzés</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="auth-wrapper">

<div class="summary-card">

    @if(($quiz['game_mode'] ?? '') === 'odds')
        @if(!($quiz['failed'] ?? false) && $quiz['correct_answers'] === $quiz['total_questions'])
            <div class="summary-icon">🏆🔥</div>
            <h2 class="summary-title-success">JACKPOT!</h2>
            <p class="summary-desc">Minden kérdésre helyesen válaszoltál az Odds-ra fel! menetben!</p>

            <div class="reward-box-amber">
                <span class="reward-label" style="color: #78350f;">Nyert összeg:</span>
                <span class="reward-value" style="color: #d97706;">+{{ number_format($quiz['current_pot'], 0, ',', ' ') }} PT</span>
            </div>
        @else
            <div class="summary-icon">💥</div>
            <h2 class="summary-title-danger">Sajnos nem sikerült!</h2>
            <p class="summary-desc">Elrontottad az egyik kérdést, így a halmozott nyeremény elszállt.</p>
        @endif
    @else
        <div class="summary-icon">🏆</div>
        <h2 class="summary-title-neutral">Menet Vége!</h2>
        <p class="summary-desc">Íme a teljesítményed összefoglalója:</p>

        <div class="summary-stat-group">
            <div class="summary-stat-item">
                <span style="color: #4b5563; font-weight: 500; font-size: 0.875rem;">Helyes válaszok:</span>
                <span style="font-weight: 800; font-size: 1.125rem; color: #4f46e5;">{{ $quiz['correct_answers'] }} / {{ $quiz['total_questions'] }}</span>
            </div>
            <div class="summary-stat-item-gold">
                <span style="color: #78350f; font-weight: 500; font-size: 0.875rem;">Összesen nyert pont:</span>
                <span style="font-weight: 800; font-size: 1.125rem; color: #b45309;">+{{ number_format($quiz['total_won'], 0, ',', ' ') }} PT</span>
            </div>
        </div>
    @endif

    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        <a href="{{ route('quiz.bet') }}" class="btn-result-next" style="padding: 0.75rem;">
            🔄 Új menet indítása
        </a>
        <a href="{{ route('dashboard') }}" class="btn-secondary-gray">
            🏠 Vissza a műszerfalra
        </a>
    </div>
</div>

</body>
</html>
