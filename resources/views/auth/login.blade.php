<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Bejelentkezés</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
    <h2 class="text-3xl font-extrabold text-center text-gray-800 mb-2">🎯 BetQuiz</h2>
    <p class="text-sm text-center text-gray-500 mb-6">Jelentkezz be a játék folytatásához!</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">E-mail cím</label>
            <input id="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none" type="email" name="email" value="{{ old('email') }}" required autofocus />
        </div>

        <!-- Password -->
        <div class="mb-6">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Jelszó</label>
            <input id="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none" type="password" name="password" required />
        </div>

        <div class="flex items-center justify-between">
            <a class="text-sm text-indigo-600 hover:underline font-medium" href="{{ route('register') }}">
                Regisztráció ➔
            </a>

            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition">
                Bejelentkezés
            </button>
        </div>
    </form>
</div>

</body>
</html>
