<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Műszerfal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<!-- Új, egységes navigációs menü -->
@include('layouts.navigation')

<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl shadow-xl p-10 border border-gray-100 text-center">

        <h1 class="text-3xl font-extrabold text-gray-800 mb-2">
            Üdvözlünk a BetQuiz-ben, {{ Auth::user()->name }}! 👋
        </h1>

        <p class="text-lg font-semibold text-gray-600 mb-8">
            Jelenlegi egyenleged: <span class="text-amber-600 font-extrabold">{{ number_format($user->points ?? $user->balance ?? 0) }} PT</span>
        </p>

        <div class="bg-indigo-50/60 rounded-2xl p-6 mb-8 max-w-md mx-auto border border-indigo-100">
            <p class="font-bold text-indigo-900 mb-1">🎮 Készen állsz a kihívásra?</p>
            <p class="text-sm text-indigo-700">Válaszolj a kérdésekre és növeld a pontjaidat!</p>
        </div>

        <div class="flex flex-col sm:flex-row justify-center gap-4 max-w-md mx-auto">
            <a href="{{ route('quizzes.index') }}" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-lg rounded-2xl shadow-lg hover:shadow-indigo-200 transition">
                🚀 Kvíz Választása / Indítása
            </a>

            <a href="{{ route('questions.create') }}" class="w-full py-4 bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-lg rounded-2xl shadow-lg hover:shadow-amber-200 transition">
                📝 Kérdés Hozzáadása / Importálása
            </a>
        </div>

    </div>
</div>

</body>
</html>
