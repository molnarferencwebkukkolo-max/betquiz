<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quiz->title }} - BetQuiz</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Vissza gomb -->
    <div class="mb-6">
        <a href="{{ route('quizzes.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition">
            ← Vissza a Kvízekhez
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded-2xl font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl font-bold space-y-1">
            @foreach($errors->all() as $err)
                <p>• {{ $err }}</p>
            @endforeach
        </div>
    @endif

    @php
        $qCount = $quiz->questions->count();
        $percent = min(100, round(($qCount / 100) * 100));
        $isPublished = (bool) $quiz->is_public;
        $canPublish = ($qCount >= 100);
        $totalAnswers = $quiz->totalAnswersCount();
        $correctAnswers = $quiz->correctAnswersCount();
        $successRate = $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100) : 0;
    @endphp

        <!-- Kvíz Fejléc Kártya -->
    <div class="bg-white rounded-3xl shadow-md border border-gray-100 p-8 mb-8 overflow-hidden">

        <!-- Ha van fejléckép, megjelenítjük borítóként -->
        @if($quiz->cover_image)
            <div class="-mx-8 -mt-8 mb-6 h-48 sm:h-64 overflow-hidden border-b">
                <img src="{{ asset('storage/' . $quiz->cover_image) }}" alt="{{ $quiz->title }}" class="w-full h-full object-cover">
            </div>
        @endif

        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                        <span class="text-xs font-bold px-3 py-1 rounded-full bg-indigo-50 text-indigo-700">
                            {{ is_array($quiz->category->name ?? null) ? ($quiz->category->name['hu'] ?? reset($quiz->category->name)) : ($quiz->category->name ?? 'Általános') }}
                        </span>

                    @if($isPublished)
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-green-100 text-green-700">🟢 Publikus</span>
                    @elseif($quiz->status === 'pending')
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-blue-100 text-blue-700">🔵 Kérdésfeltöltés ({{ $qCount }}/100)</span>
                    @else
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700">❌ Elutasítva</span>
                    @endif
                </div>
                <h1 class="text-3xl font-extrabold text-gray-800">{{ $quiz->title }}</h1>
                @if($quiz->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mt-3">
                        @foreach($quiz->tags as $tag)
                            <span class="text-xs font-extrabold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
                <p class="text-gray-500 mt-1">{{ $quiz->description ?? 'Nincs megadva leírás.' }}</p>
            </div>

            <!-- Műveleti gombok a fejlécben -->
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">

                <!-- ✏️ Kvíz Szerkesztése Gomb -->
                <a href="{{ route('my-quizzes.edit', $quiz) }}" class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold rounded-2xl transition flex items-center gap-1 text-sm">
                    ✏️ Szerkesztés
                </a>

                <!-- 🚀 Publikálás / Visszavonás Gomb -->
                @if($user->isUseradmin() || $user->isHostadmin())
                    <form action="{{ route('admin.quizzes.bulk-update') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="quiz_ids[]" value="{{ $quiz->id }}">
                        <input type="hidden" name="bulk_action" value="{{ $isPublished ? 'make_private' : 'make_public' }}">
                        @if($isPublished)
                            <select class="moderation-reason-preset w-full mb-2 px-3 py-2 border border-amber-200 rounded-xl text-sm">
                                <option value="">Gyakori indok…</option>
                                <option value="A kvíz tartalma vagy témája nem felel meg a közzétételi irányelveknek.">Nem megfelelő tartalom</option>
                                <option value="A kérdések vagy válaszok minősége további javítást igényel.">Minőségi javítás</option>
                                <option value="A kvíz ideiglenesen további adminisztrátori ellenőrzést igényel.">További ellenőrzés</option>
                            </select>
                            <textarea name="moderation_reason" rows="2" maxlength="2000" required
                                      class="moderation-reason-input w-full mb-2 px-3 py-2 border border-amber-200 rounded-xl text-sm"
                                      placeholder="A visszavonás indoka…"></textarea>
                            <button type="submit" class="px-5 py-3 bg-amber-500 hover:bg-amber-600 text-white font-extrabold rounded-2xl shadow transition flex items-center gap-2 text-sm">
                                🔒 Publikálás visszavonása
                            </button>
                        @else
                            <button type="submit"
                                    {{ !$canPublish || $quiz->status !== 'approved' ? 'disabled' : '' }}
                                    class="px-5 py-3 font-extrabold rounded-2xl shadow transition flex items-center gap-2 text-sm
                                               {{ $canPublish && $quiz->status === 'approved' ? 'bg-green-600 hover:bg-green-700 text-white cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
                                🚀 Kvíz publikálása
                            </button>
                        @endif
                    </form>
                @endif
                <!-- TÖRLÉS Gomb -->
                <form action="{{ route('my-quizzes.destroy', $quiz) }}" method="POST" onsubmit="return confirm('Biztosan törölni szeretnéd ezt a kvízt?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-3 bg-red-100 hover:bg-red-200 text-red-600 font-extrabold rounded-2xl transition flex items-center gap-1 text-sm">
                        🗑️ Kvíz törlése
                    </button>
                </form>

                <!-- ➕ Új Kérdés Gomb -->
                <a href="{{ route('questions.create', ['quiz_id' => $quiz->id]) }}" class="btn-primary">
                    ➕ Új kérdés hozzáadása
                </a>
            </div>
        </div>

        @if($quiz->rejection_reason)
            <div class="mb-6 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
                <p class="text-xs font-extrabold uppercase tracking-wide text-amber-700 mb-2">Adminisztrátori indok</p>
                <p class="font-semibold whitespace-pre-line">{{ $quiz->rejection_reason }}</p>
            </div>
        @endif

        <!-- Haladási sáv (Progress Bar) -->
        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <p class="text-xs font-extrabold text-gray-400 uppercase mb-1">Összes válasz</p>
                    <p class="text-2xl font-extrabold text-gray-800">{{ number_format($totalAnswers, 0, ',', ' ') }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <p class="text-xs font-extrabold text-gray-400 uppercase mb-1">Helyes válasz</p>
                    <p class="text-2xl font-extrabold text-green-600">{{ number_format($correctAnswers, 0, ',', ' ') }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <p class="text-xs font-extrabold text-gray-400 uppercase mb-1">Találati arány</p>
                    <p class="text-2xl font-extrabold text-indigo-600">{{ $successRate }}%</p>
                </div>
            </div>
            <div class="flex justify-between text-xs font-extrabold text-gray-600 mb-1">
                <span>Kérdések feltöltöttsége (Cél: 100 db a publikáláshoz):</span>
                <span class="{{ $canPublish ? 'text-green-600 font-bold' : '' }}">{{ $qCount }} / 100 DB ({{ $percent }}%)</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div class="h-3 rounded-full transition-all duration-500 {{ $canPublish ? 'bg-green-500' : 'bg-indigo-600' }}" style="width: {{ $percent }}%;"></div>
            </div>
            @if(!$canPublish && !$isPublished)
                <p class="text-xs text-amber-600 mt-2 font-semibold">
                    ⚠️ Tölts fel még <strong>{{ 100 - $qCount }} db</strong> kérdést a publikálási gomb aktiválásához!
                </p>
            @endif
        </div>

        <!-- 📊 CSV IMPORTÁLÓ BLOKK -->
        <div class="bg-indigo-50/60 rounded-2xl p-5 border border-indigo-100">
            <h3 class="font-extrabold text-indigo-900 text-sm mb-2 flex items-center gap-2">
                📂 Tömeges Kérdés Importálás CSV-ből
            </h3>
            <p class="text-xs text-indigo-700 mb-4">
                Formátum (pontosvesszővel <code>;</code> elválasztva):
                <code>Kérdés; Helyes válasz; Hibás1; Hibás2; Hibás3; Nehézség (easy/medium/hard)</code>
            </p>

            <form action="{{ route('my-quizzes.questions.import', $quiz) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 items-center">
                @csrf
                <input type="file" name="csv_file" required accept=".csv,.txt" class="w-full sm:w-auto text-xs bg-white p-2.5 rounded-xl border border-indigo-200 font-medium text-gray-600">
                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow transition">
                    📥 CSV Feltöltése és Importálása
                </button>
            </form>
        </div>
    </div>

    <!-- Kérdések Listája Statisztikával -->
    <div class="bg-white rounded-3xl shadow-md border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">❓ A Kvízben szereplő kérdések ({{ $qCount }} db)</h2>
        </div>

        <form id="question-bulk-form"
              action="{{ route('my-quizzes.questions.bulk-update', $quiz) }}"
              method="POST"
              class="p-5 bg-slate-50 border-b border-gray-100">
            @csrf
            @method('PATCH')
            <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                <div class="flex-1">
                    <label for="question-bulk-action" class="block text-xs font-extrabold uppercase text-gray-500 mb-2">
                        Tömeges művelet
                    </label>
                    <select id="question-bulk-action" name="bulk_action" required
                            class="w-full px-4 py-3 rounded-2xl border border-gray-200 bg-white">
                        <option value="">Válassz műveletet…</option>
                        <option value="change_difficulty">Nehézség módosítása</option>
                        @if($user->isUseradmin() || $user->isHostadmin())
                            <option value="move_to_quiz">Áthelyezés másik kvízbe</option>
                        @endif
                    </select>
                </div>

                <div id="question-difficulty-field" class="flex-1 hidden">
                    <label for="question-bulk-difficulty" class="block text-xs font-extrabold uppercase text-gray-500 mb-2">
                        Új nehézség
                    </label>
                    <select id="question-bulk-difficulty" name="difficulty"
                            class="w-full px-4 py-3 rounded-2xl border border-gray-200 bg-white">
                        <option value="">Válassz nehézséget…</option>
                        <option value="easy">Könnyű</option>
                        <option value="medium">Közepes</option>
                        <option value="hard">Nehéz</option>
                    </select>
                </div>

                @if($user->isUseradmin() || $user->isHostadmin())
                    <div id="question-target-quiz-field" class="flex-1 hidden relative">
                        <label for="target-quiz-search" class="block text-xs font-extrabold uppercase text-gray-500 mb-2">
                            Célkvíz keresése
                        </label>
                        <input id="target-quiz-id" type="hidden" name="target_quiz_id">
                        <input id="target-quiz-search" type="search" autocomplete="off"
                               placeholder="Kvíznév, szerző vagy ID…"
                               class="w-full px-4 py-3 rounded-2xl border border-gray-200 bg-white">
                        <div id="target-quiz-results"
                             class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-2xl shadow-xl max-h-64 overflow-y-auto"></div>
                    </div>
                @endif

                <button type="submit"
                        class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-2xl">
                    Alkalmazás a kijelöltekre
                </button>
            </div>
            <div class="flex items-center gap-4 mt-3">
                <button id="select-all-questions-button" type="button"
                        class="text-xs font-extrabold text-indigo-600 hover:text-indigo-800">
                    Összes kijelölése
                </button>
                <button id="clear-question-selection-button" type="button"
                        class="text-xs font-extrabold text-gray-500 hover:text-gray-800">
                    Kijelölés törlése
                </button>
                <span id="question-selection-count" class="text-xs font-bold text-gray-500">0 kérdés kijelölve</span>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase font-extrabold text-gray-500 tracking-wider">
                    <th class="p-4">
                        <input id="select-all-questions" type="checkbox" class="w-4 h-4 accent-indigo-600"
                               title="Összes kijelölése">
                    </th>
                    <th class="p-4">#</th>
                    <th class="p-4">Kérdés Szövege</th>
                    <th class="p-4">Nehézség</th>
                    <th class="p-4">Helyes Válasz</th>
                    <th class="p-4 text-center">📊 Megválaszolva</th>
                    <th class="p-4 text-center">🎯 Helyes (%)</th>
                    <th class="p-4 text-right">Műveletek</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($quiz->questions as $index => $question)
                    @php
                        $qText = is_array($question->question_text)
                            ? ($question->question_text['hu'] ?? reset($question->question_text))
                            : $question->question_text;

                        $correctOpt = $question->options->firstWhere('is_correct', true);
                        $cText = $correctOpt ? (is_array($correctOpt->option_text) ? ($correctOpt->option_text['hu'] ?? reset($correctOpt->option_text)) : $correctOpt->option_text) : '-';

                        $rate = method_exists($question, 'successRate') ? $question->successRate() : 0;
                    @endphp

                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="p-4">
                            <input type="checkbox" name="question_ids[]" value="{{ $question->id }}"
                                   form="question-bulk-form"
                                   class="question-row-checkbox w-4 h-4 accent-indigo-600">
                        </td>
                        <td class="p-4 font-bold text-gray-400">{{ $index + 1 }}.</td>

                        <td class="p-4 font-semibold text-gray-800 max-w-xs sm:max-w-md">
                            {{ $qText }}
                        </td>

                        <td class="p-4">
                            @if($question->difficulty === 'easy')
                                <span class="px-2.5 py-1 bg-green-100 text-green-700 font-extrabold text-xs rounded-full">Könnyű</span>
                            @elseif($question->difficulty === 'hard')
                                <span class="px-2.5 py-1 bg-red-100 text-red-700 font-extrabold text-xs rounded-full">Nehéz</span>
                            @else
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-700 font-extrabold text-xs rounded-full">Közepes</span>
                            @endif
                        </td>

                        <td class="p-4 text-emerald-600 font-bold">
                            ✓ {{ $cText }}
                        </td>

                        <td class="p-4 text-center">
                            <span class="font-extrabold text-gray-700">{{ $question->times_answered ?? 0 }}</span>
                            <span class="text-xs text-gray-400 block">alkalom</span>
                        </td>

                        <td class="p-4 text-center">
                            @if(($question->times_answered ?? 0) > 0)
                                <div class="inline-flex flex-col items-center">
                                    <span class="font-extrabold text-xs px-2.5 py-1 rounded-full
                                        {{ $rate >= 70 ? 'bg-green-100 text-green-800' : ($rate >= 40 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $question->times_correct }} / {{ $question->times_answered }} ({{ $rate }}%)
                                    </span>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Még nincs adat</span>
                            @endif
                        </td>

                        <td class="p-4 text-right space-x-2">
                            <a href="{{ route('questions.edit', $question->id) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">
                                ✏️ Szerkesztés
                            </a>
                            <form action="{{ route('questions.destroy', $question->id) }}" method="POST" onsubmit="return confirm('Biztosan törölni szeretnéd ezt a kérdést?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="color: #ef4444; background: none; border: none; cursor: pointer; font-size: 0.875rem; font-weight: 700;">
                                    🗑️ Törlés
                                </button>
                            </form>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-gray-400 font-semibold">
                            Ebben a kvízben még nincsenek kérdések! Használd a fenti CSV importálót vagy adj hozzá egyesével.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    document.querySelectorAll('.moderation-reason-preset').forEach((preset) => {
        preset.addEventListener('change', () => {
            const input = preset.closest('form')?.querySelector('.moderation-reason-input');
            if (preset.value && input) {
                input.value = preset.value;
                input.focus();
            }
        });
    });
</script>

<script>
    (() => {
        const checkboxes = [...document.querySelectorAll('.question-row-checkbox')];
        const bulkForm = document.getElementById('question-bulk-form');
        const selectAll = document.getElementById('select-all-questions');
        const selectAllButton = document.getElementById('select-all-questions-button');
        const clearButton = document.getElementById('clear-question-selection-button');
        const selectionCount = document.getElementById('question-selection-count');
        const action = document.getElementById('question-bulk-action');
        const difficultyField = document.getElementById('question-difficulty-field');
        const difficulty = document.getElementById('question-bulk-difficulty');
        const targetField = document.getElementById('question-target-quiz-field');
        const targetInput = document.getElementById('target-quiz-id');
        const targetSearch = document.getElementById('target-quiz-search');
        const targetResults = document.getElementById('target-quiz-results');
        let searchTimer;

        const refreshSelection = () => {
            const count = checkboxes.filter((checkbox) => checkbox.checked).length;
            selectionCount.textContent = `${count} kérdés kijelölve`;
            selectAll.checked = checkboxes.length > 0 && count === checkboxes.length;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        };

        const setAll = (checked) => {
            checkboxes.forEach((checkbox) => checkbox.checked = checked);
            refreshSelection();
        };

        selectAll.addEventListener('change', () => setAll(selectAll.checked));
        selectAllButton.addEventListener('click', () => setAll(true));
        clearButton.addEventListener('click', () => setAll(false));
        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshSelection));

        action.addEventListener('change', () => {
            const changesDifficulty = action.value === 'change_difficulty';
            const movesQuiz = action.value === 'move_to_quiz';
            difficultyField.classList.toggle('hidden', !changesDifficulty);
            targetField?.classList.toggle('hidden', !movesQuiz);
            difficulty.required = changesDifficulty;
            if (targetInput) targetInput.required = movesQuiz;
        });

        targetSearch?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            targetInput.value = '';
            const query = targetSearch.value.trim();
            if (query.length < 2) {
                targetResults.classList.add('hidden');
                return;
            }

            searchTimer = setTimeout(async () => {
                const response = await fetch(`{{ route('admin.quizzes.search') }}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const quizzes = await response.json();
                targetResults.innerHTML = '';

                quizzes.forEach((quiz) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'block w-full text-left px-4 py-3 hover:bg-indigo-50 border-b border-gray-100';
                    button.textContent = `#${quiz.id} · ${quiz.title}${quiz.creator ? ` — ${quiz.creator}` : ''}`;
                    button.addEventListener('click', () => {
                        targetInput.value = quiz.id;
                        targetSearch.value = button.textContent;
                        targetResults.classList.add('hidden');
                    });
                    targetResults.appendChild(button);
                });

                targetResults.classList.toggle('hidden', quizzes.length === 0);
            }, 300);
        });

        bulkForm.addEventListener('submit', (event) => {
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

            if (checkedCount === 0) {
                event.preventDefault();
                window.alert('Jelölj ki legalább egy kérdést a tömeges művelethez!');
                return;
            }

            const actionLabel = action.selectedOptions[0]?.textContent.trim() || 'kiválasztott művelet';
            let detail = '';

            if (action.value === 'change_difficulty') {
                detail = `\nÚj nehézség: ${difficulty.selectedOptions[0]?.textContent.trim() || 'nincs kiválasztva'}`;
            } else if (action.value === 'move_to_quiz') {
                detail = `\nCélkvíz: ${targetSearch?.value || 'nincs kiválasztva'}`;
            }

            const confirmed = window.confirm(
                `Biztosan végrehajtod ezt a műveletet?\n\n` +
                `Művelet: ${actionLabel}\n` +
                `Kijelölt kérdések: ${checkedCount}${detail}`
            );

            if (!confirmed) {
                event.preventDefault();
            }
        });
    })();
</script>

</body>
</html>
