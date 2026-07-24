@extends('layouts.game')

@section('content')
    <div class="py-8" x-data="{
        timeLeft: {{ $game['time_limit'] }},
        timer: null,
        startTimer() {
            @if(empty($game['awaiting_decision']) && empty($game['awaiting_dice']) && empty($game['awaiting_time_travel']))
                this.timer = setInterval(() => {
                    if (this.timeLeft > 0) {
                        this.timeLeft--;
                    } else {
                        clearInterval(this.timer);

                        // ⏱️ IDŐ LEJÁRT: Kiszedjük a 'required' attribútumot, hogy üresen is beküldhesse
                        document.querySelectorAll('input[name=selected_option]').forEach(el => el.removeAttribute('required'));

                        // Automatikusan beküldjük a formot
                        let form = document.getElementById('game-form');
                        if (form) {
                            HTMLFormElement.prototype.submit.call(form);
                        }
                    }
                }, 1000);
            @endif
        }
    }" x-init="startTimer()">

        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- STATS BAR (JÁTÉKMÓD, NYEREMÉNY, LÉPÉS, IDŐZÍTŐ) --}}
            <div class="bg-white rounded-2xl shadow-lg p-4 mb-6 flex justify-between items-center border border-gray-100">
                <div>
                    <span class="text-xs text-gray-500 font-bold uppercase block">Játékmód</span>
                    <span class="text-lg font-black text-indigo-600">
                        {{ $game['game_mode'] === 'normal' ? '🎯 Normál' : '🎲 Odds-os' }}
                    </span>
                </div>

                <div>
                    <span class="text-xs text-gray-500 font-bold uppercase block">
                        {{ $game['game_mode'] === 'normal' ? 'Utolsó kör nyereménye' : 'Göngyölt Tét (Pot)' }}
                    </span>
                    <span class="text-xl font-black text-green-600">
                        💰 {{ $game['game_mode'] === 'normal' ? ($game['won_amount'] ?? 0) : $game['current_pot'] }} PT
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

            {{-- 1. ESET: NORMÁL MÓD HELYES VÁLASZ UTÁNI DÖNTÉSI KÉPERNYŐ --}}
            @if(!empty($game['awaiting_decision']))
                <div class="bg-gradient-to-br from-emerald-900 via-teal-950 to-slate-900 text-white rounded-3xl p-8 text-center shadow-2xl mb-6 border border-emerald-500/30">

                    <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 animate-bounce">
                        🎉
                    </div>

                    <h3 class="text-3xl font-black mb-2">HELYES VÁLASZ!</h3>
                    <p class="text-emerald-200 text-lg mb-4">
                        Ebben a körben nyertél: <strong class="text-amber-400 font-black text-2xl">+{{ $game['won_amount'] }} PT-t</strong>!
                    </p>

                    <div class="bg-slate-900/60 rounded-2xl p-4 mb-8 max-w-md mx-auto border border-emerald-500/20">
                        <p class="text-slate-300 text-sm">
                            A nyereményt azonnal jóváírtuk az egyenlegeden! <br>
                            Jelenlegi egyenleged: <strong class="text-amber-400 text-lg font-bold">{{ auth()->user()->points }} PT</strong>
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center max-w-lg mx-auto">
                        {{-- KISZÁLLÁS / BEFEJEZÉS --}}
                        <form action="{{ route('quiz.cashout', $quiz) }}" method="POST" class="w-full sm:w-auto">
                            @csrf
                            <button type="submit" class="w-full py-4 px-8 bg-slate-800 hover:bg-slate-700 text-rose-300 font-bold rounded-2xl border border-rose-500/30 transition transform active:scale-95 shadow-lg">
                                🛑 Befejezem a játékot
                            </button>
                        </form>

                        {{-- FOLYTATÁS A BEÁLLÍTOTT TÉTTEL --}}
                        <form action="{{ route('quiz.next_question', $quiz) }}" method="POST" class="w-full sm:w-auto">
                            @csrf
                            <button type="submit" class="w-full py-4 px-8 bg-gradient-to-r from-emerald-400 to-teal-500 hover:from-emerald-500 hover:to-teal-600 text-slate-950 font-black text-lg rounded-2xl shadow-lg hover:shadow-emerald-500/30 transition transform active:scale-95">
                                🚀 Következő kérdés (Tét: {{ $game['initial_bet'] }} PT)
                            </button>
                        </form>
                    </div>

                </div>

                {{-- 2. ESET: ROSSZ VÁLASZ UTÁN ➔ KOCKADOBÁS MENTŐÖV --}}
            @elseif(!empty($game['awaiting_dice']))
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
                                let form = document.getElementById('dice-form');
                                if (form) {
                                    HTMLFormElement.prototype.submit.call(form);
                                }
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

                    <h3 class="text-3xl font-black mb-2">Hoppá, helytelen válasz!</h3>
                    <p class="text-indigo-200 text-sm max-w-md mx-auto mb-6">
                        Dobj 6-ost a kockával a nyereményért!
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

                {{-- 3. ESET: IDŐLEJÁRÁS UTÁN ➔ DOKI IDŐUGRÁSA (88 MPH MENTŐÖV) --}}
            @elseif(!empty($game['awaiting_time_travel']))
                @php
                    $freeTravelsUsed = auth()->user()->lifetime_free_time_travels_used ?? 0;
                    $remainingTravels = max(0, 3 - $freeTravelsUsed);
                @endphp

                <div x-data="{
                    traveling: false,
                    speed: 0,
                    triggerTimeTravel() {
                        if (this.traveling) return;
                        this.traveling = true;

                        let interval = setInterval(() => {
                            if (this.speed < 88) {
                                this.speed += 4;
                            } else {
                                clearInterval(interval);
                                let form = document.getElementById('time-travel-form');
                                if (form) {
                                    HTMLFormElement.prototype.submit.call(form);
                                }
                            }
                        }, 40);
                    }
                }" class="bg-gradient-to-br from-slate-950 via-amber-950 to-slate-900 text-white rounded-3xl p-8 text-center shadow-2xl mb-6 border-2 border-amber-500/40 relative overflow-hidden">

                    <div class="mb-4 flex justify-center gap-2">
                        @if($remainingTravels > 0)
                            <span class="px-4 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/50 text-xs font-black rounded-full uppercase tracking-wider animate-bounce">
                                ⚡ INGYENES IDŐUGRÁS! (Még hátralévő ingyenesek: {{ $remainingTravels }}/3 db)
                            </span>
                        @else
                            <span class="px-4 py-1 bg-red-500/20 text-red-300 border border-red-500/50 text-xs font-black rounded-full uppercase tracking-wider">
                                💳 ÁRA: 100 PT (Egyenleged: {{ auth()->user()->points }} PT)
                            </span>
                        @endif
                    </div>

                    <h3 class="text-3xl font-black mb-2 text-amber-400 font-mono">⏱️ KIFUTOTTÁL AZ IDŐBŐL!</h3>
                    <p class="text-gray-300 text-sm max-w-lg mx-auto mb-6">
                        A <strong class="text-amber-400">Fluxuskondenzátor</strong> segítségével visszapörgetheted az órát a kérdés elejére!
                    </p>

                    <div class="my-6 max-w-xs mx-auto bg-black border-4 border-gray-800 rounded-2xl p-4 shadow-inner flex flex-col items-center justify-center font-mono">
                        <span class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-1">SEBESSÉGMÉRŐ</span>
                        <div class="text-5xl font-black tracking-widest" :class="speed >= 88 ? 'text-amber-400 animate-pulse' : 'text-red-600'">
                            <span x-text="speed"></span> <span class="text-xl">MPH</span>
                        </div>
                    </div>

                    <form id="time-travel-form" action="{{ route('quiz.time_travel', $quiz) }}" method="POST" class="hidden">
                        @csrf
                    </form>

                    <button type="button"
                            @click="triggerTimeTravel()"
                            :disabled="traveling"
                            class="py-4 px-10 bg-gradient-to-r from-amber-500 via-orange-500 to-yellow-500 hover:from-amber-600 text-slate-950 font-black text-xl rounded-2xl shadow-xl transition transform active:scale-95 disabled:opacity-50">
                        <span x-text="traveling ? '⚡ VISSZAÚT A MÚLTBA...' : '🚗 VISSZA A MÚLTBA (ÓRA ÚJRAINDÍTÁSA)'"></span>
                    </button>
                </div>

                {{-- 4. ESET: AKTÍV KÉRDÉS KÁRTYA --}}
            @else
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

                {{-- KISZÁLLÁS GOMB (CSAK A NORMÁL MÓDBAN ÉS KÉRDÉS KÖZBEN JELENIK MEG) --}}
                @if($game['game_mode'] === 'normal')
                    <div class="flex justify-end">
                        <form action="{{ route('quiz.cashout', $quiz) }}" method="POST" onsubmit="return confirm('Biztosan be szeretnéd fejezni a játékot? Az eddig megnyert pontjaid már a számládon vannak.');">
                            @csrf
                            <button type="submit" class="py-3 px-6 bg-red-50 hover:bg-red-100 text-red-600 font-extrabold rounded-xl border border-red-200 transition">
                                🛑 Befejezem a játékot
                            </button>
                        </form>
                    </div>
                @endif
            @endif

        </div>
    </div>
@endsection
