<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Eredmény</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="auth-wrapper">

<div class="result-card">

    @if($isCorrect)
        <div class="result-icon">🎉</div>
        <h2 class="result-title-success">Helyes válasz!</h2>

        @if(($quiz['game_mode'] ?? '') === 'odds')
            <div class="reward-box-amber">
                <span class="reward-label" style="color: #92400e;">Várható halmozott nyereményed:</span>
                <span class="reward-value" style="color: #d97706;">{{ number_format($quiz['current_pot'], 0, ',', ' ') }} PT</span>
            </div>
        @else
            <div class="reward-box-green">
                <span class="reward-label" style="color: #166534;">Jóváírt nyeremény:</span>
                <span class="reward-value" style="color: #15803d;">+{{ number_format($reward, 0, ',', ' ') }} PT</span>
            </div>
        @endif
    @else
        <div class="result-icon">❌</div>
        <h2 class="result-title-danger">Sajnos hibás!</h2>

        <p style="color: #4b5563; margin-bottom: 0.5rem;">A helyes válasz ez lett volna:</p>
        <p class="correct-text-badge">
            {{ $correctText }}
        </p>

        @if(($quiz['game_mode'] ?? '') === 'odds')
            <div class="reward-box-red">
                <span class="reward-label" style="color: #991b1b;">Az Odds-ra fel! menet véget ért.</span>
                <span style="font-size: 1.25rem; font-weight: 700; color: #dc2626;">Elvesztetted a feltett {{ number_format($quiz['initial_bet'], 0, ',', ' ') }} PT-t.</span>
            </div>
        @endif
    @endif

    <a href="{{ route('quiz.next') }}" class="btn-result-next">
        ➡️ {{ ($quiz['game_mode'] ?? '') === 'odds' && !$isCorrect ? 'Összegzés megtekintése' : 'Következő kérdés' }}
    </a>

</div>

</body>
</html>
