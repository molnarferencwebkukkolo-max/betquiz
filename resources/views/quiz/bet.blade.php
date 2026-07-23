<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Játék Indítása - BetQuiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Fejléc & Egyenleg -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800">🎮 Válassz Kvízt és Játssz!</h1>
            <p class="text-gray-500 text-sm mt-1">Teszteld a tudásod, tegyél meg tétet és gyűjts pontokat!</p>
        </div>

        <!-- Egyenleg kártya -->
        <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-3">
            <span class="text-2xl">🪙</span>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase">Egyenleged</p>
                <p class="text-xl font-extrabold text-indigo-600">{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</p>
            </div>
        </div>
    </div>

    <!-- 🔍 Szűrő és Kereső Sáv -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-8">
        <form action="{{ route('quiz.bet') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <!-- Kereső -->
            <div class="md:col-span-2">
                <label class="block text-xs font-extrabold text-gray-400 uppercase mb-1">Keresés</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Keresés kvíz címe vagy leírása alapján..."
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-semibold text-gray-800 text-sm">
            </div>

            <!-- Kategória szűrő -->
            <div>
                <label class="block text-xs font-extrabold text-gray-400 uppercase mb-1">Kategória</label>
                <select name="category_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-semibold text-gray-800 text-sm">
                    <option value="all">Minden kategória</option>
                    @foreach($categories as $cat)
                        @php
                            $cName = is_array($cat->name) ? ($cat->name['hu'] ?? reset($cat->name)) : $cat->name;
                        @endphp
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Rendezés & Gomb -->
            <div class="flex items-end gap-2">
                <div class="w-full">
                    <label class="block text-xs font-extrabold text-gray-400 uppercase mb-1">Rendezés</label>
                    <select name="sort" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-semibold text-gray-800 text-sm">
                        <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Legújabbak</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Légtrégiebbek</option>
                    </select>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-xl shadow transition text-sm">
                    Szűrés
                </button>
            </div>

        </form>
    </div>

    <!-- 🎴 KVÍZ KÁRTYÁK GRID -->
    @if($quizzes->isEmpty())
        <div class="bg-white rounded-3xl p-12 text-center border border-gray-100">
            <p class="text-4xl mb-3">🔍</p>
            <h3 class="text-lg font-extrabold text-gray-800 mb-1">Nem található kvíz</h3>
            <p class="text-sm text-gray-500">Próbálj meg más keresési feltételt megadni!</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($quizzes as $quiz)
                @php
                    $catName = is_array($quiz->category->name ?? null)
                        ? ($quiz->category->name['hu'] ?? reset($quiz->category->name))
                        : ($quiz->category->name ?? 'Általános');
                @endphp

                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition overflow-hidden flex flex-col justify-between">
                    <div>
                        <!-- Fejléckép vagy Alapértelmezett borító -->
                        <div class="h-36 w-full bg-gradient-to-r from-indigo-500 to-purple-600 relative overflow-hidden">
                            @if($quiz->cover_image)
                                <img src="{{ asset('storage/' . $quiz->cover_image) }}" alt="{{ $quiz->title }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-white/20 font-black text-5xl select-none">
                                    BETQUIZ
                                </div>
                            @endif

                            <!-- Kategória badge -->
                            <span class="absolute top-3 left-3 bg-white/90 backdrop-blur-md text-indigo-800 text-xs font-extrabold px-3 py-1 rounded-full shadow-sm">
                                    {{ $catName }}
                                </span>
                        </div>

                        <!-- Tartalom -->
                        <div class="p-5">
                            <h3 class="font-extrabold text-gray-800 text-lg leading-snug line-clamp-2 mb-2">
                                {{ $quiz->title }}
                            </h3>
                            <p class="text-xs text-gray-500 line-clamp-2 mb-4">
                                {{ $quiz->description ?? 'Nincs külön leírás megadva ehhez a kvízhez.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Kártya Lábléc & Indítás -->
                    <div class="p-5 pt-0 border-t border-gray-50 mt-auto">
                        <div class="flex items-center justify-between text-xs font-bold text-gray-400 py-3">
                            <span>❓ {{ $quiz->questions_count }} kérdés</span>
                            <span>👤 {{ $quiz->creator->name ?? 'Rendszer' }}</span>
                        </div>

                        <!-- ❌ ROSSZ VOLT: href="{{ route('quizzes.show', $quiz->id) }}" -->

                        <!-- ✅ JÓ: Közvetlenül a játékra mutató hivatkozás: -->
                        <a href="{{ route('quiz.bet', ['quiz_id' => $quiz->id]) }}" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-2xl shadow transition text-center block text-sm">
                            🎮 Játék Indítása
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Lapozó -->
        <div class="mt-8">
            {{ $quizzes->links() }}
        </div>
    @endif

</div>

</body>
</html>
