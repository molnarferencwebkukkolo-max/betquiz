<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Új Kérdés Hozzáadása - BetQuiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">

        <!-- Fejléc kontextussal -->
        <div class="flex justify-between items-center mb-6 pb-4 border-b">
            <div>
                    <span class="text-xs font-bold px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full">
                        🎯 {{ $quiz->title }}
                    </span>
                <h1 class="text-2xl font-extrabold text-gray-800 mt-2">➕ Új Kérdés Hozzáadása</h1>
            </div>
            <a href="{{ route('quizzes.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-800">← Vissza a Kvízekhez</a>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl font-bold text-sm space-y-1">
                @foreach($errors->all() as $err)
                    <p>• {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('quizzes.questions.store', $quiz->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Nehézség -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nehézség:</label>
                <select name="difficulty" required class="w-full p-3 border rounded-xl bg-white focus:ring-2 focus:ring-indigo-500">
                    <option value="easy" {{ old('difficulty') == 'easy' ? 'selected' : '' }}>Könnyű</option>
                    <option value="medium" {{ old('difficulty', 'medium') == 'medium' ? 'selected' : '' }}>Közepes</option>
                    <option value="hard" {{ old('difficulty') == 'hard' ? 'selected' : '' }}>Nehéz</option>
                </select>
            </div>

            <!-- Kérdés Szövege -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kérdés Szövege:</label>
                <textarea name="question_text" rows="3" placeholder="Írd ide a kérdést..." class="w-full p-3 border rounded-xl focus:ring-2 focus:ring-indigo-500">{{ old('question_text') }}</textarea>
            </div>

            <!-- Kérdés Kép (Opcionális) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kérdés Kép (Opcionális):</label>
                <input type="file" name="question_image" class="w-full p-2 border rounded-xl bg-gray-50 text-sm">
            </div>

            <!-- Válaszlehetőségek -->
            <div class="pt-4 border-t">
                <label class="block text-base font-extrabold text-gray-800 mb-3">Válaszlehetőségek (Jelöld be a helyes választ!)</label>

                <div class="space-y-3">
                    @for($i = 0; $i < 4; $i++)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-2xl border border-gray-200">
                            <input type="radio" name="correct_option" value="{{ $i }}" {{ old('correct_option', 0) == $i ? 'checked' : '' }} class="w-5 h-5 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-bold text-gray-500 text-sm">{{ chr(65 + $i) }})</span>
                            <input type="text" name="options[{{ $i }}][text]" value="{{ old("options.{$i}.text") }}" placeholder="Válasz szövege..." class="w-full p-2 border rounded-xl text-sm">
                            <input type="file" name="options[{{ $i }}][image]" class="text-xs text-gray-500">
                        </div>
                    @endfor
                </div>
            </div>

            <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-lg rounded-2xl shadow-lg transition">
                💾 Kérdés Mentése a Kvízhez
            </button>
        </form>

    </div>
</div>

</body>
</html>
