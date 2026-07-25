@extends('layouts.game')

@section('content')
    <div class="py-12" x-data="{
        mode: 'normal',
        userPoints: {{ auth()->user()->points ?? 1000 }},
        bet: 50,
        time: 60,
        difficulty: 'mixed',
        questionCount: {{ $remainingQuestionsCount >= 30 ? 30 : ($remainingQuestionsCount >= 20 ? 20 : 10) }}
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-2">🎮 {{ $quiz->title }} — Játék beállítása</h3>
                <p class="text-gray-600 mb-6">{{ $quiz->description }}</p>

                {{-- Kvíz infó sáv --}}
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-8 grid grid-cols-2 md:grid-cols-4 gap-4 items-center">
                    <div>
                        <span class="text-xs font-semibold text-indigo-900 uppercase block">Kategória</span>
                        <span class="text-base font-bold text-indigo-700 flex items-center gap-1">
                            @if(!empty($quiz->category->icon))
                                <i class="fas {{ $quiz->category->icon }}"></i>
                            @endif
                            {{ $quiz->category->translated_name ?? 'Általános' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-indigo-900 uppercase block">Készítő</span>
                        <span class="text-base font-bold text-indigo-700">
                            {{ $quiz->creator->name ?? 'Ismeretlen' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-indigo-900 uppercase block">Összes kérdés</span>
                        <span class="text-base font-bold text-indigo-700">
                            {{ $totalQuestionsCount ?? $quiz->questions()->count() }} db
                        </span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-indigo-900 uppercase block">Kitöltetlen kérdés</span>
                        <span class="text-base font-bold text-green-600">
                            {{ $remainingQuestionsCount ?? $quiz->questions()->count() }} db
                        </span>
                    </div>
                </div>

                {{-- 🎮 INTERAKCIÓS GOMBOK (Egymás melletti, független formok) --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-8 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <div class="flex items-center gap-3">
                        {{-- 1. KEDVENC GOMB --}}
                        @if(!$isDisliked)
                            <form action="{{ route('quiz.toggle-favorite', $quiz->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-sm {{ $isFavorite ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100' }}">
                                    <span>{{ $isFavorite ? '❤️ Mégsem kedvenc' : '🤍 Kedvencekhez' }}</span>
                                </button>
                            </form>
                        @endif

                        {{-- 2. DISLIKE GOMB --}}
                        @if(!$isFavorite)
                            <form action="{{ route('quiz.toggle-dislike', $quiz->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-sm {{ $isDisliked ? 'bg-gray-800 text-white hover:bg-gray-900' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-100' }}">
                                    <span>{{ $isDisliked ? '👎 Nem tetszik (Visszavonás)' : '👎 Nem tetszik' }}</span>
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- 3. KVÍZ FELÉLESZTÉSE GOMB --}}
                    @if($answeredCount > 0)
                        <form action="{{ route('quiz.reset-answers', $quiz->id) }}" method="POST" onsubmit="return confirm('Biztosan feléleszted a kvízt? Ez {{ $resetCost }} PT-be kerül!')">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl transition flex items-center gap-2 shadow-sm">
                                <span>⚡ Kvíz felélesztése ({{ $answeredCount }} kérdés = {{ $resetCost }} PT)</span>
                            </button>
                        </form>
                    @endif
                </div>

                {{-- 🚀 FŐ JÁTÉKINDÍTÓ FORM --}}
                <form action="{{ route('quiz.start_play', $quiz) }}" method="POST">
                    @csrf

                    {{-- 1. JÁTÉKMÓD VÁLASZTÁS --}}
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Játékmód:</label>
                        <div class="grid grid-cols-2 gap-4">
                            <button type="button"
                                    @click="mode = 'normal'; bet = 50"
                                    :class="mode === 'normal' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600'"
                                    class="p-4 border-2 rounded-xl text-left transition font-semibold">
                                <div class="text-lg">🎯 Normál Játékmód</div>
                                <div class="text-xs font-normal mt-1 opacity-80">Bármikor kiszállhatsz, fix alapszorzókkal növelheted a pontjaidat.</div>
                            </button>

                            <button type="button"
                                    @click="mode = 'odds'; bet = 10"
                                    :class="mode === 'odds' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600'"
                                    class="p-4 border-2 rounded-xl text-left transition font-semibold">
                                <div class="text-lg">🎲 Odds-os Játékmód</div>
                                <div class="text-xs font-normal mt-1 opacity-80">Kamatos-kamat elv. Kiszálláskor az alaptét elvész!</div>
                            </button>
                        </div>
                        <input type="hidden" name="game_mode" :value="mode">
                    </div>

                    {{-- 2. TÉT MEGADÁSA --}}
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Tét (Pont):
                            <span class="text-indigo-600 font-extrabold" x-text="bet + ' PT'"></span>
                        </label>

                        {{-- Normál Tét --}}
                        <template x-if="mode === 'normal'">
                            <div>
                                <input type="number" name="bet_points" x-model.number="bet" min="50" :max="userPoints"
                                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3 border">
                                <span class="text-xs text-gray-500 mt-1 block">Min: 50 PT | Max: Összes zsetonod (<span x-text="userPoints"></span> PT)</span>
                            </div>
                        </template>

                        {{-- Odds Tét --}}
                        <template x-if="mode === 'odds'">
                            <div>
                                <input type="number" name="bet_points" x-model.number="bet" min="10" max="100"
                                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3 border">
                                <span class="text-xs text-gray-500 mt-1 block">Min: 10 PT | Max: 100 PT</span>
                            </div>
                        </template>
                    </div>

                    {{-- 3. ODDS-OS KÉRDEZÉSSZÁM (Csak Odds-os módnál) --}}
                    <div class="mb-6" x-show="mode === 'odds'">
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Kérdések száma:
                            <span class="text-xs font-normal text-gray-500">(Még megválaszolható: {{ $remainingQuestionsCount }} db)</span>
                        </label>

                        <div class="grid grid-cols-3 gap-4">
                            <template x-for="count in [10, 20, 30]">
                                <button type="button"
                                        @click="if ({{ $remainingQuestionsCount }} >= count) questionCount = count"
                                        :disabled="{{ $remainingQuestionsCount }} < count"
                                        :class="{
                                            'bg-indigo-600 text-white': questionCount === count && {{ $remainingQuestionsCount }} >= count,
                                            'bg-gray-100 text-gray-700 hover:bg-gray-200': questionCount !== count && {{ $remainingQuestionsCount }} >= count,
                                            'bg-gray-100 text-gray-400 opacity-40 cursor-not-allowed border border-dashed border-gray-300': {{ $remainingQuestionsCount }} < count
                                        }"
                                        class="py-2.5 rounded-xl font-bold transition flex flex-col items-center justify-center"
                                        x-text="count + ' kérdés'">
                                </button>
                            </template>
                        </div>

                        @if($remainingQuestionsCount < 10)
                            <p class="text-xs text-red-500 font-semibold mt-2">
                                ⚠️ Ebben a kvízben kevesebb mint 10 új kérdés maradt ({{ $remainingQuestionsCount }} db), így az Odds mód jelenleg nem indítható.
                            </p>
                        @endif

                        <input type="hidden" name="question_count" :value="questionCount">
                    </div>

                    {{-- 4. IDŐKORLÁT --}}
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Válaszadási idő (Kérdésenként):</label>
                        <div class="grid grid-cols-3 gap-4">
                            <button type="button" @click="time = 60" :class="time === 60 ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600'" class="p-3 border-2 rounded-xl text-center font-bold transition">
                                60 sec <span class="block text-xs font-normal text-gray-500">(Alap szorzó: 1.0x)</span>
                            </button>
                            <button type="button" @click="time = 30" :class="time === 30 ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600'" class="p-3 border-2 rounded-xl text-center font-bold transition">
                                30 sec <span class="block text-xs font-normal text-indigo-600">(+1.5x Módosító)</span>
                            </button>
                            <button type="button" @click="time = 10" :class="time === 10 ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600'" class="p-3 border-2 rounded-xl text-center font-bold transition">
                                10 sec <span class="block text-xs font-normal text-indigo-600">(+2.0x Módosító)</span>
                            </button>
                        </div>
                        <input type="hidden" name="time_limit" :value="time">
                    </div>

                    {{-- 5. NEHÉZSÉG BEÁLLÍTÁSA --}}
                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nehézségi szint:</label>
                        <select name="difficulty" x-model="difficulty" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3 border">
                            <option value="mixed">🔀 Vegyes (Default)</option>
                            <option value="easy">🟢 Csak Könnyű (1.3x)</option>
                            <option value="medium">🟡 Csak Közepes (1.5x)</option>
                            <option value="hard">🔴 Csak Nehéz (2.0x)</option>
                        </select>
                    </div>

                    {{-- INDÍTÓ GOMBOK --}}
                    <div class="flex gap-4">
                        <a href="{{ route('dashboard') }}" class="w-1/3 py-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-extrabold rounded-2xl transition text-center block">
                            ⬅️ Mégse
                        </a>
                        <button type="submit" class="w-2/3 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-2xl transition shadow-lg text-center block text-lg">
                            🚀 Játék Indítása!
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
