<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Új Kvíz Nyitása - KwizzGo</title>
    <!-- Központi Stíluslap -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="editor-page min-h-screen pb-12">

@include('layouts.navigation')

<div class="editor-shell max-w-4xl mx-auto px-4 py-8">

    <div class="editor-card bg-white rounded-3xl shadow-xl p-8 border border-gray-100">

        <div class="flex justify-between items-center mb-6 pb-4 border-b">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-800">🚀 Új Kvíz Nyitása</h1>
                <p class="text-sm text-gray-500">
                    {{ $isAdmin
                        ? 'Hozd létre a kvízt, majd tölts fel tetszőleges számú kérdést egyenként vagy CSV-ből.'
                        : 'Nyiss új témát, töltsd fel az első 10 mintakérdést a bírálathoz!' }}
                </p>
            </div>
            <div class="bg-amber-100 text-amber-800 font-extrabold px-4 py-2 rounded-2xl text-sm border border-amber-200">
                💰 {{ $creationCost === 0 ? 'Adminisztrátoroknak ingyenes' : '50 000 PT' }}
            </div>
        </div>

        @if($errors->has('error'))
            <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl font-bold">
                {{ $errors->first('error') }}
            </div>
        @endif

        <form action="{{ route('my-quizzes.store') }}" method="POST" class="space-y-8">
            @csrf

            <!-- 1. Kvíz Alapadatok -->
            <div class="bg-indigo-50/50 rounded-2xl p-6 border border-indigo-100 space-y-4">
                <h2 class="text-lg font-bold text-indigo-900 mb-2">1. Kvíz Alapadatok</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kvíz Címe:</label>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="Pl. Agymenők Mesterkvíz" required class="w-full p-3 border rounded-xl">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Főkategória:</label>
                        <select name="category_id" required class="w-full p-3 border rounded-xl bg-white">
                            <option value="">-- Válassz kategóriát --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">
                                    {{ is_array($cat->name) ? ($cat->name['hu'] ?? reset($cat->name)) : $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Rövid Leírás:</label>
                    <textarea name="description" rows="2" placeholder="Miről szól ez a kvíz?" class="w-full p-3 border rounded-xl">{{ old('description') }}</textarea>
                </div>
            </div>

            @unless($isAdmin)
            <!-- 2. A 10 Minta Kérdés -->
            <div class="space-y-6">
                <h2 class="text-xl font-extrabold text-gray-800">2. Minta Kérdések Feltöltése (Pontosan 10 db)</h2>

                @for($i = 0; $i < 10; $i++)
                    <div class="p-6 bg-gray-50 rounded-2xl border border-gray-200 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="font-extrabold text-indigo-600 text-lg">❓ {{ $i + 1 }}. Kérdés</span>
                            <select name="questions[{{ $i }}][difficulty]" class="p-2 border rounded-lg text-xs font-bold">
                                <option value="easy">Könnyű</option>
                                <option value="medium" selected>Közepes</option>
                                <option value="hard">Nehéz</option>
                            </select>
                        </div>

                        <div>
                            <input type="text" name="questions[{{ $i }}][text]" placeholder="Írd ide a kérdést..." required class="w-full p-3 border rounded-xl">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @for($j = 0; $j < 4; $j++)
                                <div class="flex items-center gap-2 p-2 bg-white rounded-xl border">
                                    <input type="radio" name="questions[{{ $i }}][correct]" value="{{ $j }}" {{ $j === 0 ? 'checked' : '' }} class="w-4 h-4 text-indigo-600">
                                    <input type="text" name="questions[{{ $i }}][options][{{ $j }}]" placeholder="{{ chr(65 + $j) }} válasz" required class="w-full text-sm p-1 border-0 focus:ring-0">
                                </div>
                            @endfor
                        </div>
                        <span class="text-xs text-gray-400 italic">* Jelöld ki a rádiógombbal a helyes választ!</span>
                    </div>
                @endfor
            </div>
            @endunless

            <!-- Nyitás gomb -->
            <button type="submit" onclick="return confirm('{{ $isAdmin ? 'Biztosan létrehozod a kvízt?' : 'Biztosan elindítod a kvíznyitást 50 000 PT-ért?' }}');" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-lg rounded-2xl shadow-xl transition">
                🚀 {{ $isAdmin ? 'Kvíz létrehozása' : 'Kvíz benyújtása bírálatra (50 000 PT)' }}
            </button>
        </form>

    </div>

</div>

</body>
</html>
