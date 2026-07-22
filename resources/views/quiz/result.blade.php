<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Eredmény</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-lg bg-white rounded-2xl shadow-xl p-8 text-center border border-gray-100">

    @if($isCorrect)
        <div class="text-6xl mb-4">🎉</div>
        <h2 class="text-3xl font-extrabold text-green-600 mb-2">Helyes válasz!</h2>

        @if(($quiz['game_mode'] ?? '') === 'odds')
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                <span class="text-sm text-amber-800 font-medium block mb-1">Várható halmozott nyereményed:</span>
                <span class="text-3xl font-extrabold text-amber-600">{{ number_format($quiz['current_pot'], 0, ',', ' ') }} PT</span>
            </div>
        @else
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                <span class="text-sm text-green-800 font-medium block mb-1">Jóváírt nyeremény:</span>
                <span class="text-3xl font-extrabold text-green-700">+{{ number_format($reward, 0, ',', ' ') }} PT</span>
            </div>
        @endif
    @else
        <div class="text-6xl mb-4">❌</div>
        <h2 class="text-3xl font-extrabold text-red-600 mb-2">Sajnos hibás!</h2>

        <p class="text-gray-600 mb-2">A helyes válasz ez lett volna:</p>
        <p class="text-lg font-bold text-gray-800 bg-gray-100 p-3 rounded-xl mb-6 inline-block">
            {{ $correctText }}
        </p>

        @if(($quiz['game_mode'] ?? '') === 'odds')
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <span class="text-sm text-red-800 font-medium block mb-1">Az Odds-ra fel! menet véget ért.</span>
                <span class="text-xl font-bold text-red-600">Elvesztetted a feltett {{ number_format($quiz['initial_bet'], 0, ',', ' ') }} PT-t.</span>
            </div>
        @endif
    @endif

    <a href="{{ route('quiz.next') }}" class="block w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg rounded-xl shadow-lg transition duration-150">
        ➡️ {{ ($quiz['game_mode'] ?? '') === 'odds' && !$isCorrect ? 'Összegzés megtekintése' : 'Következő kérdés' }}
    </a>

</div>

</body>
</html>
