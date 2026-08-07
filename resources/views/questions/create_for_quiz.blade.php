<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Új Kérdés Hozzáadása - KwizzGo</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body style="padding-bottom: 3rem;">

@include('layouts.navigation')

<div class="quiz-q-container">
    <div class="quiz-q-card">

        <!-- Fejléc kontextussal -->
        <div class="quiz-q-header">
            <div>
                <span class="badge-quiz-title">
                    🎯 {{ $quiz->title }}
                </span>
                <h1 class="quiz-q-title">➕ Új Kérdés Hozzáadása</h1>
            </div>
            <a href="{{ route('quizzes.index') }}" class="nav-link-item">← Vissza a Kvízekhez</a>
        </div>

        @if($errors->any())
            <div class="alert-danger-custom">
                @foreach($errors->all() as $err)
                    <p>• {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('quizzes.questions.store', $quiz->id) }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.5rem;">
            @csrf

            <!-- Nehézség -->
            <div class="form-group">
                <label class="form-label">Nehézség:</label>
                <select name="difficulty" required class="form-select-custom w-100">
                    <option value="easy" {{ old('difficulty') == 'easy' ? 'selected' : '' }}>Könnyű</option>
                    <option value="medium" {{ old('difficulty', 'medium') == 'medium' ? 'selected' : '' }}>Közepes</option>
                    <option value="hard" {{ old('difficulty') == 'hard' ? 'selected' : '' }}>Nehéz</option>
                </select>
            </div>

            <!-- Kérdés Szövege -->
            <div class="form-group">
                <label class="form-label">Kérdés Szövege:</label>
                <textarea name="question_text" rows="3" placeholder="Írd ide a kérdést..." class="form-textarea-custom">{{ old('question_text') }}</textarea>
            </div>

            <!-- Kérdés Kép (Opcionális) -->
            <div class="form-group">
                <label class="form-label">Kérdés Kép (Opcionális):</label>
                <input type="file" name="question_image" class="file-input-custom">
            </div>

            <!-- Válaszlehetőségek -->
            <div style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <label class="form-label" style="font-size: 1rem; font-weight: 800; color: #1f2937; margin-bottom: 0.75rem;">Válaszlehetőségek (Jelöld be a helyes választ!)</label>

                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @for($i = 0; $i < 4; $i++)
                        <div class="quiz-option-row">
                            <input type="radio" name="correct_option" value="{{ $i }}" {{ old('correct_option', 0) == $i ? 'checked' : '' }} style="width: 1.25rem; height: 1.25rem; accent-color: #4f46e5;">
                            <span style="font-weight: 700; color: #6b7280; font-size: 0.875rem;">{{ chr(65 + $i) }})</span>
                            <input type="text" name="options[{{ $i }}][text]" value="{{ old("options.{$i}.text") }}" placeholder="Válasz szövege..." class="form-control-custom w-100" style="font-size: 0.875rem;">
                            <input type="file" name="options[{{ $i }}][image]" class="file-input-custom">
                        </div>
                    @endfor
                </div>
            </div>

            <button type="submit" class="btn-save-quiz-question">
                💾 Kérdés Mentése a Kvízhez
            </button>
        </form>

    </div>
</div>

</body>
</html>
