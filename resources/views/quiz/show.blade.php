<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Játék Beállítása - {{ $quiz->title }}</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body style="padding-bottom: 3rem;">

@include('layouts.navigation')

<div class="quiz-setup-container">

    <a href="{{ route('dashboard') }}" class="nav-link-item" style="display: inline-flex; font-size: 0.75rem; margin-bottom: 1.5rem;">
        ← Vissza a Műszerfalra
    </a>

    <div class="quiz-setup-card">

        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="badge-quiz-title" style="text-transform: uppercase; margin-bottom: 0.5rem;">
                {{ is_array($quiz->category->name ?? null) ? ($quiz->category->name['hu'] ?? reset($quiz->category->name)) : ($quiz->category->name ?? 'Általános') }}
            </span>
            <h1 style="font-size: 1.5rem; font-weight: 900; color: #1f2937; line-height: 1.25; margin-top: 0.5rem; margin-bottom: 0.5rem;">{{ $quiz->title }}</h1>
            <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">{{ $quiz->description ?? 'Nincs külön leírás megadva.' }}</p>
        </div>

        <!-- TÉT, NEHÉZSÉG ÉS JÁTÉKMÓD FORM -->
        <form action="{{ route('quiz.start', $quiz->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
            @csrf

            <!-- 1. JÁTÉKMÓD -->
            <div>
                <label class="setup-section-title">1. Válassz Játékmódot</label>
                <div class="mode-card-grid">
                    <label class="radio-card">
                        <input type="radio" name="mode" value="bet" checked style="accent-color: #4f46e5; width: 1.25rem; height: 1.25rem;">
                        <div>
                            <h4 class="radio-card-title">🎯 Fixed Tétes Mód</h4>
                            <p class="radio-card-desc">Fix tét alapon játszol.</p>
                        </div>
                    </label>

                    <label class="radio-card">
                        <input type="radio" name="mode" value="odds" style="accent-color: #4f46e5; width: 1.25rem; height: 1.25rem;">
                        <div>
                            <h4 class="radio-card-title">🎲 Odds-alapú Mód</h4>
                            <p class="radio-card-desc">Szorzók alapján nyerhetsz.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 2. NEHÉZSÉGI SZINT ÉS SZORZÓ -->
            <div>
                <label class="setup-section-title">2. Válassz Nehézséget (Nyereményszorzó)</label>
                <div class="diff-card-grid">

                    <!-- Könnyű -->
                    <label class="diff-card diff-card-easy">
                        <input type="radio" name="difficulty" value="easy" class="sr-only">
                        <span style="font-size: 1.25rem; display: block; margin-bottom: 0.25rem;">🟢</span>
                        <span style="font-weight: 800; color: #1f2937; font-size: 0.75rem; display: block;">Könnyű</span>
                        <span style="font-size: 0.75rem; font-weight: 900; color: #16a34a;">1.3x szorzó</span>
                    </label>

                    <!-- Közepes -->
                    <label class="diff-card diff-card-medium">
                        <input type="radio" name="difficulty" value="medium" checked class="sr-only">
                        <span style="font-size: 1.25rem; display: block; margin-bottom: 0.25rem;">🟡</span>
                        <span style="font-weight: 800; color: #1f2937; font-size: 0.75rem; display: block;">Közepes</span>
                        <span style="font-size: 0.75rem; font-weight: 900; color: #d97706;">1.5x szorzó</span>
                    </label>

                    <!-- Nehéz -->
                    <label class="diff-card diff-card-hard">
                        <input type="radio" name="difficulty" value="hard" class="sr-only">
                        <span style="font-size: 1.25rem; display: block; margin-bottom: 0.25rem;">🔴</span>
                        <span style="font-weight: 800; color: #1f2937; font-size: 0.75rem; display: block;">Nehéz</span>
                        <span style="font-size: 0.75rem; font-weight: 900; color: #dc2626;">2.0x szorzó</span>
                    </label>

                </div>
            </div>

            <!-- KÉRDÉSSZÁM VÁLASZTÓ (Alapból rejtve: 'd-none') -->
            <div id="question-count-section" class="d-none" style="margin-top: 1rem;">
                <label class="setup-section-title">
                    Hány kérdésre szeretnél válaszolni?
                </label>
                <select name="question_count" class="form-select-custom w-100">
                    <option value="5" selected>5 Kérdés</option>
                    <option value="10">10 Kérdés</option>
                    <option value="15">15 Kérdés</option>
                    <option value="20">20 Kérdés</option>
                </select>
            </div>

            <!-- 🔮 JAVASCRIPT: Csak Odds mód esetén jeleníti meg a kérdésszámot -->
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const modeInputs = document.querySelectorAll('input[name="mode"]');
                    const questionSection = document.getElementById('question-count-section');

                    function toggleQuestionCount() {
                        const selectedMode = document.querySelector('input[name="mode"]:checked')?.value;
                        if (selectedMode === 'odds') {
                            questionSection.classList.remove('d-none');
                        } else {
                            questionSection.classList.add('d-none');
                        }
                    }

                    modeInputs.forEach(input => {
                        input.addEventListener('change', toggleQuestionCount);
                    });

                    // Betöltéskor is lefut
                    toggleQuestionCount();
                });
            </script>

            <!-- 3. TÉT MEGADÁSA -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label class="setup-section-title" style="margin-bottom: 0;">3. Megadott Tét (PT)</label>
                    <span style="font-size: 0.75rem; font-weight: 700; color: #9ca3af;">Egyenleged: <strong style="color: #4f46e5;">{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</strong></span>
                </div>

                <input type="number" name="bet_amount" min="100" max="{{ $user->points ?? 1000 }}" value="1000" step="100" required
                       class="input-bet-amount">
            </div>

            <!-- INDÍTÁS GOMB -->
            <button type="submit" class="btn-start-game-gradient">
                🚀 Tét Rakása & Játék Indítása!
            </button>

        </form>

    </div>

</div>

</body>
</html>
