<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Vezérlőpult</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<!-- Fejléc -->
<nav class="bg-white shadow-md py-4 px-6 mb-8">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
        <h1 class="text-2xl font-extrabold text-indigo-600">🎯 BetQuiz</h1>

        <div class="flex items-center gap-4">
            <!-- Egyenleg kijelző -->
            <div class="bg-amber-100 text-amber-800 font-bold px-4 py-2 rounded-lg border border-amber-300 shadow-sm flex items-center">
                🪙 Egyenleg: <span class="text-xl ml-2">{{ number_format(auth()->user()->points ?? 1000, 0, ',', ' ') }}</span>&nbsp;PT
            </div>

            <!-- Kijelentkezés gomb -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-600 hover:text-red-600 font-semibold transition">
                    Kijelentkezés 🚪
                </button>
            </form>
        </div>
    </div>
</nav>

<!-- Fő tartalom -->
<main class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 text-center border border-gray-100">
        <h2 class="text-3xl font-bold text-gray-800 mb-3">
            Üdvözlünk a BetQuiz-ben, {{ auth()->user()->name }}! 👋
        </h2>
        <p class="text-gray-600 text-lg mb-8">
            Jelenlegi egyenleged: <span class="font-bold text-amber-600 text-xl">{{ number_format(auth()->user()->points ?? 1000, 0, ',', ' ') }} PT</span>
        </p>

        <div class="p-6 bg-indigo-50 rounded-xl mb-8 border border-indigo-100 inline-block w-full max-w-md">
            <p class="text-indigo-900 font-medium mb-1">🎮 Készen állsz a kihívásra?</p>
            <p class="text-sm text-indigo-600">Válaszolj a kérdésekre és növeld a pontjaidat!</p>
        </div>

        <div>
            <a href="{{ route('quiz.start') }}" class="inline-flex items-center px-8 py-4 bg-indigo-600 text-white font-bold text-xl rounded-xl shadow-lg hover:bg-indigo-700 hover:scale-105 active:scale-95 transition duration-150">
                🚀 Kvíz Indítása Most
            </a>
        </div>
    </div>
</main>

</body>
</html>
