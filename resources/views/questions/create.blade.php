<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Kérdés Hozzáadása</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body>

@include('layouts.navigation')

<div class="q-container">

    <div class="q-header">
        <h1 class="q-title">📝 Kérdések Kezelése</h1>
        <a href="{{ route('dashboard') }}" class="nav-pill-custom">
            🏠 Műszerfal
        </a>
    </div>

    <!-- Visszajelzések -->
    @if(session('success'))
        <div class="alert-success-custom">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-danger-custom">
            @foreach($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="q-grid">

        <!-- 1. KÉZI KÉRDÉS FELVITEL -->
        <div class="q-card">
            <h2 class="q-card-title">➕ Új kérdés felvitele (Kép / Szöveg)</h2>

            <form action="{{ route('questions.store') }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.25rem;">
                @csrf

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label class="form-label">📂 Kategória:</label>
                        <select name="category_id" required class="form-select-custom w-100">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">
                                    {{ is_array($cat->name) ? ($cat->name['hu'] ?? reset($cat->name)) : $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">⚡ Nehézség:</label>
                        <select name="difficulty" required class="form-select-custom w-100">
                            <option value="easy">Könnyű (1.3x)</option>
                            <option value="medium" selected>Közepes (1.5x)</option>
                            <option value="hard">Nehéz (2.0x)</option>
                        </select>
                    </div>
                </div>

                <!-- Kérdés Szövege / Képe -->
                <div class="q-section-bg" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div>
                        <label class="form-label">❓ Kérdés szövege (Opcionális):</label>
                        <input type="text" name="question_text" placeholder="Pl. Mi látható a képen?" class="form-control-custom w-100">
                    </div>
                    <div>
                        <label class="form-label">🖼️ Kérdés képe (Opcionális):</label>
                        <input type="file" name="question_image" accept="image/*" class="file-input-custom">
                    </div>
                </div>

                <!-- 4 Válaszlehetőség -->
                <div>
                    <label class="form-label" style="font-weight: 700; margin-bottom: 0.75rem;">🎯 Válaszlehetőségek (Jelöld be a helyeset!):</label>

                    @for($i = 0; $i < 4; $i++)
                        <div class="q-option-box">
                            <div class="q-option-header">
                                <input type="radio" name="correct_option" value="{{ $i }}" {{ $i === 0 ? 'checked' : '' }} style="width: 1.25rem; height: 1.25rem; accent-color: #4f46e5;">
                                <span style="font-weight: 700; color: #374151;">{{ chr(65 + $i) }} opció</span>
                                @if($i === 0)
                                    <span class="badge-default-correct">Alapértelmezett Helyes</span>
                                @endif
                            </div>
                            <input type="text" name="options[{{ $i }}][text]" placeholder="Válasz szövege..." class="form-control-custom w-100" style="margin-bottom: 0.5rem; font-size: 0.875rem;">
                            <input type="file" name="options[{{ $i }}][image]" accept="image/*" class="file-input-custom">
                        </div>
                    @endfor
                </div>

                <button type="submit" class="btn-save-question">
                    💾 Kérdés Mentése
                </button>
            </form>
        </div>


        <!-- 2. EXCEL / CSV IMPORTÁLÁS -->
        <div class="q-card" style="height: fit-content;">
            <h2 class="q-card-title">📂 Tömeges Importálás</h2>

            <p style="font-size: 0.75rem; color: #4b5563; line-height: 1.5; margin-bottom: 1rem;">
                Hozz létre egy CSV fájlt (pontosvesszővel <code>;</code> elválasztva) az alábbi oszlopsorrenddel:
            </p>

            <div class="csv-example-box">
                Kategória; Kérdés; Jó válasz; Rossz válasz 1; RV2; RV3; Nehézség
            </div>

            <form action="{{ route('questions.import') }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1rem;">
                @csrf
                <div>
                    <label class="form-label">CSV / Excel Fájl:</label>
                    <input type="file" name="csv_file" accept=".csv, .txt, .xlsx" required class="file-input-custom">
                </div>

                <button type="submit" class="btn-import-csv">
                    📥 Importálás Indítása
                </button>
            </form>
        </div>

    </div>

</div>

</body>
</html>
