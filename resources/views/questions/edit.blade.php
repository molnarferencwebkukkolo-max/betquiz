<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Kérdés Szerkesztése</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl p-8 border border-gray-100">

    <div class="flex justify-between items-center mb-6 pb-3 border-b">
        <h1 class="text-2xl font-extrabold text-gray-800">✏️ Kérdés Szerkesztése</h1>
        <a href="{{ route('questions.index') }}" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 font-bold text-gray-700 rounded-xl text-sm transition">
            Mégse
        </a>
    </div>

    <form action="{{ route('questions.update', $question->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">📂 Kategória:</label>
                <select name="category_id" required class="w-full p-3 border-2 border-gray-200 rounded-xl">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $question->category_id == $cat->id ? 'selected' : '' }}>
                            {{ is_array($cat->name) ? ($cat->name['hu'] ?? reset($cat->name)) : $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">⚡ Nehézség:</label>
                <select name="difficulty" required class="w-full p-3 border-2 border-gray-200 rounded-xl">
                    <option value="easy" {{ $question->difficulty == 'easy' ? 'selected' : '' }}>Könnyű</option>
                    <option value="medium" {{ $question->difficulty == 'medium' ? 'selected' : '' }}>Közepes</option>
                    <option value="hard" {{ $question->difficulty == 'hard' ? 'selected' : '' }}>Nehéz</option>
                </select>
            </div>
        </div>

        <!-- Kérdés Szövege / Képe -->
        <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-3">
            @php
                $qText = is_array($question->question_text) ? ($question->question_text['hu'] ?? reset($question->question_text)) : $question->question_text;
            @endphp
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">❓ Kérdés szövege:</label>
                <input type="text" name="question_text" value="{{ $qText }}" class="w-full p-3 border-2 border-gray-200 rounded-xl">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">🖼️ Kérdés képe:</label>
                @if($question->image_path)
                    <img src="{{ asset('storage/' . $question->image_path) }}" class="h-20 rounded-lg mb-2 border">
                @endif
                <input type="file" name="question_image" accept="image/*" class="w-full text-xs text-gray-500">
            </div>
        </div>

        <!-- Válaszok -->
        <div class="space-y-4">
            <label class="block text-sm font-bold text-gray-800">🎯 Válaszlehetőségek:</label>

            @foreach($question->options as $i => $opt)
                @php
                    $optText = is_array($opt->option_text) ? ($opt->option_text['hu'] ?? reset($opt->option_text)) : $opt->option_text;
                @endphp
                <div class="p-3 border-2 border-gray-200 rounded-xl space-y-2 bg-gray-50/50">
                    <div class="flex items-center gap-3">
                        <input type="radio" name="correct_option" value="{{ $i }}" {{ $opt->is_correct ? 'checked' : '' }} class="w-5 h-5 text-indigo-600">
                        <span class="font-bold text-gray-700">{{ chr(65 + $i) }} opció</span>
                    </div>
                    <input type="text" name="options[{{ $i }}][text]" value="{{ $optText }}" class="w-full p-2 border rounded-lg text-sm">
                    @if($opt->image_path)
                        <img src="{{ asset('storage/' . $opt->image_path) }}" class="h-12 rounded border">
                    @endif
                    <input type="file" name="options[{{ $i }}][image]" accept="image/*" class="w-full text-xs text-gray-500">
                </div>
            @endforeach
        </div>

        <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg rounded-xl shadow-lg transition">
            💾 Változtatások Mentése
        </button>
    </form>

</div>

</body>
</html>
