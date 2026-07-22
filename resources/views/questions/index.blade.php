<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kérdésbank - BetQuiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Fejléc -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800">❓ Kérdésbank</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $user->isUseradmin() ? 'Az adatbázisban szereplő összes kérdés és azok kvízei.' : 'A saját kvízeidhez feltöltött kérdések.' }}
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('questions.create') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow transition text-sm">
                ➕ Új Kérdés Hozzáadása
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded-2xl font-bold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Kérdések Táblázat -->
    <div class="bg-white rounded-3xl shadow-md border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase font-extrabold text-gray-500 tracking-wider">
                    <th class="p-4">Kérdés Szövege</th>
                    <th class="p-4">Tartozó Kvíz</th>
                    <th class="p-4">Nehézség</th>
                    <th class="p-4">Helyes Válasz</th>
                    <th class="p-4 text-right">Műveletek</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($questions as $question)
                    @php
                        $qText = is_array($question->question_text)
                            ? ($question->question_text['hu'] ?? reset($question->question_text))
                            : $question->question_text;

                        $correctOpt = $question->options->firstWhere('is_correct', true);
                        $cText = $correctOpt ? (is_array($correctOpt->option_text) ? ($correctOpt->option_text['hu'] ?? reset($correctOpt->option_text)) : $correctOpt->option_text) : '-';
                    @endphp

                    <tr class="hover:bg-gray-50/50 transition">
                        <!-- Kérdés -->
                        <td class="p-4 font-semibold text-gray-800 max-w-md">
                            {{ $qText }}
                        </td>

                        <!-- Tartozó Kvíz Badge -->
                        <td class="p-4">
                            @if($question->quiz)
                                <span class="inline-block px-3 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs rounded-xl">
                                            🎯 {{ $question->quiz->title }}
                                        </span>
                            @else
                                <span class="inline-block px-2.5 py-1 bg-gray-100 text-gray-500 font-semibold text-xs rounded-xl">
                                            Nincs Kvízhez kötve
                                        </span>
                            @endif
                        </td>

                        <!-- Nehézség -->
                        <td class="p-4">
                            @if($question->difficulty === 'easy')
                                <span class="px-2.5 py-1 bg-green-100 text-green-700 font-extrabold text-xs rounded-full">Könnyű</span>
                            @elseif($question->difficulty === 'hard')
                                <span class="px-2.5 py-1 bg-red-100 text-red-700 font-extrabold text-xs rounded-full">Nehéz</span>
                            @else
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-700 font-extrabold text-xs rounded-full">Közepes</span>
                            @endif
                        </td>

                        <!-- Helyes Válasz -->
                        <td class="p-4 text-emerald-600 font-bold">
                            ✓ {{ $cText }}
                        </td>

                        <!-- Műveletek -->
                        <td class="p-4 text-right space-x-2">
                            <a href="{{ route('questions.edit', $question->id) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">
                                ✏️ Szerkesztés
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-400 font-semibold">
                            Még nincsenek kérdések az adatbázisban!
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <!-- Lapozó -->
        <div class="p-4 border-t border-gray-100">
            {{ $questions->links() }}
        </div>
    </div>

</div>

</body>
</html>
