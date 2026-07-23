<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Kérdés Szerkesztése</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body style="padding: 1.5rem;">

<div class="q-edit-container">

    <div class="q-header" style="padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb;">
        <h1 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0;">✏️ Kérdés Szerkesztése</h1>
        <a href="{{ route('questions.index') }}" class="nav-link-item">
            Mégse
        </a>
    </div>

    <form action="{{ route('questions.update', $question->id) }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1.5rem;">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <label class="form-label">📂 Kategória:</label>
                <select name="category_id" required class="form-select-custom w-100">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $question->category_id == $cat->id ? 'selected' : '' }}>
                            {{ is_array($cat->name) ? ($cat->name['hu'] ?? reset($cat->name)) : $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">⚡ Nehézség:</label>
                <select name="difficulty" required class="form-select-custom w-100">
                    <option value="easy" {{ $question->difficulty == 'easy' ? 'selected' : '' }}>Könnyű</option>
                    <option value="medium" {{ $question->difficulty == 'medium' ? 'selected' : '' }}>Közepes</option>
                    <option value="hard" {{ $question->difficulty == 'hard' ? 'selected' : '' }}>Nehéz</option>
                </select>
            </div>
        </div>

        <!-- Kérdés Szövege / Képe -->
        <div class="q-section-bg" style="display: flex; flex-direction: column; gap: 0.75rem;">
            @php
                $qText = is_array($question->question_text) ? ($question->question_text['hu'] ?? reset($question->question_text)) : $question->question_text;
            @endphp
            <div>
                <label class="form-label">❓ Kérdés szövege:</label>
                <input type="text" name="question_text" value="{{ $qText }}" class="form-control-custom w-100">
            </div>
            <div>
                <label class="form-label">🖼️ Kérdés képe:</label>
                @if($question->image_path)
                    <div>
                        <img src="{{ asset('storage/' . $question->image_path) }}" class="img-preview-q">
                    </div>
                @endif
                <input type="file" name="question_image" accept="image/*" class="file-input-custom">
            </div>
        </div>

        <!-- Válaszok -->
        <div>
            <label class="form-label" style="font-weight: 700; margin-bottom: 0.75rem;">🎯 Válaszlehetőségek:</label>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach($question->options as $i => $opt)
                    @php
                        $optText = is_array($opt->option_text) ? ($opt->option_text['hu'] ?? reset($opt->option_text)) : $opt->option_text;
                    @endphp
                    <div class="q-option-box">
                        <div class="q-option-header">
                            <input type="radio" name="correct_option" value="{{ $i }}" {{ $opt->is_correct ? 'checked' : '' }} style="width: 1.25rem; height: 1.25rem; accent-color: #4f46e5;">
                            <span style="font-weight: 700; color: #374151;">{{ chr(65 + $i) }} opció</span>
                        </div>
                        <input type="text" name="options[{{ $i }}][text]" value="{{ $optText }}" class="form-control-custom w-100" style="margin-bottom: 0.5rem; font-size: 0.875rem;">
                        @if($opt->image_path)
                            <div style="margin-bottom: 0.5rem;">
                                <img src="{{ asset('storage/' . $opt->image_path) }}" class="img-preview-opt">
                            </div>
                        @endif
                        <input type="file" name="options[{{ $i }}][image]" accept="image/*" class="file-input-custom">
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn-save-question">
            💾 Változtatások Mentése
        </button>
    </form>

</div>

</body>
</html>
