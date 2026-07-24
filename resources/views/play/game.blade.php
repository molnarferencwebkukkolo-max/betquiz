@extends('layouts.game')

@section('content')
    <div class="py-8" x-data="{
    timeLeft: {{ $game['time_limit'] }},
    timer: null,
    startTimer() {
        this.timer = setInterval(() => {
            if (this.timeLeft > 0) {
                this.timeLeft--;
            } else {
                clearInterval(this.timer);
                // Időnapló lejártakor automatikusan beküldi a rossz válasz státuszt / lejárást
                document.getElementById('game-form').submit();
            }
        }, 1000);
    }
}" x-init="startTimer()">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- STATS BAR (PONT, LÉPÉS, IDŐZÍTŐ) --}}
            <div class="bg-white rounded-2xl shadow-lg p-4 mb-6 flex justify-between items-center border border-gray-100">
                <div>
                    <span class="text-xs text-gray-500 font-bold uppercase block">Játékmód</span>
                    <span class="text-lg font-black text-indigo-600">
                    {{ $game['game_mode'] === 'normal' ? '🎯 Normál' : '🎲 Odds-os' }}
                </span>
                </div>

                <div>
                <span class="text-xs text-gray-500 font-bold uppercase block">
                    {{ $game['game_mode'] === 'normal' ? 'Aktuális Nyeremény' : 'Göngyölt Tét (Pot)' }}
                </span>
                    <span class="text-xl font-black text-green-600">
                    💰 {{ $game['game_mode'] === 'normal' ? $game['won_amount'] : $game['current_pot'] }} PT
                </span>
                </div>

                <div>
                    <span class="text-xs text-gray-500 font-bold uppercase block">Kérdés</span>
                    <span class="text-lg font-black text-gray-700">
                    #{{ $game['current_step'] }} {{ $game['target_count'] ? '/ ' . $game['target_count'] : '' }}
                </span>
                </div>

                <div class="text-right">
                    <span class="text-xs text-gray-500 font-bold uppercase block">Hátralévő idő</span>
                    <span class="text-2xl font-black" :class="timeLeft <= 5 ? 'text-red-600 animate-pulse' : 'text-indigo-600'" x-text="timeLeft + 's'"></span>
                </div>
            </div>

            {{-- HA DOBÓKOCKA MENTŐÖVRE VÁR A JÁTÉKOS (ROSSZ VÁLASZ UTÁN) --}}
            @if($game['awaiting_dice'])
                <div x-data="{
        rolling: false,
        diceValue: 1,
        rollDice() {
            if (this.rolling) return;
            this.rolling = true;

            let iterations = 0;
            let interval = setInterval(() => {
                this.diceValue = Math.floor(Math.random() * 6) + 1;
                iterations++;
                if (iterations > 15) {
                    clearInterval(interval);
                    document.getElementById('dice-form').submit();
                }
            }, 100);
        }
    }" class="bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-900 text-white rounded-3xl p-8 text-center shadow-2xl mb-6 border border-indigo-500/30">

                    {{-- STATUS BADGE --}}
                    <div class="mb-4 flex justify-center gap-2">
                        @if($remainingFreeRolls > 0)
                            <span class="px-3 py-1 bg-green-500/20 text-green-300 border border-green-500/40 text-xs font-black rounded-full uppercase tracking-wider">
                    🎁 Ingyenes dobás! (Mára még: {{ $remainingFreeRolls }} db)
                </span>
                        @else
                            <span class="px-3 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/40 text-xs font-black rounded-full uppercase tracking-wider">
                    💳 Ingyenes lejárva! Ára: 100 PT
                </span>
                        @endif
                    </div>

                    <h3 class="text-3xl font-black mb-2">Hoppá, a válasz helytelen volt!</h3>
                    <p class="text-indigo-200 text-sm max-w-md mx-auto mb-6">
                        Dobj 6-ost a kockával a nyereményért! <br>
                        @if($remainingFreeRolls > 0)
                            <span class="text-green-400 font-bold">Ez a dobásod még INGYENES!</span>
                        @else
                            <span class="text-amber-400 font-bold">Ennek a dobásnak az ára 100 PT!</span> (Egyenleged: {{ auth()->user()->points }} PT)
                        @endif
                    </p>

                    {{-- ANIMÁLT KOCKA --}}
                    <div class="my-6 flex justify-center items-center">
                        <div :class="rolling ? 'animate-bounce scale-110' : ''"
                             class="w-28 h-28 bg-white text-slate-900 rounded-3xl shadow-2xl flex items-center justify-center text-6xl font-black border-4 border-amber-400 transition-all transform duration-200 select-none">
                            <template x-if="diceValue === 1"><span>🎲 1</span></template>
                            <template x-if="diceValue === 2"><span>🎲 2</span></template>
                            <template x-if="diceValue === 3"><span>🎲 3</span></template>
                            <template x-if="diceValue === 4"><span>🎲 4</span></template>
                            <template x-if="diceValue === 5"><span>🎲 5</span></template>
                            <template x-if="diceValue === 6"><span class="text-green-600 scale-125 transition">🎯 6</span></template>
                        </div>
                    </div>

                    <form id="dice-form" action="{{ route('quiz.roll_dice', $quiz) }}" method="POST" class="hidden">
                        @csrf
                    </form>

                    {{-- GOMB --}}
                    <button type="button"
                            @click="rollDice()"
                            :disabled="rolling"
                            class="py-4 px-10 bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-500 hover:to-orange-600 text-slate-950 font-black text-xl rounded-2xl shadow-lg hover:shadow-orange-500/50 transition transform active:scale-95 disabled:opacity-50">
                        <span x-text="rolling ? 'KOCKA PÖRGETÉSE...' : '{{ $remainingFreeRolls > 0 ? '🎲 INGYENES GURÍTÁS!' : '💳 GURÍTÁS (100 PT)' }}'"></span>
                    </button>

                </div>
            @else
                {{-- KÉRDÉS KÁRTYA --}}
                <div class="bg-white overflow-hidden shadow-xl rounded-2xl p-8 mb-6">
                    <div class="flex justify-between items-center mb-4">
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-full uppercase">
                        Nehézség: {{ $currentQuestion->difficulty ?? 'Normál' }}
                    </span>
                        <span class="text-xs text-gray-400">ID: #{{ $currentQuestion->id }}</span>
                    </div>

                    <h2 class="text-2xl font-extrabold text-gray-800 mb-8 leading-snug">
                        {{ $currentQuestion->question_text }}
                    </h2>

                    {{-- VÁLASZ LEHETŐSÉGEK FORM --}}
                    <form id="game-form" action="{{ route('quiz.submit_answer', $quiz) }}" method="POST">
                        @csrf
                        <input type="hidden" name="question_id" value="{{ $currentQuestion->id }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                            @php
                                // Megkeressük, hogy answers vagy options néven van-e a reláció
                                $answersList = $currentQuestion->answers ?? $currentQuestion->options ?? [];
                            @endphp

                            @foreach($answersList as $index => $answer)
                                <label class="p-4 border-2 border-gray-200 hover:border-indigo-500 hover:bg-indigo-50 rounded-2xl cursor-pointer transition flex items-center gap-3 group">
                                    <input type="radio" name="selected_option" value="{{ is_object($answer) ? $answer->id : ($answer['id'] ?? $index) }}" class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-gray-300" required>
                                    <span class="font-black text-indigo-600 group-hover:text-indigo-800">{{ chr(65 + $index) }}:</span>
                                    <span class="text-gray-700 font-semibold group-hover:text-gray-900">
                {{ is_object($answer) ? $answer->option_text : ($answer['option_text'] ?? '') }}
            </span>
                                </label>
                            @endforeach
                        </div>

                        <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-lg rounded-2xl shadow-lg transition">
                            Válasz beküldése ➔
                        </button>
                    </form>
                </div>

                {{-- KISZÁLLÁS / CASH OUT GOMB --}}
                <div class="flex justify-end">
                    <form action="{{ route('quiz.cashout', $quiz) }}" method="POST" onsubmit="return confirm('Biztosan ki szeretnél szállni és felveszed az eddigi nyereményt?');">
                        @csrf
                        <button type="submit" class="py-3 px-6 bg-red-50 hover:bg-red-100 text-red-600 font-extrabold rounded-xl border border-red-200 transition">
                            🛑 Kiszállok (Nyeremény felvétele)
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
@endsection
