<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Műszerfal - BetQuiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Üdvözlő Fejléc & Egyenleg -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-3xl p-8 text-white shadow-xl mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <h1 class="text-3xl font-black">Üdv újra, {{ $user->name }}! 👋</h1>
            <p class="text-indigo-100 mt-1 text-sm font-medium">Készen állsz egy újabb kvízre vagy saját kérdések feltöltésére?</p>
        </div>

        <div class="bg-white/10 backdrop-blur-md px-6 py-4 rounded-2xl border border-white/20 flex items-center gap-4">
            <span class="text-3xl">🪙</span>
            <div>
                <p class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Jelenlegi Egyenleged</p>
                <p class="text-2xl font-black text-white">{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</p>
            </div>
        </div>
    </div>

    <!-- 🚀 GYORS AKCIÓK (Quick Actions) -->

    <!-- 🛡️ ADMIN BÍRÁLATI SZEKCIÓ (Csak Adminoknak jelenik meg, ha van elbírálandó kvíz) -->
    @if(Auth::user()->isUseradmin() && $pendingQuizzes->isNotEmpty())
        <div class="bg-amber-50 border-2 border-amber-200 rounded-3xl p-6 mb-10 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⏳</span>
                    <div>
                        <h2 class="text-lg font-extrabold text-amber-900">Bírálatra Váró Kvízek ({{ $pendingQuizzes->count() }} db)</h2>
                        <p class="text-xs text-amber-700 font-medium">Más játékosok által beküldött kvízek, amik jóváhagyásra várnak.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($pendingQuizzes as $pQuiz)
                    @php
                        $pCatName = is_array($pQuiz->category->name ?? null)
                            ? ($pQuiz->category->name['hu'] ?? reset($pQuiz->category->name))
                            : ($pQuiz->category->name ?? 'Általános');
                    @endphp
                    <div class="bg-white rounded-2xl p-5 border border-amber-200 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2.5 py-1 rounded-full uppercase">
                                    {{ $pCatName }}
                                </span>
                                <span class="text-xs text-gray-400 font-bold">👤 {{ $pQuiz->creator->name ?? 'Ismeretlen' }}</span>
                            </div>
                            <h3 class="font-extrabold text-gray-800 text-base mb-1 line-clamp-1">{{ $pQuiz->title }}</h3>
                            <p class="text-xs text-gray-500 line-clamp-2 mb-3">{{ $pQuiz->description ?? 'Nincs leírás.' }}</p>
                            <p class="text-xs text-gray-400 font-bold mb-4">❓ {{ $pQuiz->questions_count }} kérdés van benne</p>
                        </div>

                        <!-- BÍRÁLATI GOMBOK -->
                        <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
                            <!-- Elfogadás -->
                            <form action="{{ route('quizzes.approve', $pQuiz->id) }}" method="POST" class="w-1/2">
                                @csrf
                                <button type="submit" onclick="return confirm('Biztosan jóváhagyod és publikálod ezt a kvízt?')"
                                        class="w-full py-2 bg-green-600 hover:bg-green-700 text-white font-extrabold text-xs rounded-xl shadow transition text-center">
                                    ✅ Elfogad
                                </button>
                            </form>

                            <!-- Elutasítás -->
                            <form action="{{ route('quizzes.reject', $pQuiz->id) }}" method="POST" class="w-1/2">
                                @csrf
                                <button type="submit" onclick="return confirm('Biztosan elutasítod ezt a kvízt?')"
                                        class="w-full py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow transition text-center">
                                    ❌ Elutasít
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

        <!-- 🎮 JÁTÉK -->
        <a href="{{ route('quiz.bet') }}" class="group bg-white rounded-3xl p-6 shadow-sm hover:shadow-md border border-gray-100 transition flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl group-hover:scale-110 transition">
                🎮
            </div>
            <div>
                <h3 class="font-extrabold text-gray-800 text-lg group-hover:text-indigo-600 transition">Játék Indítása</h3>
                <p class="text-xs text-gray-400 font-medium mt-0.5">Válassz a legnépszerűbb kvízek közül!</p>
            </div>
        </a>

        <!-- ➕ ÚJ KVÍZ -->
        <a href="{{ route('quizzes.create') }}" class="group bg-white rounded-3xl p-6 shadow-sm hover:shadow-md border border-gray-100 transition flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl group-hover:scale-110 transition">
                ➕
            </div>
            <div>
                <h3 class="font-extrabold text-gray-800 text-lg group-hover:text-amber-600 transition">Kvíz Nyitása</h3>
                <p class="text-xs text-gray-400 font-medium mt-0.5">Hozz létre saját kvízt 50.000 PT-ért!</p>
            </div>
        </a>

        <!-- 📑 KÉRDÉSEIM / KVÍZEIM -->
        <a href="{{ route('questions.index') }}" class="group bg-white rounded-3xl p-6 shadow-sm hover:shadow-md border border-gray-100 transition flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl group-hover:scale-110 transition">
                📑
            </div>
            <div>
                <h3 class="font-extrabold text-gray-800 text-lg group-hover:text-emerald-600 transition">Tartalmaim</h3>
                <p class="text-xs text-gray-400 font-medium mt-0.5">Kérdéseid és kvízeid kezelése</p>
            </div>
        </a>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- 🌟 KIEMELT / LEGÚJABB KVÍZEK (2 oszlop) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-extrabold text-gray-800">🔥 Kiemelt Kvízek</h2>
                <a href="{{ route('quiz.bet') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">Összes Kvíz →</a>
            </div>

            @if($featuredQuizzes->isEmpty())
                <div class="bg-white rounded-3xl p-8 text-center text-gray-400 border border-gray-100">
                    Még nincsenek elérhető kvízek. Legyél te az első, aki nyit egyet! 🚀
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach($featuredQuizzes as $quiz)
                        @php
                            $cName = is_array($quiz->category->name ?? null)
                                ? ($quiz->category->name['hu'] ?? reset($quiz->category->name))
                                : ($quiz->category->name ?? 'Általános');
                        @endphp
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition overflow-hidden flex flex-col justify-between">
                            <div>
                                <div class="h-32 bg-gradient-to-r from-indigo-500 to-purple-600 relative">
                                    @if($quiz->cover_image)
                                        <img src="{{ asset('storage/' . $quiz->cover_image) }}" alt="{{ $quiz->title }}" class="w-full h-full object-cover">
                                    @endif
                                    <span class="absolute top-3 left-3 bg-white/90 backdrop-blur-md text-indigo-800 text-xs font-extrabold px-3 py-1 rounded-full shadow-sm">
                                            {{ $cName }}
                                        </span>
                                </div>
                                <div class="p-5">
                                    <h3 class="font-extrabold text-gray-800 text-base line-clamp-1 mb-1">{{ $quiz->title }}</h3>
                                    <p class="text-xs text-gray-400 line-clamp-2">{{ $quiz->description ?? 'Nincs leírás.' }}</p>
                                </div>
                            </div>
                            <div class="p-5 pt-0 border-t border-gray-50 flex items-center justify-between mt-auto">
                                <span class="text-xs text-gray-400 font-bold">❓ {{ $quiz->questions_count }} kérdés</span>
                                <!-- ✅ EGYBŐL a Tétbeállító képernyőre visz: -->
                                <a href="{{ route('quiz.setup', $quiz->id) }}"
                                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow transition">
                                    🎮 Játék Indítása
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 🛠️ SAJÁT KVÍZEID ÁLLAPOTA (1 oszlop) -->
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-extrabold text-gray-800">📌 Saját Kvízeid</h2>
                <a href="{{ route('questions.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">Kezelés →</a>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                @if($myQuizzes->isEmpty())
                    <div class="text-center py-6 text-gray-400 text-sm">
                        Még nem nyitottál saját kvízt.
                    </div>
                @else
                    @foreach($myQuizzes as $q)
                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-between">
                            <div>
                                <h4 class="font-extrabold text-gray-800 text-sm line-clamp-1">{{ $q->title }}</h4>
                                <p class="text-xs text-gray-400 font-bold mt-0.5">
                                    {{ $q->questions_count }}/100 kérdés
                                </p>
                            </div>

                            <div>
                                @if($q->status === 'approved')
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-green-100 text-green-700">🟢 Publikus</span>
                                @elseif($q->status === 'pending')
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-blue-100 text-blue-700">🔵 Gyűjtés</span>
                                @else
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700">❌ Elutasítva</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif

                <a href="{{ route('quizzes.create') }}" class="block w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold text-center rounded-2xl text-xs transition mt-2">
                    ➕ Új Kvíz Nyitása (50.000 PT)
                </a>
            </div>
        </div>

    </div>

</div>

</body>
</html>
