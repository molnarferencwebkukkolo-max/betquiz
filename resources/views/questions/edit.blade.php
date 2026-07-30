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

    @if($errors->any())
        <div style="margin-top: 1rem; padding: 1rem; border: 1px solid #fca5a5; border-radius: 1rem; background: #fef2f2; color: #991b1b;">
            <p style="font-weight: 800; margin: 0 0 0.5rem;">A módosításokat nem sikerült elmenteni:</p>
            <ul style="margin: 0; padding-left: 1.25rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="question-edit-form" action="{{ route('questions.update', $question->id) }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1.5rem;">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <label class="form-label">🧩 Kvíz:</label>
                @if(auth()->user()->isUseradmin() || auth()->user()->isHostadmin())
                    <select name="quiz_id" required class="form-select-custom w-100">
                        @foreach($quizzes as $quiz)
                            <option value="{{ $quiz->id }}" {{ $question->quiz_id == $quiz->id ? 'selected' : '' }}>
                                {{ $quiz->title }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="quiz_id" value="{{ $question->quiz_id }}">
                    <div class="form-control-custom w-100" style="background: #f3f4f6; color: #4b5563;">
                        {{ $question->quiz->title }}
                    </div>
                @endif
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
                <div id="question-image-preview-wrap" style="{{ $question->image_path ? '' : 'display: none;' }} margin-bottom: 0.5rem;">
                    <img id="question-image-preview"
                         src="{{ $question->image_path ? asset('storage/' . $question->image_path) : '' }}"
                         class="img-preview-q" alt="Kérdéskép előnézete">
                </div>
                <input type="file" name="question_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                       class="file-input-custom image-preview-input"
                       data-preview-image="question-image-preview"
                       data-preview-wrap="question-image-preview-wrap"
                       data-file-info="question-image-info">
                <p id="question-image-info" style="font-size: 0.75rem; color: #6b7280; margin: 0.4rem 0 0;">
                    {{ $question->image_path ? 'Jelenlegi kép: '.basename($question->image_path) : 'Nincs kép kiválasztva.' }}
                    JPG, PNG vagy WEBP, legfeljebb 5 MB.
                </p>
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
                        <div id="option-image-preview-wrap-{{ $i }}" style="{{ $opt->image_path ? '' : 'display: none;' }} margin-bottom: 0.5rem;">
                            <img id="option-image-preview-{{ $i }}"
                                 src="{{ $opt->image_path ? asset('storage/' . $opt->image_path) : '' }}"
                                 class="img-preview-opt" alt="{{ chr(65 + $i) }} válaszkép előnézete">
                        </div>
                        <input type="file" name="options[{{ $i }}][image]"
                               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                               class="file-input-custom image-preview-input"
                               data-preview-image="option-image-preview-{{ $i }}"
                               data-preview-wrap="option-image-preview-wrap-{{ $i }}"
                               data-file-info="option-image-info-{{ $i }}">
                        <p id="option-image-info-{{ $i }}" style="font-size: 0.75rem; color: #6b7280; margin: 0.4rem 0 0;">
                            {{ $opt->image_path ? 'Jelenlegi kép: '.basename($opt->image_path) : 'Nincs kép kiválasztva.' }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <button id="question-save-button" type="submit" class="btn-save-question">
            <span id="question-save-label">💾 Változtatások Mentése</span>
        </button>
    </form>

</div>

<script>
    document.querySelectorAll('.image-preview-input').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const preview = document.getElementById(input.dataset.previewImage);
            const previewWrap = document.getElementById(input.dataset.previewWrap);
            const fileInfo = document.getElementById(input.dataset.fileInfo);

            if (!file) {
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                input.value = '';
                fileInfo.textContent = 'A kiválasztott kép nagyobb 5 MB-nál.';
                fileInfo.style.color = '#b91c1c';
                return;
            }

            preview.src = URL.createObjectURL(file);
            previewWrap.style.display = '';
            fileInfo.textContent = `Kiválasztva: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
            fileInfo.style.color = '#047857';
        });
    });

    document.getElementById('question-edit-form').addEventListener('submit', () => {
        const button = document.getElementById('question-save-button');
        const label = document.getElementById('question-save-label');
        button.disabled = true;
        button.style.opacity = '0.7';
        label.textContent = '⏳ Mentés folyamatban…';
    });
</script>

</body>
</html>
