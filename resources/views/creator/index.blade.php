<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kvízek - BetQuiz</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

@php
    $statusLabels = [
        'approved' => ['Jóváhagyott', 'bg-green-100 text-green-700'],
        'pending' => ['Bírálatra vár', 'bg-amber-100 text-amber-700'],
        'rejected' => ['Elutasítva', 'bg-red-100 text-red-700'],
    ];
@endphp

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800">
                {{ $isAdmin ? 'Összes kvíz (Admin)' : 'Saját kvízeim' }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Keresd, szűrd és kezeld a kvízeket egy helyen.
            </p>
        </div>

        <a href="{{ route('my-quizzes.create') }}"
           class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-2xl shadow-lg transition">
            + Új kvíz nyitása (50k PT)
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded-2xl font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl font-bold">
            @foreach($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 mb-6">
        <form action="{{ route('my-quizzes.index') }}" method="GET"
              class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 items-end">
            @if($isAdmin)
                <input type="hidden" name="view" value="{{ $viewMode }}">
            @endif

            <div class="md:col-span-2">
                <label for="quiz-search" class="block text-xs font-extrabold uppercase tracking-wide text-gray-500 mb-2">
                    Keresés
                </label>
                <input id="quiz-search" type="search" name="q" value="{{ $search }}"
                       placeholder="{{ $isAdmin ? 'Kvíz neve, leírás, címke vagy szerző…' : 'Kvíz neve, leírás vagy címke…' }}"
                       class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="quiz-status" class="block text-xs font-extrabold uppercase tracking-wide text-gray-500 mb-2">
                    Állapot
                </label>
                <select id="quiz-status" name="status"
                        class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500">
                    <option value="">Minden állapot</option>
                    @foreach($statusLabels as $value => [$label])
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="quiz-category" class="block text-xs font-extrabold uppercase tracking-wide text-gray-500 mb-2">
                    Kategória
                </label>
                <select id="quiz-category" name="category_id"
                        class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500">
                    <option value="">Minden kategória</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected($categoryId === $category->id)>
                            {{ $category->translated_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-slate-900 hover:bg-slate-800 text-white font-extrabold rounded-2xl transition">
                    Szűrés
                </button>
                @if($search !== '' || $status !== '' || $categoryId > 0)
                    <a href="{{ route('my-quizzes.index', $isAdmin ? ['view' => $viewMode] : []) }}"
                       class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl transition"
                       title="Szűrők törlése">
                        Törlés
                    </a>
                @endif
            </div>
        </form>
    </section>

    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 mb-5">
        <p class="text-sm font-bold text-gray-500">
            {{ $quizzes->total() }} kvíz található
        </p>

        @if($isAdmin)
            <div class="inline-flex self-start rounded-2xl bg-white border border-gray-200 p-1 shadow-sm" aria-label="Nézetváltó">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'cards', 'page' => null]) }}"
                   class="px-4 py-2 rounded-xl text-sm font-extrabold transition {{ $viewMode === 'cards' ? 'bg-indigo-600 text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
                    Kártyák
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'table', 'page' => null]) }}"
                   class="px-4 py-2 rounded-xl text-sm font-extrabold transition {{ $viewMode === 'table' ? 'bg-indigo-600 text-white shadow' : 'text-gray-600 hover:bg-gray-100' }}">
                    Táblázat
                </a>
            </div>
        @endif
    </div>

    @if($isAdmin && $viewMode === 'table')
        <form id="quiz-bulk-form" action="{{ route('admin.quizzes.bulk-update') }}" method="POST"
              class="bg-white rounded-3xl shadow-sm border border-gray-100 p-4 mb-4">
            @csrf
            @method('PATCH')
            <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                <div class="flex-1">
                    <label for="quiz-bulk-action" class="block text-xs font-extrabold uppercase text-gray-500 mb-2">
                        Tömeges művelet
                    </label>
                    <select id="quiz-bulk-action" name="bulk_action" required
                            class="w-full px-4 py-3 rounded-2xl border border-gray-200">
                        <option value="">Válassz műveletet…</option>
                        <option value="approve">Jóváhagyás</option>
                        <option value="reject">Elutasítás</option>
                        <option value="make_public">Publikussá tétel</option>
                        <option value="make_private">Priváttá tétel</option>
                        <option value="change_owner">Tulajdonos módosítása</option>
                    </select>
                </div>
                <div id="quiz-owner-field" class="flex-1 hidden">
                    <label for="quiz-owner-id" class="block text-xs font-extrabold uppercase text-gray-500 mb-2">
                        Új tulajdonos
                    </label>
                    <select id="quiz-owner-id" name="owner_id"
                            class="w-full px-4 py-3 rounded-2xl border border-gray-200">
                        <option value="">Válassz felhasználót…</option>
                        @foreach($owners as $owner)
                            <option value="{{ $owner->id }}">{{ $owner->name }} ({{ $owner->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div id="quiz-moderation-reason-field" class="lg:basis-full hidden rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <label for="quiz-moderation-reason-preset" class="block text-xs font-extrabold uppercase text-amber-800 mb-2">
                        Gyakori indok
                    </label>
                    <select id="quiz-moderation-reason-preset"
                            class="moderation-reason-preset w-full px-4 py-3 rounded-2xl border border-amber-200 bg-white mb-3">
                        <option value="">Válassz indokot…</option>
                        <option value="A kvíz leírása nem elég részletes.">Hiányos leírás</option>
                        <option value="A kvíz tartalma vagy témája nem felel meg a közzétételi irányelveknek.">Nem megfelelő tartalom vagy téma</option>
                        <option value="A kérdések vagy válaszok minősége további javítást igényel.">Minőségi javítás szükséges</option>
                        <option value="A kvíz duplikált vagy jelentősen átfed egy már meglévő kvízzel.">Duplikált tartalom</option>
                        <option value="A kvíz ideiglenesen további adminisztrátori ellenőrzést igényel.">További ellenőrzés</option>
                    </select>
                    <label for="quiz-moderation-reason" class="block text-xs font-extrabold uppercase text-amber-800 mb-2">
                        Végleges, szerkeszthető indok
                    </label>
                    <textarea id="quiz-moderation-reason" name="moderation_reason" rows="3" maxlength="2000"
                              class="moderation-reason-input w-full px-4 py-3 rounded-2xl border border-amber-200 bg-white"
                              placeholder="Írd le pontosan, mit kell javítania a készítőnek…">{{ old('moderation_reason') }}</textarea>
                    @error('moderation_reason')
                        <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-2xl">
                    Alkalmazás a kijelöltekre
                </button>
            </div>
            <p id="quiz-selection-count" class="text-xs font-bold text-gray-500 mt-3">0 kvíz kijelölve</p>
        </form>

        <section class="bg-white rounded-3xl shadow-md border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-extrabold uppercase tracking-wide text-gray-500">
                        <th class="px-5 py-4">
                            <input id="select-all-quizzes" type="checkbox" class="w-4 h-4 accent-indigo-600"
                                   title="Összes kijelölése ezen az oldalon">
                        </th>
                        <th class="px-5 py-4">Kvíz neve</th>
                        <th class="px-5 py-4">Szerző</th>
                        <th class="px-5 py-4">Állapot</th>
                        <th class="px-5 py-4">Láthatóság</th>
                        <th class="px-5 py-4 text-center">Kérdések</th>
                        <th class="px-5 py-4 text-right">Műveletek</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($quizzes as $quiz)
                        @php
                            [$statusLabel, $statusClasses] = $statusLabels[$quiz->status] ?? [ucfirst($quiz->status), 'bg-gray-100 text-gray-700'];
                        @endphp
                        <tr class="hover:bg-indigo-50/40 transition">
                            <td class="px-5 py-4">
                                <input type="checkbox" name="quiz_ids[]" value="{{ $quiz->id }}"
                                       form="quiz-bulk-form"
                                       class="quiz-row-checkbox w-4 h-4 accent-indigo-600">
                            </td>
                            <td class="px-5 py-4">
                                <a href="{{ route('my-quizzes.show', $quiz) }}"
                                   class="font-extrabold text-gray-800 hover:text-indigo-600">
                                    {{ $quiz->title }}
                                </a>
                                <div class="text-xs text-gray-400 mt-1">{{ $quiz->category?->translated_name ?? 'Általános' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-gray-700">{{ $quiz->creator->name ?? 'Rendszer' }}</div>
                                <div class="text-xs text-gray-400">{{ $quiz->creator->email ?? '' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-extrabold {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-extrabold {{ $quiz->is_public ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $quiz->is_public ? 'Publikus' : 'Privát' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center font-extrabold text-gray-700">
                                {{ $quiz->questions_count }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2 whitespace-nowrap">
                                    <a href="{{ route('my-quizzes.edit', $quiz) }}"
                                       class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                                        Szerkesztés
                                    </a>
                                    <a href="{{ route('my-quizzes.preview', $quiz) }}"
                                       class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition">
                                        Próbajáték
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center text-gray-500 font-bold">
                                Nincs a szűrésnek megfelelő kvíz.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($quizzes as $quiz)
                @php
                    $qCount = $quiz->questions_count;
                    $percent = min(100, $qCount);
                    [$statusLabel, $statusClasses] = $statusLabels[$quiz->status] ?? [ucfirst($quiz->status), 'bg-gray-100 text-gray-700'];
                @endphp

                <article class="bg-white rounded-3xl shadow-md border border-gray-100 overflow-hidden flex flex-col hover:shadow-xl transition">
                    @if($quiz->cover_image)
                        <div class="h-36 overflow-hidden bg-gray-100">
                            <img src="{{ asset('storage/'.$quiz->cover_image) }}" alt="{{ $quiz->title }}"
                                 class="w-full h-full object-cover">
                        </div>
                    @endif

                    <div class="p-6 flex flex-col flex-1">
                        <div class="flex justify-between items-center gap-3 mb-3">
                            <span class="text-xs font-bold px-3 py-1 rounded-full bg-indigo-50 text-indigo-700">
                                {{ $quiz->category?->translated_name ?? 'Általános' }}
                            </span>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $statusClasses }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <h2 class="text-xl font-bold text-gray-800 mb-2">
                            <a href="{{ route('my-quizzes.show', $quiz) }}" class="hover:text-indigo-600 transition">
                                {{ $quiz->title }}
                            </a>
                        </h2>
                        <p class="text-sm text-gray-500 line-clamp-2 mb-4">{{ $quiz->description ?? 'Nincs leírás.' }}</p>

                        @if($quiz->tags->isNotEmpty())
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach($quiz->tags->take(5) as $tag)
                                    <span class="text-xs font-extrabold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-auto">
                            <div class="flex justify-between text-xs font-extrabold text-gray-600 mb-1">
                                <span>Kérdések állása</span>
                                <span>{{ $qCount }} / 100 ({{ $percent }}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3 border overflow-hidden mb-4">
                                <div class="bg-indigo-600 h-3 rounded-full" style="width: {{ $percent }}%"></div>
                            </div>

                            <div class="pt-4 border-t flex flex-wrap justify-between items-center gap-3">
                                <span class="text-xs text-gray-400 font-semibold">
                                    Készítő: {{ $quiz->creator->name ?? 'Rendszer' }}
                                </span>
                                <div class="flex gap-2">
                                    @if($isAdmin)
                                        <a href="{{ route('my-quizzes.preview', $quiz) }}"
                                           class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition">
                                            Próbajáték
                                        </a>
                                    @endif
                                    <a href="{{ route('my-quizzes.show', $quiz) }}"
                                       class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition">
                                        Kezelés
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full bg-white rounded-3xl p-12 text-center text-gray-500 border">
                    <p class="text-xl font-bold mb-2">Nincs a szűrésnek megfelelő kvíz.</p>
                    <p class="text-sm">Módosítsd a keresést vagy töröld a szűrőket.</p>
                </div>
            @endforelse
        </section>
    @endif

    <div class="mt-8">
        {{ $quizzes->links() }}
    </div>
</main>

<script>
    (() => {
        const selectAll = document.getElementById('select-all-quizzes');
        const checkboxes = [...document.querySelectorAll('.quiz-row-checkbox')];
        const selectionCount = document.getElementById('quiz-selection-count');
        const bulkForm = document.getElementById('quiz-bulk-form');
        const bulkAction = document.getElementById('quiz-bulk-action');
        const ownerField = document.getElementById('quiz-owner-field');
        const ownerSelect = document.getElementById('quiz-owner-id');
        const reasonField = document.getElementById('quiz-moderation-reason-field');
        const reasonPreset = document.getElementById('quiz-moderation-reason-preset');
        const reasonInput = document.getElementById('quiz-moderation-reason');

        const refreshSelection = () => {
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            if (selectionCount) selectionCount.textContent = `${checkedCount} kvíz kijelölve`;
            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            }
        };

        selectAll?.addEventListener('change', () => {
            checkboxes.forEach((checkbox) => checkbox.checked = selectAll.checked);
            refreshSelection();
        });
        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshSelection));

        bulkAction?.addEventListener('change', () => {
            const changesOwner = bulkAction.value === 'change_owner';
            const requiresReason = ['reject', 'make_private'].includes(bulkAction.value);
            ownerField?.classList.toggle('hidden', !changesOwner);
            reasonField?.classList.toggle('hidden', !requiresReason);
            if (ownerSelect) ownerSelect.required = changesOwner;
            if (reasonInput) reasonInput.required = requiresReason;
        });

        reasonPreset?.addEventListener('change', () => {
            if (reasonPreset.value && reasonInput) {
                reasonInput.value = reasonPreset.value;
                reasonInput.focus();
            }
        });

        bulkForm?.addEventListener('submit', (event) => {
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

            if (checkedCount === 0) {
                event.preventDefault();
                window.alert('Jelölj ki legalább egy kvízt a tömeges művelethez!');
                return;
            }

            const actionLabel = bulkAction.selectedOptions[0]?.textContent.trim() || 'kiválasztott művelet';
            const ownerLabel = bulkAction.value === 'change_owner'
                ? `\nÚj tulajdonos: ${ownerSelect.selectedOptions[0]?.textContent.trim() || 'nincs kiválasztva'}`
                : '';

            const confirmed = window.confirm(
                `Biztosan végrehajtod ezt a műveletet?\n\n` +
                `Művelet: ${actionLabel}\n` +
                `Kijelölt kvízek: ${checkedCount}${ownerLabel}`
            );

            if (!confirmed) {
                event.preventDefault();
            }
        });
    })();
</script>

</body>
</html>
