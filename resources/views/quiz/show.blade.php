<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Játék Beállítása - {{ $quiz->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

<div class="max-w-2xl mx-auto px-4 py-10">

    <a href="{{ route('dashboard') }}" class="inline-flex items-center text-xs font-bold text-gray-500 hover:text-indigo-600 mb-6 transition">
        ← Vissza a Műszerfalra
    </a>

    <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">

        <div class="text-center mb-8">
                <span class="inline-block bg-indigo-50 text-indigo-700 text-xs font-extrabold px-3 py-1 rounded-full uppercase mb-2">
                    {{ is_array($quiz->category->name ?? null) ? ($quiz->category->name['hu'] ?? reset($quiz->category->name)) : ($quiz->category->name ?? 'Általános') }}
                </span>
            <h1 class="text-2xl font-black text-gray-800 leading-tight mb-2">{{ $quiz->title }}</h1>
            <p class="text-xs text-gray-500">{{ $quiz->description ?? 'Nincs külön leírás megadva.' }}</p>
        </div>

        <!-- TÉT, NEHÉZSÉG ÉS JÁTÉKMÓD FORM -->
        <form action="{{ route('quiz.start', $quiz->id) }}" method="POST" class="space-y-6">
            @csrf

            <!-- 1. JÁTÉKMÓD -->
            <div>
                <label class="block text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-3">1. Válassz Játékmódot</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="relative border-2 border-gray-200 rounded-2xl p-4 flex items-center gap-3 cursor-pointer hover:border-indigo-500 transition has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/50">
                        <input type="radio" name="mode" value="bet" checked class="text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <h4 class="font-extrabold text-gray-800 text-sm">🎯 Fixed Tétes Mód</h4>
                            <p class="text-[11px] text-gray-400 mt-0.5">Fix tét alapon játszol.</p>
                        </div>
                    </label>

                    <label class="relative border-2 border-gray-200 rounded-2xl p-4 flex items-center gap-3 cursor-pointer hover:border-indigo-500 transition has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/50">
                        <input type="radio" name="mode" value="odds" class="text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <h4 class="font-extrabold text-gray-800 text-sm">🎲 Odds-alapú Mód</h4>
                            <p class="text-[11px] text-gray-400 mt-0.5">Szorzók alapján nyerhetsz.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 2. NEHÉZSÉGI SZINT ÉS SZORZÓ -->
            <div>
                <label class="block text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-3">2. Válassz Nehézséget (Nyereményszorzó)</label>
                <div class="grid grid-cols-3 gap-3">

                    <!-- Könnyű -->
                    <label class="relative border-2 border-gray-200 rounded-2xl p-3 text-center cursor-pointer hover:border-green-500 transition has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                        <input type="radio" name="difficulty" value="easy" class="sr-only">
                        <span class="text-xl block mb-1">🟢</span>
                        <span class="font-extrabold text-gray-800 text-xs block">Könnyű</span>
                        <span class="text-xs font-black text-green-600">1.3x szorzó</span>
                    </label>

                    <!-- Közepes -->
                    <label class="relative border-2 border-gray-200 rounded-2xl p-3 text-center cursor-pointer hover:border-amber-500 transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
                        <input type="radio" name="difficulty" value="medium" checked class="sr-only">
                        <span class="text-xl block mb-1">🟡</span>
                        <span class="font-extrabold text-gray-800 text-xs block">Közepes</span>
                        <span class="text-xs font-black text-amber-600">1.5x szorzó</span>
                    </label>

                    <!-- Nehéz -->
                    <label class="relative border-2 border-gray-200 rounded-2xl p-3 text-center cursor-pointer hover:border-red-500 transition has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                        <input type="radio" name="difficulty" value="hard" class="sr-only">
                        <span class="text-xl block mb-1">🔴</span>
                        <span class="font-extrabold text-gray-800 text-xs block">Nehéz</span>
                        <span class="text-xs font-black text-red-600">2.0x szorzó</span>
                    </label>

                </div>
            </div>

            <!-- KÉRDÉSSZÁM VÁLASZTÓ (Alapból rejtve: 'hidden') -->
            <div id="question-count-section" class="mt-4 hidden transition-all">
                <label class="block text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-2">
                    Hány kérdésre szeretnél válaszolni?
                </label>
                <select name="question_count" class="w-full px-4 py-3 rounded-2xl border border-gray-200 font-bold text-gray-800 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="5" selected>5 Kérdés</option>
                    <option value="10">10 Kérdés</option>
                    <option value="15">15 Kérdés</option>
                    <option value="20">20 Kérdés</option>
                </select>
            </div>

            <!-- 🔮 JAVASCRIPT: Csak Odds mód esetén jeleníti meg a kérdésszámot -->
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const modeInputs = document.querySelectorAll('input[name="mode"]');
                    const questionSection = document.getElementById('question-count-section');

                    function toggleQuestionCount() {
                        const selectedMode = document.querySelector('input[name="mode"]:checked')?.value;
                        if (selectedMode === 'odds') {
                            questionSection.classList.remove('hidden');
                        } else {
                            questionSection.classList.add('hidden');
                        }
                    }

                    modeInputs.forEach(input => {
                        input.addEventListener('change', toggleQuestionCount);
                    });

                    // Betöltéskor is lefut
                    toggleQuestionCount();
                });
            </script>

            <!-- 3. TÉT MEGADÁSA -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-xs font-extrabold text-gray-400 uppercase tracking-wider">3. Megadott Tét (PT)</label>
                    <span class="text-xs font-bold text-gray-400">Egyenleged: <strong class="text-indigo-600">{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</strong></span>
                </div>

                <input type="number" name="bet_amount" min="100" max="{{ $user->points ?? 1000 }}" value="1000" step="100" required
                       class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-black text-gray-800 text-lg">
            </div>

            <!-- INDÍTÁS GOMB -->
            <button type="submit" class="w-full py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-black rounded-2xl shadow-lg transition text-base text-center">
                🚀 Tét Rakása & Játék Indítása!
            </button>

        </form>

    </div>

</div>

</body>
</html>
