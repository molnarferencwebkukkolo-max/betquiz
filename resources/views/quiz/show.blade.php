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
        $isPublished = ($quiz->status === 'approved' || $quiz->status === 'published');
        $canPublish = ($qCount >= 100);
    @endphp
    {{-- 🛡️ ADMIN BÍRÁLATI PANEL (Kizárólag Useradmin / Hostadmin számára) --}}
    @if(auth()->check() && (auth()->user()->isUseradmin() || auth()->user()->isHostadmin()))
        <div style="background-color: #fef3c7; border: 2px solid #f59e0b; padding: 1rem 1.5rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <strong style="color: #92400e; font-size: 1rem;">🛡️ Adminisztrátori döntés:</strong>
                <span style="color: #b45309; margin-left: 0.5rem; font-size: 0.875rem;">Jelenlegi státusz: <strong style="text-transform: uppercase;">{{ $quiz->status }}</strong></span>
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <!-- APPROVE (Elfogadás) -->
                <form action="{{ route('admin.quizzes.approve', $quiz->id) }}" method="POST">
                    @csrf
                    <button type="submit" style="background-color: #16a34a; color: white; font-weight: 800; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem;">
                        ✅ Approve (Elfogadás)
                    </button>
                </form>

                <!-- REJECT (Elutasítás) -->
                <form action="{{ route('admin.quizzes.reject', $quiz->id) }}" method="POST" onsubmit="return confirm('Biztosan elutasítod ezt a kvízt?');">
                    @csrf
                    <button type="submit" style="background-color: #dc2626; color: white; font-weight: 800; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem;">
                        ❌ Reject (Elutasítás)
                    </button>
                </form>
            </div>
        </div>
    @endif
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
                <p class="text-gray-500 mt-1">{{ $quiz->description ?? 'Nincs megadva leírás.' }}</p>
            </div>

            <!-- Műveleti gombok a fejlécben -->
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">

                <!-- ✏️ Kvíz Szerkesztése Gomb -->
                <a href="{{ route('quizzes.edit', $quiz->id) }}" class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold rounded-2xl transition flex items-center gap-1 text-sm">
                    ✏️ Szerkesztés
                </a>

                <!-- 🚀 Publikálás / Visszavonás Gomb -->
                <form action="{{ route('quizzes.update', $quiz->id) }}" method="POST">
                    @csrf
                    @if($isPublished)
                        <button type="submit" class="px-5 py-3 bg-amber-500 hover:bg-amber-600 text-white font-extrabold rounded-2xl shadow transition flex items-center gap-2 text-sm">
                            🔒 Publikálás Visszavonása
                        </button>
                    @else
                        <button type="submit"
                                {{ !$canPublish ? 'disabled' : '' }}
                                class="px-5 py-3 font-extrabold rounded-2xl shadow transition flex items-center gap-2 text-sm
                                           {{ $canPublish ? 'bg-green-600 hover:bg-green-700 text-white cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}"
                                title="{{ !$canPublish ? 'Még hiányzik ' . (100 - $qCount) . ' db kérdés a publikáláshoz!' : 'Kattints a publikáláshoz!' }}">
                            🚀 Kvíz Publikálása (100/100)
                        </button>
                    @endif
                </form>

                <!-- ➕ Új Kérdés Gomb -->
                <a href="{{ route('questions.create', ['quiz_id' => $quiz->id]) }}" class="btn-primary">
                    ➕ Új kérdés hozzáadása
                </a>
            </div>
        </div>

        <!-- Haladási sáv (Progress Bar) -->
        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 mb-6">
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

            <form action="{{ route('quizzes.questions.import', $quiz->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 items-center">
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

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase font-extrabold text-gray-500 tracking-wider">
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400 font-semibold">
                            Ebben a kvízben még nincsenek kérdések! Használd a fenti CSV importálót vagy adj hozzá egyesével.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
