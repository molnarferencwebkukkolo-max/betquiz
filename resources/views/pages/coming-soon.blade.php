<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - BetQuiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

@include('layouts.navigation')

<div class="max-w-3xl mx-auto px-4 py-16 text-center">
    <div class="bg-white rounded-3xl shadow-xl p-10 border border-gray-100">
        <div class="text-6xl mb-4">🚀</div>
        <h1 class="text-3xl font-extrabold text-gray-800 mb-3">{{ $title }}</h1>
        <p class="text-gray-600 text-lg mb-8">{{ $subtitle }}</p>

        <span class="inline-block px-4 py-2 bg-indigo-50 text-indigo-700 font-bold text-sm rounded-full border border-indigo-100">
                Fejlesztés alatt (Coming Soon)
            </span>

        <div class="mt-8">
            <a href="{{ route('dashboard') }}" class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-xl shadow hover:bg-indigo-700 transition">
                Vissza a Játékhoz
            </a>
        </div>
    </div>
</div>

</body>
</html>
