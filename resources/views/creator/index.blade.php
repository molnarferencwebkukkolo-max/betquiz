<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kvízek - BetQuiz</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Fejléc -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800">
                📋 {{ Auth::user()->isUseradmin() ? 'Összes Kvíz (Admin)' : 'Saját Kvízeim' }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Nyiss új kvízt, töltsd fel kérdésekkel és kövesd nyomon a statisztikáit!
            </p>
        </div>

        <a href="{{ route('my-quizzes.create') }}" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-2xl shadow-lg transition flex items-center gap-2">
            ➕ Új Kvíz Nyitása (50k PT)
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded-2xl font-bold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Kvíz Kártyák Rácsa -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($quizzes as $quiz)
            @php
                $qCount = $quiz->questions_count;
                $percent = min(100, round(($qCount / 100) * 100));
            @endphp

            <div class="bg-white rounded-3xl shadow-md border border-gray-100 p-6 flex flex-col justify-between hover:shadow-xl transition">
                <div>
                    <!-- Státusz Badge + Kategória -->
                    <div class="flex justify-between items-center mb-3">
                            <span class="text-xs font-bold px-3 py-1 rounded-full bg-indigo-50 text-indigo-700">
                                {{ is_array($quiz->category->name ?? null) ? ($quiz->category->name['hu'] ?? reset($quiz->category->name)) : ($quiz->category->name ?? 'Általános') }}
                            </span>

                        @if($quiz->status === 'published' || $quiz->status === 'approved')
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-green-100 text-green-700">🟢 Publikus</span>
                        @elseif($quiz->status === 'draft_approved')
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-blue-100 text-blue-700">🔵 Kérdésfeltöltés</span>
                        @elseif($quiz->status === 'pending')
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">⏳ Bírálatra vár</span>
                        @else
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700">❌ Elutasítva</span>
                        @endif
                    </div>

                    <h2 class="text-xl font-bold text-gray-800 mb-2">
                        <a href="{{ route('quizzes.show', $quiz->id) }}" class="hover:text-indigo-600 transition">
                            {{ $quiz->title }}
                        </a>
                    </h2>
                    <p class="text-sm text-gray-500 line-clamp-2 mb-4">{{ $quiz->description ?? 'Nincs leírás.' }}</p>
                </div>

                <div>
                    <!-- Haladási sáv (Progress Bar) -->
                    <div class="mb-4">
                        <div class="flex justify-between text-xs font-extrabold text-gray-600 mb-1">
                            <span>Kérdések állása:</span>
                            <span>{{ $qCount }} / 100 DB ({{ $percent }}%)</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 border overflow-hidden">
                            <div class="bg-indigo-600 h-3 rounded-full transition-all duration-500" style="width: {{ $percent }}%;"></div>
                        </div>
                    </div>

                    <!-- Műveleti Gombok -->
                    <div class="pt-3 border-t flex justify-between items-center text-xs">
                            <span class="text-gray-400 font-semibold">
                                Készítő: {{ $quiz->creator->name ?? 'Rendszer' }}
                            </span>

                        <!-- 🎯 ITT A LÉNYEG: Direkt kattintható gomb a részletezéshez -->
                        <a href="{{ route('quizzes.show', $quiz->id) }}" class="px-3 py-1.5 bg-indigo-600 text-white font-bold rounded-xl shadow hover:bg-indigo-700 transition flex items-center gap-1">
                            👁️ Megtekintés & Statisztika →
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-3xl p-12 text-center text-gray-500 border">
                <p class="text-xl font-bold mb-2">Még nincs megjeleníthető kvíz!</p>
                <p class="text-sm mb-6">Nyiss egyet a fenti gombra kattintva!</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $quizzes->links() }}
    </div>

</div>

</body>
</html>
