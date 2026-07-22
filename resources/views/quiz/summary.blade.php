<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Összegzés</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 text-center border border-gray-100">

    @if(($quiz['game_mode'] ?? '') === 'odds')
        @if(!($quiz['failed'] ?? false) && $quiz['correct_answers'] === $quiz['total_questions'])
            <div class="text-6xl mb-4">🏆🔥</div>
            <h2 class="text-3xl font-extrabold text-green-600 mb-2">JACKPOT!</h2>
            <p class="text-gray-500 mb-6">Minden kérdésre helyesen válaszoltál az Odds-ra fel! menetben!</p>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                <span class="text-amber-900 font-medium block mb-1">Nyert összeg:</span>
                <span class="text-3xl font-bold text-amber-600">+{{ number_format($quiz['current_pot'], 0, ',', ' ') }} PT</span>
            </div>
        @else
            <div class="text-6xl mb-4">💥</div>
            <h2 class="text-3xl font-extrabold text-red-600 mb-2">Sajnos nem sikerült!</h2>
            <p class="text-gray-500 mb-6">Elrontottad az egyik kérdést, így a halmozott nyeremény elszállt.</p>
        @endif
    @else
        <div class="text-6xl mb-4">🏆</div>
        <h2 class="text-3xl font-extrabold text-gray-800 mb-2">Menet Vége!</h2>
        <p class="text-gray-500 mb-6">Íme a teljesítményed összefoglalója:</p>

        <div class="space-y-3 mb-6">
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                <span class="text-gray-600 font-medium">Helyes válaszok:</span>
                <span class="font-bold text-xl text-indigo-600">{{ $quiz['correct_answers'] }} / {{ $quiz['total_questions'] }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-amber-50 rounded-xl">
                <span class="text-amber-900 font-medium">Összesen nyert pont:</span>
                <span class="font-bold text-xl text-amber-700">+{{ number_format($quiz['total_won'], 0, ',', ' ') }} PT</span>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        <a href="{{ route('quiz.bet') }}" class="block w-full py-3 bg-indigo-600 text-white font-bold rounded-xl shadow hover:bg-indigo-700 transition">
            🔄 Új menet indítása
        </a>
        <a href="{{ route('dashboard') }}" class="block w-full py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition">
            🏠 Vissza a műszerfalra
        </a>
    </div>
</div>

</body>
</html>
