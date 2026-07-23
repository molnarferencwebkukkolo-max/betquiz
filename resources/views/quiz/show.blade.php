<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Játék Beállítása - {{ $quiz->title }}</title>

    <!-- Tailwind CSS (A navigáció és a keretrendszer működéséhez) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body style="padding-bottom: 3rem; background-color: #f3f4f6;">

@include('layouts.navigation')

<div class="quiz-setup-container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">

    <a href="{{ route('dashboard') }}" class="nav-link-item" style="display: inline-flex; font-size: 0.875rem; margin-bottom: 1.5rem; text-decoration: none;">
        ← Vissza a Műszerfalra
    </a>

    <div class="quiz-setup-card" style="background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">

        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="badge-quiz-title" style="text-transform: uppercase; margin-bottom: 0.5rem; display: inline-block;">
                {{ is_array($quiz->category->name ?? null) ? ($quiz->category->name['hu'] ?? reset($quiz->category->name)) : ($quiz->category->name ?? 'Általános') }}
            </span>
            <h1 style="font-size: 1.5rem; font-weight: 900; color: #1f2937; line-height: 1.25; margin-top: 0.5rem; margin-bottom: 0.5rem;">{{ $quiz->title }}</h1>
            <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">{{ $quiz->description ?? 'Nincs külön leírás megadva.' }}</p>
        </div>

        <!-- TÉT, NEHÉZSÉG ÉS JÁTÉKMÓD FORM -->
        <form action="{{ route('quiz.start', $quiz->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
            @csrf

            <!-- 1. JÁTÉKMÓD -->
            <div>
                <label class="setup-section-title" style="font-weight: 800; display: block; margin-bottom: 0.5rem;">1. Válassz Játékmódot</label>
                <div class="mode-card-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <label class="radio-card" style="display: flex; gap: 0.75rem; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 0.75rem; cursor: pointer;">
                        <input type="radio" name="mode" value="bet" checked style="accent-color: #4f46e5; width: 1.25rem; height: 1.25rem;">
                        <div>
                            <h4 class="radio-card-title" style="font-weight: 700; margin: 0;">🎯 Fixed Tétes Mód</h4>
                            <p class="radio-card-desc" style="font-size: 0.75rem; color: #6b7280; margin: 0;">Fix tét alapon játszol.</p>
                        </div>
                    </label>

                    <label class="radio-card" style="display: flex; gap: 0.75rem; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 0.75rem; cursor: pointer;">
                        <input type="radio" name="mode" value="odds" style="accent-color: #4f46e5; width: 1.25rem; height: 1.25rem;">
                        <div>
                            <h4 class="radio-card-title" style="font-weight: 700; margin: 0;">🎲 Odds-alapú Mód</h4>
                            <p class="radio-card-desc" style="font-size: 0.75rem; color: #6b7280; margin: 0;">Szorzók alapján nyerhetsz.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 2. NEHÉZSÉGI SZINT ÉS SZORZÓ -->
            <div>
                <label class="setup-section-title" style="font-weight: 800; display: block; margin-bottom: 0.5rem;">2. Válassz Nehézséget (Nyereményszorzó)</label>
                <div class="diff-card-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; text-align: center;">

                    <!-- Könnyű -->
                    <label class="diff-card diff-card-easy" style="padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.75rem; cursor: pointer;">
                        <input type="radio" name="difficulty" value="easy" style="display: none;">
                        <span style="font-size: 1.25rem; display: block; margin-bottom: 0.25rem;">🟢</span>
                        <span style="font-weight: 800; color: #1f2937; font-size: 0.75rem; display: block;">Könnyű</span>
                        <span style="font-size: 0.75rem; font-weight: 900; color: #16a34a;">1.3x szorzó</span>
                    </label>

                    <!-- Közepes -->
                    <label class="diff-card diff-card-medium" style="padding: 0.75rem; border: 2px solid #4f46e5; border-radius: 0.75rem; cursor: pointer; background-color: #f5f3ff;">
                        <input type="radio" name="difficulty" value="medium" checked style="display: none;">
                        <span style="font-size: 1.25rem; display: block; margin-bottom: 0.25rem;">🟡</span>
                        <span style="font-weight: 800; color: #1f2937; font-size: 0.75rem; display: block;">Közepes</span>
                        <span style="font-size: 0.75rem; font-weight: 900; color: #d97706;">1.5x szorzó</span>
                    </label>

                    <!-- Nehéz -->
                    <label class="diff-card diff-card-hard" style="padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.75rem; cursor: pointer;">
                        <input type="radio" name="difficulty" value="hard" style="display: none;">
                        <span style="font-size: 1.25rem; display: block; margin-bottom: 0.25rem;">🔴</span>
                        <span style="font-weight: 800; color: #1f2937; font-size: 0.75rem; display: block;">Nehéz</span>
                        <span style="font-size: 0.75rem; font-weight: 900; color: #dc2626;">2.0x szorzó</span>
                    </label>

                </div>
            </div>

            <!-- KÉRDÉSSZÁM VÁLASZTÓ (Kezdetben elrejtve inline style-lal) -->
            <div id="question-count-section" style="display: none; margin-top: 1rem;">
                <label class="setup-section-title" style="font-weight: 800; display: block; margin-bottom: 0.5rem;">
                    Hány kérdésre szeretnél válaszolni?
                </label>
                <select name="question_count" class="form-select-custom" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;">
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
                            questionSection.style.display = 'block';
                        } else {
                            questionSection.style.display = 'none';
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
                    <label class="setup-section-title" style="font-weight: 800; margin-bottom: 0;">3. Megadott Tét (PT)</label>
                    <span style="font-size: 0.75rem; font-weight: 700; color: #9ca3af;">Egyenleged: <strong style="color: #4f46e5;">{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</strong></span>
                </div>

                <input type="number" name="bet_amount" min="100" max="{{ $user->points ?? 1000 }}" value="1000" step="100" required
                       class="input-bet-amount" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1.125rem; font-weight: 700;">
            </div>

            <!-- INDÍTÁS GOMB -->
            <button type="submit" class="btn-start-game-gradient" style="background: linear-gradient(to right, #4f46e5, #7c3aed); color: white; font-weight: 800; padding: 1rem; border-radius: 0.75rem; border: none; cursor: pointer; font-size: 1.125rem; width: 100%;">
                🚀 Tét Rakása & Játék Indítása!
            </button>

        </form>

    </div>

</div>

</body>
</html>
