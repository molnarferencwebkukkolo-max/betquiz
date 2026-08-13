@extends('layouts.game')

@section('body_class', 'kwizzgo-game-page')
@section('page_wrapper_class', 'kwizzgo-game-wrapper')

@section('content')
    <div class="py-8 game-screen" x-data="{
        timeLeft: {{ $game['time_limit'] }},
        timer: null,
        startTimer() {
            @if(empty($game['awaiting_decision']) && empty($game['awaiting_dice']) && empty($game['awaiting_time_travel']) && empty($game['helper_overlay']) && empty($game['dice_result']))
                this.timer = setInterval(() => {
                    if (this.timeLeft > 0) {
                        this.timeLeft--;
                    } else {
                        clearInterval(this.timer);

                        document.querySelectorAll('input[name=selected_option]').forEach(el => el.removeAttribute('required'));

                        let form = document.getElementById('game-form');
                        if (form) {
                            HTMLFormElement.prototype.submit.call(form);
                        }
                    }
                }, 1000);
            @endif
        }
    }" x-init="startTimer()">

        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            {{-- 🎯 DYNAMIC STATS BAR --}}
            <div class="game-stats-bar">
                @if($game['game_mode'] === 'normal')
                    {{-- 🟢 NORMÁL MÓD STATS BAR --}}
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 items-center text-center md:text-left">
                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase block">Zsetonjaim</span>
                            <span class="text-lg font-black text-amber-500">💰 {{ auth()->user()->points }} PT</span>
                        </div>

                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase block">Tét</span>
                            <span class="text-lg font-black text-indigo-600">🎯 {{ $game['initial_bet'] }} PT</span>
                        </div>

                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase block">Szorzók</span>
                            <span class="text-sm font-extrabold text-gray-700 bg-gray-100 px-2 py-1 rounded-lg inline-block">
                                Nehézség: {{ $difficultyMultiplier }}x | Idő: {{ $timeMultiplier }}x
                            </span>
                        </div>

                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase block">Várható nyeremény</span>
                            <span class="text-xl font-black text-green-600">💰 +{{ $expectedWin }} PT</span>
                        </div>

                        <div class="col-span-2 md:col-span-1 text-right">
                            <span class="text-xs text-gray-400 font-bold uppercase block">Hátralévő idő</span>
                            <span class="text-2xl font-black" :class="timeLeft <= 5 ? 'text-red-600 animate-pulse' : 'text-indigo-600'" x-text="timeLeft + 's'"></span>
                        </div>
                    </div>
                @else
                    {{-- 🎲 ODDS MÓD STATS BAR --}}
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 items-center text-center md:text-left">
                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase block">Zsetonjaim</span>
                            <span class="text-lg font-black text-amber-500">💰 {{ auth()->user()->points }} PT</span>
                        </div>

                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase block">Alaptét</span>
                            <span class="text-lg font-black text-indigo-600">🎯 {{ $game['initial_bet'] }} PT</span>
                        </div>

                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase block">Nyereménybank</span>
                            <span class="text-lg font-black text-emerald-600">🏆 {{ $game['current_pot'] }} PT</span>
                        </div>

                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase block">Szorzók</span>
                            <span class="text-xs font-extrabold text-gray-700 bg-gray-100 px-2 py-1 rounded-lg inline-block">
                                {{ $difficultyMultiplier }}x × {{ $timeMultiplier }}x
                            </span>
                        </div>

                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase block">
                                {{ $game['current_step'] == $game['target_count'] ? 'Várható Nyeremény' : 'Nyereménybank növekedés' }}
                            </span>
                            <span class="text-lg font-black text-amber-600">💰 +{{ $expectedWin }} PT</span>
                        </div>

                        <div class="col-span-2 md:col-span-1 text-right">
                            <span class="text-xs text-gray-400 font-bold uppercase block">Kérdés</span>
                            <span class="text-sm font-black text-gray-700">#{{ $game['current_step'] }} / {{ $game['target_count'] }}</span>
                            <span class="text-xl font-black block" :class="timeLeft <= 5 ? 'text-red-600 animate-pulse' : 'text-indigo-600'" x-text="timeLeft + 's'"></span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- 1. ESET: HELYES VÁLASZ UTÁNI DÖNTÉSI KÉPERNYŐ (NORMÁL ÉS ODDS MÓDBAN IS) --}}
            @if(!empty($game['awaiting_decision']))
                <div class="bg-gradient-to-br from-emerald-900 via-teal-950 to-slate-900 text-white rounded-3xl p-8 text-center shadow-2xl mb-6 border border-emerald-500/30">

                    <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 animate-bounce">
                        🎉
                    </div>

                    <h3 class="text-3xl font-black mb-2">HELYES VÁLASZ!</h3>

                    @if($game['game_mode'] === 'normal')
                        <p class="text-emerald-200 text-lg mb-4">
                            Ebben a körben nyertél: <strong class="text-amber-400 font-black text-2xl">+{{ $game['won_amount'] }} PT-t</strong>!
                        </p>
                        <div class="bg-slate-900/60 rounded-2xl p-4 mb-8 max-w-md mx-auto border border-emerald-500/20">
                            <p class="text-slate-300 text-sm">
                                A nyereményt azonnal jóváírtuk az egyenlegeden! <br>
                                Jelenlegi egyenleged: <strong class="text-amber-400 text-lg font-bold">{{ auth()->user()->points }} PT</strong>
                            </p>
                        </div>
                    @else
                        <p class="text-emerald-200 text-lg mb-4">
                            A Nyereménybankod megnőtt: <strong class="text-amber-400 font-black text-2xl">{{ $game['current_pot'] }} PT-re</strong>!
                        </p>
                    @endif

                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center max-w-xl mx-auto">
                        @if($game['game_mode'] === 'normal')
                            {{-- NORMÁL MÓD KISZÁLLÁS --}}
                            <form action="{{ route('quiz.cashout', $quiz) }}" method="POST" class="w-full sm:w-auto">
                                @csrf
                                <button type="submit" class="w-full py-4 px-8 bg-slate-800 hover:bg-slate-700 text-rose-300 font-bold rounded-2xl border border-rose-500/30 transition transform active:scale-95 shadow-lg">
                                    🛑 Befejezem a játékot
                                </button>
                            </form>
                        @else
                            {{-- ODDS MÓD KISZÁLLÁS: 20%-ÉRT --}}
                            @php
                                $cashout20 = (int)round($game['current_pot'] * 0.20);
                            @endphp
                            <form action="{{ route('quiz.cashout', $quiz) }}" method="POST" class="w-full sm:w-auto" onsubmit="return confirm('Biztosan kiszállsz? Így a nyereménybank 20%-át ({{ $cashout20 }} PT) kapod meg!');">
                                @csrf
                                <button type="submit" class="w-full py-4 px-8 bg-slate-800 hover:bg-slate-700 text-amber-300 font-bold rounded-2xl border border-amber-500/30 transition transform active:scale-95 shadow-lg">
                                    🛑 Kiszállok 20%-ért ({{ $cashout20 }} PT)
                                </button>
                            </form>
                        @endif

                        {{-- FOLYTATÁS --}}
                        <form action="{{ route('quiz.next_question', $quiz) }}" method="POST" class="w-full sm:w-auto">
                            @csrf
                            <button type="submit" class="w-full py-4 px-8 bg-gradient-to-r from-emerald-400 to-teal-500 hover:from-emerald-500 hover:to-teal-600 text-slate-950 font-black text-lg rounded-2xl shadow-lg hover:shadow-emerald-500/30 transition transform active:scale-95">
                                🚀 Következő kérdés
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

                    <button type="button"
                            @click="rollDice()"
                            :disabled="rolling"
                            class="py-4 px-10 bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-500 hover:to-orange-600 text-slate-950 font-black text-xl rounded-2xl shadow-lg transition transform active:scale-95 disabled:opacity-50">
                        <span x-text="rolling ? 'KOCKA PÖRGETÉSE...' : '{{ $remainingFreeRolls > 0 ? '🎲 INGYENES GURÍTÁS!' : '💳 GURÍTÁS (100 PT)' }}'"></span>
                    </button>

                </div>

                {{-- 3. ESET: IDŐLEJÁRÁS UTÁN ➔ DOKI IDŐUGRÁSA --}}
            @elseif(!empty($game['dice_result']))
                @php
                    $diceResult = $game['dice_result'];
                    $diceCashout = (int) round(($game['current_pot'] ?? 0) * 0.20);
                @endphp
                <section class="dice-result-stage {{ $diceResult['success'] ? 'success' : 'failure' }}">
                    <span class="dice-result-kicker">A dobás eredménye</span>
                    <div class="dice-result-cube">{{ $diceResult['roll'] ?? '×' }}</div>
                    @if($diceResult['success'])
                        <h2>Sikerült! Hatost dobtál!</h2><p>A kocka megmentett, folytathatod a játékot.</p>
                        <div class="dice-result-actions">
                            @if(($game['game_mode'] ?? 'normal') === 'odds')
                                <form action="{{ route('quiz.cashout', $quiz) }}" method="POST">@csrf<button class="dice-secondary-action">Kiszállok {{ $diceCashout }} PT-ért</button></form>
                            @else
                                <form action="{{ route('quiz.roll_dice.finish', $quiz) }}" method="POST">@csrf<button class="dice-secondary-action">Vissza a kvízekhez</button></form>
                            @endif
                            <form action="{{ route('quiz.next_question', $quiz) }}" method="POST">@csrf<button class="dice-primary-action">Következő kérdés →</button></form>
                        </div>
                    @else
                        <h2>{{ ($diceResult['reason'] ?? null) === 'no_points' ? 'Nincs elég zsetonod' : 'Sajnos rosszat dobtál' }}</h2>
                        <p>{{ ($diceResult['reason'] ?? null) === 'no_points' ? 'A fizetős gurításhoz 100 PT szükséges.' : $diceResult['roll'].' lett a dobásod. A játék most véget ért.' }}</p>
                        <div class="dice-result-actions single"><form action="{{ route('quiz.roll_dice.finish', $quiz) }}" method="POST">@csrf<button class="dice-primary-action">Vissza a kvízekhez</button></form></div>
                    @endif
                </section>
            @elseif(!empty($game['awaiting_time_travel']))
                @php
                    $freeTravelsUsed = auth()->user()->lifetime_free_time_travels_used ?? 0;
                    $remainingTravels = max(0, 3 - $freeTravelsUsed);
                    $timeTravelTheme = auth()->user()->time_travel_theme ?? 'back_to_future';
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
                }" class="{{ $timeTravelTheme === 'harry_potter' ? 'bg-gradient-to-br from-indigo-950 via-violet-950 to-slate-950 border-violet-400/50' : 'bg-gradient-to-br from-slate-950 via-amber-950 to-slate-900 border-amber-500/40' }} text-white rounded-3xl p-8 text-center shadow-2xl mb-6 border-2 relative overflow-hidden">

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

                    @if($timeTravelTheme === 'harry_potter')
                        <h3 class="text-3xl font-black mb-2 text-violet-200">⏱️ KIFUTOTTÁL AZ IDŐBŐL!</h3>
                        <p class="text-violet-100 text-sm max-w-lg mx-auto mb-6">
                            Hermione megpörgeti az <strong class="text-amber-300">Időnyerőt</strong>, és visszaforgatja az órát a kérdés elejére.
                        </p>

                        <div class="my-6 max-w-xs mx-auto relative flex items-center justify-center">
                            <div class="absolute inset-0 rounded-full bg-violet-400/20 blur-2xl" :class="traveling ? 'animate-pulse' : ''"></div>
                            <div class="relative w-40 h-40 rounded-full border-4 border-amber-300 bg-slate-950/80 shadow-2xl flex items-center justify-center"
                                 :class="traveling ? 'animate-spin' : ''"
                                 style="animation-duration: 0.9s;">
                                <div class="absolute w-28 h-28 rounded-full border-2 border-amber-200/80"></div>
                                <div class="absolute h-32 w-1 bg-amber-300 rounded-full"></div>
                                <div class="absolute w-32 h-1 bg-amber-300 rounded-full"></div>
                                <span class="relative text-4xl">✦</span>
                            </div>
                        </div>
                    @else
                        <h3 class="text-3xl font-black mb-2 text-amber-400 font-mono">⏱️ KIFUTOTTÁL AZ IDŐBŐL!</h3>
                        <p class="text-gray-300 text-sm max-w-lg mx-auto mb-6">
                            Emmett Brown segít rajtad: a <strong class="text-amber-400">Fluxuskondenzátor</strong> visszapörgeti az órát a kérdés elejére.
                        </p>

                        <div class="my-6 flex flex-col items-center gap-4">
                            <div class="w-24 h-24 rounded-full bg-amber-100 text-slate-900 border-4 border-amber-400 shadow-xl flex items-center justify-center relative">
                                <div class="absolute -left-2 -right-2 -top-2 h-8 rounded-full bg-white"></div>
                                <div class="relative mt-4 text-center">
                                    <div class="text-3xl">⚗</div>
                                    <div class="text-xs font-black">DOKI</div>
                                </div>
                            </div>

                            <div class="max-w-xs w-full bg-black border-4 border-gray-800 rounded-2xl p-4 shadow-inner flex flex-col items-center justify-center font-mono">
                                <span class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-1">SEBESSÉGMÉRŐ</span>
                                <div class="text-5xl font-black tracking-widest" :class="speed >= 88 ? 'text-amber-400 animate-pulse' : 'text-red-600'">
                                    <span x-text="speed"></span> <span class="text-xl">MPH</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form id="time-travel-form" action="{{ route('quiz.time_travel', $quiz) }}" method="POST" class="hidden">
                        @csrf
                    </form>

                    <button type="button"
                            @click="triggerTimeTravel()"
                            :disabled="traveling"
                            class="py-4 px-10 {{ $timeTravelTheme === 'harry_potter' ? 'bg-gradient-to-r from-violet-300 via-fuchsia-300 to-amber-200 hover:from-violet-200' : 'bg-gradient-to-r from-amber-500 via-orange-500 to-yellow-500 hover:from-amber-600' }} text-slate-950 font-black text-xl rounded-2xl shadow-xl transition transform active:scale-95 disabled:opacity-50">
                        @if($timeTravelTheme === 'harry_potter')
                            <span x-text="traveling ? 'IDŐNYERŐ PÖRGETÉSE...' : 'IDŐNYERŐ HASZNÁLATA'"></span>
                        @else
                            <span x-text="traveling ? '⚡ VISSZAÚT A MÚLTBA...' : '🚗 VISSZA A MÚLTBA (ÓRA ÚJRAINDÍTÁSA)'"></span>
                        @endif
                    </button>
                </div>

                {{-- 4. ESET: AKTÍV KÉRDÉS KÁRTYA --}}
            @elseif(!empty($game['helper_overlay']))
                @php
                    $overlay = $game['helper_overlay'];
                @endphp
                <div class="helper-game-stage {{ $overlay === 'poker' ? 'poker-stage' : 'blackjack-stage' }}">
                    @if($overlay === 'poker')
                        @php
                            $poker = $game['helper_results']['poker'];
                        @endphp
                        <span class="helper-stage-kicker">KwizzGo segítség</span>
                        <h2 class="text-3xl font-black mb-2">♠ KwizzGo Poker</h2>
                        <p class="text-violet-200 mb-6">A szabályos pókerkéz dönti el a kérdés sorsát.</p>
                        @foreach(['dealer' => 'Gép lapjai', 'player' => 'A te lapjaid'] as $side => $title)
                            <h3 class="font-bold mt-5 mb-2">{{ $title }} – {{ $poker[$side.'_label'] }}</h3>
                            <div class="card-hand">
                                @foreach($poker[$side] as $cardIndex => $card)
                                    <span class="playing-card deal-card {{ in_array($card['suit'], ['♥','♦']) ? 'red' : '' }}" style="--deal-index: {{ ($side === 'dealer' ? 0 : 5) + $cardIndex }}">
                                        {{ $card['rank'] === 14 ? 'A' : ($card['rank'] === 13 ? 'K' : ($card['rank'] === 12 ? 'Q' : ($card['rank'] === 11 ? 'J' : $card['rank']))) }}{{ $card['suit'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endforeach
                        <p class="text-xl font-black mt-6 {{ $poker['player_won'] ? 'text-emerald-300' : 'text-rose-300' }}">{{ $poker['player_won'] ? 'Nyertél – helyes válasz!' : 'A gép nyert – helytelen válasz.' }}</p>
                    @else
                        @php
                            $blackjack = $game['blackjack'];
                        @endphp
                        @php
                            $cardLabel = function (array $card): string {
                                $rank = match ($card['rank']) {
                                    14 => 'A',
                                    13 => 'K',
                                    12 => 'Q',
                                    11 => 'J',
                                    default => (string) $card['rank'],
                                };

                                return $rank.$card['suit'];
                            };
                        @endphp
                        <span class="helper-stage-kicker">KwizzGo segítség</span>
                        <h2 class="text-3xl font-black mb-2">21 – Blackjack</h2>
                        <p class="text-violet-200 mb-5">Az óra áll. Döntetlennél te nyersz.</p>
                        <div class="blackjack-score-label"><span>Gép keze</span><strong>{{ $blackjack['finished'] ? $blackjack['dealer_value'] : '?' }}</strong></div>
                        <div class="card-hand">
                            <span class="playing-card">{{ $cardLabel($blackjack['dealer'][0]) }}</span>
                            <span class="playing-card">{{ $blackjack['finished'] ? $cardLabel($blackjack['dealer'][1]) : '🂠' }}</span>
                            @if($blackjack['finished'])
                                @foreach(array_slice($blackjack['dealer'], 2) as $card)
                                    <span class="playing-card">{{ $cardLabel($card) }}</span>
                                @endforeach
                            @endif
                        </div>
                        <div class="blackjack-score-label player"><span>A kezed értéke</span><strong>{{ $blackjack['player_value'] }}</strong></div>
                        <div class="card-hand">
                            @foreach($blackjack['player'] as $card)
                                <span class="playing-card">{{ $cardLabel($card) }}</span>
                            @endforeach
                        </div>
                        @if(!$blackjack['finished'])
                            <div class="blackjack-actions mt-7">
                                <form action="{{ route('quiz.helpers.blackjack.action', $quiz) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="hit">
                                    <button class="blackjack-action blackjack-hit">Lap kérek</button>
                                </form>
                                <form action="{{ route('quiz.helpers.blackjack.action', $quiz) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="stand">
                                    <button class="blackjack-action blackjack-stand">Megállok</button>
                                </form>
                                <form action="{{ route('quiz.helpers.blackjack.abandon', $quiz) }}" method="POST" onsubmit="return confirm('Biztosan kilépsz? Ezzel elveszíted az aktuális kérdést.');">
                                    @csrf
                                    <button class="blackjack-action blackjack-abandon">Kilépek – elveszítem</button>
                                </form>
                            </div>
                            <p class="blackjack-abandon-note">A megkezdett 21-ből való kilépés hibás válasznak számít.</p>
                        @else
                            <p class="text-xl font-black mt-6 {{ $blackjack['player_won'] ? 'text-emerald-300' : 'text-rose-300' }}">{{ $blackjack['player_won'] ? 'Nyertél – helyes válasz!' : 'Vesztettél – helytelen válasz.' }}</p>
                        @endif
                    @endif
                    @if($overlay === 'poker' || !empty($blackjack['finished']))
                        <form action="{{ route('quiz.helpers.resolve', $quiz) }}" method="POST" class="mt-6">
                            @csrf
                            <button class="px-8 py-3 bg-gradient-to-r from-violet-500 to-fuchsia-500 rounded-xl font-black">Eredmény folytatása →</button>
                        </form>
                    @endif
                </div>
            @else
                <div class="question-stage">
                    <div class="question-meta-row">
                        <span class="difficulty-badge">
                            Nehézség: {{ $currentQuestion->difficulty ?? 'Normál' }}
                        </span>
                        <span class="question-counter">Kérdés #{{ $game['current_step'] }} / {{ $game['target_count'] }}</span>
                    </div>

                    @php
                        $questionText = $currentQuestion->question_text;
                        if (is_array($questionText)) {
                            $questionText = $questionText['hu'] ?? $questionText['en'] ?? reset($questionText);
                        }
                    @endphp

                    <h2 class="question-title">
                        {{ $questionText }}
                    </h2>

                    @php
                        $answersList = $currentQuestion->answers ?? $currentQuestion->options ?? [];
                    @endphp

                    <div class="helper-section-heading"><span>✨</span><div><strong>Segítségek</strong><small>3-3 ingyenes használat, utána 100 PT</small></div></div>
                    <div class="quiz-helper-toolbar">
                        @php
                            $helperIcons = ['fifty_fifty' => '½', 'poker' => '♠', 'blackjack' => '21', 'audience' => '▥', 'bear' => '🐻'];
                        @endphp
                        @foreach(['fifty_fifty'=>'50:50','poker'=>'♠ Poker','blackjack'=>'21','audience'=>'Közönség','bear'=>'KwizzGoBear'] as $helper => $label)
                            <form action="{{ $helper === 'blackjack' ? route('quiz.helpers.blackjack.start',$quiz) : route('quiz.helpers.use',[$quiz,$helper]) }}" method="POST">@csrf<button type="submit"><span>{{ $helperIcons[$helper] }}</span><strong>{{ $label }}</strong><small>{{ $helperBalances[$helper]['remaining_free'] > 0 ? $helperBalances[$helper]['remaining_free'].' ingyen' : '100 PT' }}</small></button></form>
                        @endforeach
                    </div>

                    @if(!empty($game['helper_results']['audience']))
                        <div class="audience-result">
                            <strong>Közönségszavazás</strong>
                            @foreach($answersList as $index => $answer)
                                @php
                                    $answerId = is_object($answer) ? $answer->id : $answer['id'];
                                @endphp
                                <span>{{ chr(65 + $index) }}: {{ $game['helper_results']['audience'][$answerId] ?? 0 }}%</span>
                            @endforeach
                        </div>
                    @endif
                    @if(!empty($game['helper_results']['bear']))
                        <div class="bear-result">
                            🐻 KwizzGoBear szerint a helyes válasz:
                            @php
                                $bearAnswerIndex = collect($answersList)->search(function ($answer) use ($game): bool {
                                    $answerId = is_object($answer) ? $answer->id : $answer['id'];
                                    return (int) $answerId === (int) $game['helper_results']['bear'];
                                });
                            @endphp
                            <strong>{{ chr(65 + $bearAnswerIndex) }}</strong>
                        </div>
                    @endif

                    {{-- VÁLASZ LEHETŐSÉGEK FORM --}}
                    <form id="game-form" action="{{ route('quiz.submit_answer', $quiz) }}" method="POST">
                        @csrf
                        <input type="hidden" name="question_id" value="{{ $currentQuestion->id }}">

                        <div class="answer-grid">
                            @foreach($answersList as $index => $answer)
                                @php
                                    $answerText = is_object($answer) ? $answer->option_text : ($answer['option_text'] ?? '');
                                    if (is_array($answerText)) {
                                        $answerText = $answerText['hu'] ?? $answerText['en'] ?? reset($answerText);
                                    }
                                @endphp
                                <label class="answer-option {{ in_array((int)(is_object($answer)?$answer->id:$answer['id']), $game['helper_results']['fifty_fifty'] ?? [], true) ? 'helper-eliminated' : '' }}">
                                    <input type="radio" name="selected_option" value="{{ is_object($answer) ? $answer->id : ($answer['id'] ?? $index) }}" required>
                                    <span class="answer-letter">{{ chr(65 + $index) }}</span>
                                    <span class="answer-copy">
                                        {{ $answerText }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <button type="submit" class="answer-submit-button">
                            Válasz beküldése ➔
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
@endsection
