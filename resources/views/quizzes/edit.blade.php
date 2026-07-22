<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kvíz Szerkesztése - BetQuiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-12">

@include('layouts.navigation')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Vissza gomb -->
    <div class="mb-6">
        <a href="{{ route('quizzes.show', $quiz->id) }}" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition">
            ← Vissza a Kvízhez
        </a>
    </div>

    <div class="bg-white rounded-3xl shadow-md border border-gray-100 p-8">
        <h1 class="text-2xl font-extrabold text-gray-800 mb-6">✏️ Kvíz Alapadatainak Szerkesztése</h1>

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl font-bold space-y-1">
                @foreach($errors->all() as $err)
                    <p>• {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('quizzes.update', $quiz->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Cím -->
            <div>
                <label class="block text-sm font-extrabold text-gray-700 mb-2">Kvíz Címe *</label>
                <input type="text" name="title" value="{{ old('title', $quiz->title) }}" required
                       class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-bold text-gray-800">
            </div>

            <!-- Kategória -->
            <div>
                <label class="block text-sm font-extrabold text-gray-700 mb-2">Kategória *</label>
                <select name="category_id" required class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-semibold text-gray-800">
                    @foreach($categories as $cat)
                        @php
                            $cName = is_array($cat->name) ? ($cat->name['hu'] ?? reset($cat->name)) : $cat->name;
                        @endphp
                        <option value="{{ $cat->id }}" {{ $quiz->category_id == $cat->id ? 'selected' : '' }}>
                            {{ $cName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Leírás -->
            <div>
                <label class="block text-sm font-extrabold text-gray-700 mb-2">Leírás</label>
                <textarea name="description" rows="4"
                          class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-medium text-gray-700"
                          placeholder="Rövid tájékoztató a kvíz témájáról...">{{ old('description', $quiz->description) }}</textarea>
            </div>

            <!-- Fejléckép -->
            <div>
                <label class="block text-sm font-extrabold text-gray-700 mb-2">Fejléckép / Borítókép</label>

                @if($quiz->cover_image)
                    <div class="mb-4">
                        <p class="text-xs text-gray-400 mb-1 font-bold">Jelenlegi kép:</p>
                        <img src="{{ asset('storage/' . $quiz->cover_image) }}" alt="Borítókép" class="h-40 w-full object-cover rounded-2xl border">
                    </div>
                @endif

                <input type="file" name="cover_image" accept="image/*"
                       class="w-full text-sm bg-gray-50 p-3 rounded-2xl border border-gray-200 font-medium text-gray-600">
                <p class="text-xs text-gray-400 mt-1">Megengedett formátumok: JPG, PNG, WEBP (max. 5MB)</p>
            </div>

            <!-- Művelet gombok -->
            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('quizzes.show', $quiz->id) }}" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl transition">
                    Mégse
                </a>
                <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-2xl shadow-lg transition">
                    💾 Módosítások Mentése
                </button>
            </div>
        </form>
    </div>

</div>

</body>
</html>
