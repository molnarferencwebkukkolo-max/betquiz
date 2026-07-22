<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Játékmód Választó</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-xl bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
    <div class="text-center mb-6">
        <h2 class="text-3xl font-extrabold text-gray-800">🎮 Válassz Játékmódot</h2>
        <p class="text-sm text-gray-500 mt-1">Hogyan szeretnél játszani?</p>
    </div>

    <div class="bg-amber-50 p-4 rounded-xl border border-amber-200 text-center mb-6">
        <span class="text-amber-800 font-semibold text-sm">Jelenlegi egyenleged:</span>
        <div class="text-2xl font-bold text-amber-700">🪙 {{ number_format(auth()->user()->points, 0, ',', ' ') }} PT</div>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('quiz.start') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Játékmód váltó fül -->
        <div class="grid grid-cols-2 gap-2 p-1 bg-gray-100 rounded-xl">
            <button type="button" id="tab-per-question" onclick="setMode('per_question')" class="py-3 font-bold rounded-lg transition text-sm bg-white text-indigo-600 shadow">
                1️⃣ Kérdésenkénti Tét
            </button>
            <button type="button" id="tab-odds" onclick="setMode('odds')" class="py-3 font-bold rounded-lg transition text-sm text-gray-500 hover:text-gray-700">
                🔥 Odds-ra fel!
            </button>
        </div>
        <input type="hidden" name="game_mode" id="game_mode" value="per_question">

        <!-- Közös beállítások: Kategória & Nehézség -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">📂 Kategória:</label>
                <select name="category_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl font-medium focus:border-indigo-500 focus:outline-none">
                    <option value="all">🌐 Összes kategória</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">
                            {{ is_array($cat->name) ? ($cat->name['hu'] ?? reset($cat->name)) : $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">⚡ Nehézség (Odds):</label>
                <select name="difficulty" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl font-medium focus:border-indigo-500 focus:outline-none">
                    <option value="easy">Könnyű (1.3x)</option>
                    <option value="medium" selected>Közepes (1.5x)</option>
                    <option value="hard">Nehéz (2.0x)</option>
                </select>
            </div>
        </div>

        <!-- 1. KÉR DÉS EN KÉ N TI TÉT BEÁLLÍTÁSOK -->
        <div id="section-per-question" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">❓ Kérdések száma:</label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="question_count" value="3" checked class="peer hidden">
                        <div class="p-3 text-center rounded-xl border-2 border-gray-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 font-bold transition">3 kérdés</div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="question_count" value="5" class="peer hidden">
                        <div class="p-3 text-center rounded-xl border-2 border-gray-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 font-bold transition">5 kérdés</div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="question_count" value="10" class="peer hidden">
                        <div class="p-3 text-center rounded-xl border-2 border-gray-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 font-bold transition">10 kérdés</div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">💰 Tét kérdésenként (PT):</label>
                <input type="number" name="bet_per_question" min="10" value="100"
                       class="w-full px-4 py-3 text-xl font-bold border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none text-center">
            </div>
        </div>

        <!-- 2. ODDS-RA FEL! BEÁLLÍTÁSOK -->
        <div id="section-odds" class="space-y-4 hidden">
            <div class="p-4 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 leading-relaxed">
                ⚠️ <strong>Játékszabály:</strong> Minden helyes válasznál az eddigi nyereményed megszorzódik az Odds-al! Ha bármelyik kérdésnél hibázol, a teljes halmozott nyeremény elveszik és csak a feltett kezdőtéted bukod.
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">🎯 Hány kérdést vállalsz?</label>
                <select name="odds_question_count" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl font-bold text-center text-indigo-600 focus:border-indigo-500 focus:outline-none">
                    <option value="10">10 kérdés</option>
                    <option value="20">20 kérdés</option>
                    <option value="30">30 kérdés</option>
                    <option value="40">40 kérdés</option>
                    <option value="50">50 kérdés</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">💵 Feltett kezdő tét (PT):</label>
                <input type="number" name="odds_total_bet" min="10" value="100"
                       class="w-full px-4 py-3 text-xl font-bold border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none text-center">
            </div>
        </div>

        <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg rounded-xl shadow-lg transition duration-150">
            🚀 Menet Indítása
        </button>

        <a href="{{ route('dashboard') }}" class="block text-center text-sm text-gray-500 hover:text-gray-700 mt-2">
            Mégse, vissza a műszerfalra
        </a>
    </form>
</div>

<script>
    function setMode(mode) {
        document.getElementById('game_mode').value = mode;

        const tabPer = document.getElementById('tab-per-question');
        const tabOdds = document.getElementById('tab-odds');
        const secPer = document.getElementById('section-per-question');
        const secOdds = document.getElementById('section-odds');

        if (mode === 'per_question') {
            tabPer.className = "py-3 font-bold rounded-lg transition text-sm bg-white text-indigo-600 shadow";
            tabOdds.className = "py-3 font-bold rounded-lg transition text-sm text-gray-500 hover:text-gray-700";
            secPer.classList.remove('hidden');
            secOdds.classList.add('hidden');
        } else {
            tabOdds.className = "py-3 font-bold rounded-lg transition text-sm bg-white text-indigo-600 shadow";
            tabPer.className = "py-3 font-bold rounded-lg transition text-sm text-gray-500 hover:text-gray-700";
            secOdds.classList.remove('hidden');
            secPer.classList.add('hidden');
        }
    }
</script>

</body>
</html>
