<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Kérdés Hozzáadása</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">

@include('layouts.navigation')

<div class="max-w-4xl mx-auto">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-extrabold text-gray-800">📝 Kérdések Kezelése</h1>
        <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold text-gray-700 transition">
            🏠 Műszerfal
        </a>
    </div>

    <!-- Visszajelzések -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded-2xl font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl text-sm space-y-1">
            @foreach($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- 1. KÉZI KÉRDÉS FELVITEL -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b">➕ Új kérdés felvitele (Kép / Szöveg)</h2>

            <form action="{{ route('questions.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">📂 Kategória:</label>
                        <select name="category_id" required class="w-full p-3 border-2 border-gray-200 rounded-xl">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">
                                    {{ is_array($cat->name) ? ($cat->name['hu'] ?? reset($cat->name)) : $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">⚡ Nehézség:</label>
                        <select name="difficulty" required class="w-full p-3 border-2 border-gray-200 rounded-xl">
                            <option value="easy">Könnyű (1.3x)</option>
                            <option value="medium" selected>Közepes (1.5x)</option>
                            <option value="hard">Nehéz (2.0x)</option>
                        </select>
                    </div>
                </div>

                <!-- Kérdés Szövege / Képe -->
                <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">❓ Kérdés szövege (Opcionális):</label>
                        <input type="text" name="question_text" placeholder="Pl. Mi látható a képen?"
                               class="w-full p-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">🖼️ Kérdés képe (Opcionális):</label>
                        <input type="file" name="question_image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200">
                    </div>
                </div>

                <!-- 4 Válaszlehetőség -->
                <div class="space-y-4">
                    <label class="block text-sm font-bold text-gray-800">🎯 Válaszlehetőségek (Jelöld be a helyeset!):</label>

                    @for($i = 0; $i < 4; $i++)
                        <div class="p-3 border-2 border-gray-200 rounded-xl space-y-2 relative bg-gray-50/50">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="correct_option" value="{{ $i }}" {{ $i === 0 ? 'checked' : '' }} class="w-5 h-5 text-indigo-600 focus:ring-indigo-500">
                                <span class="font-bold text-gray-700">{{ chr(65 + $i) }} opció</span>
                                @if($i === 0)
                                    <span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-0.5 rounded-full">Alapértelmezett Helyes</span>
                                @endif
                            </div>
                            <input type="text" name="options[{{ $i }}][text]" placeholder="Válasz szövege..." class="w-full p-2 border rounded-lg text-sm">
                            <input type="file" name="options[{{ $i }}][image]" accept="image/*" class="w-full text-xs text-gray-500">
                        </div>
                    @endfor
                </div>

                <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg rounded-xl shadow-lg transition">
                    💾 Kérdés Mentése
                </button>
            </form>
        </div>


        <!-- 2. EXCEL / CSV IMPORTÁLÁS -->
        <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-100 h-fit">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b">📂 Tömeges Importálás</h2>

            <p class="text-xs text-gray-600 leading-relaxed mb-4">
                Hozz létre egy CSV fájlt (pontosvesszővel <code>;</code> elválasztva) az alábbi oszlopsorrenddel:
            </p>

            <div class="bg-gray-50 p-3 rounded-xl border text-xs font-mono text-gray-700 mb-4 overflow-x-auto">
                Kategória; Kérdés; Jó válasz; Rossz válasz 1; RV2; RV3; Nehézség
            </div>

            <form action="{{ route('questions.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">CSV / Excel Fájl:</label>
                    <input type="file" name="csv_file" accept=".csv, .txt, .xlsx" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200">
                </div>

                <button type="submit" class="w-full py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow transition">
                    📥 Importálás Indítása
                </button>
            </form>
        </div>

    </div>

</div>

</body>
</html>
