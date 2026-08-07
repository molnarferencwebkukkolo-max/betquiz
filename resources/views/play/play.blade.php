<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KwizzGo - Játék</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="play-wrapper">

<div class="play-container">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h1 class="auth-title">🎯 KwizzGo Live</h1>
        <p style="color: #6b7280; font-weight: 600; margin-top: 0.25rem;">Játékos: {{ auth()->user()->name }}</p>
    </div>

    @if($questions->count() > 0)
        <div id="quiz-container">
            @foreach($questions as $index => $question)
                <div class="play-card question-block {{ $index !== 0 ? 'd-none' : '' }}" id="question-{{ $index }}">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span class="badge-quiz-title">{{ $question->category->translated_name ?? 'Kvíz' }}</span>
                        <span style="color: #6b7280; font-weight: 700; font-size: 0.875rem;">Kérdés {{ $index + 1 }} / {{ $questions->count() }}</span>
                    </div>

                    <h4 style="font-size: 1.25rem; font-weight: 700; color: #1f2937; margin-bottom: 1.5rem;">{{ $question->translated_text }}</h4>

                    <!-- KÉRDEZETT KÉP HELYE -->
                    @if($question->image_path)
                        <div style="text-align: center; margin-bottom: 1.5rem;">
                            <img src="{{ asset('storage/' . $question->image_path) }}"
                                 alt="Kérdés illusztráció"
                                 class="question-img-live">
                        </div>
                    @endif

                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        @foreach($question->options->shuffle() as $option)
                            <button type="button"
                                    class="btn-quiz-option option-btn"
                                    data-option-id="{{ $option->id }}"
                                    data-question-index="{{ $index }}">
                                {{ $option->translated_text }}
                            </button>
                        @endforeach
                    </div>

                    <div class="alert-game-msg d-none result-message"></div>
                </div>
            @endforeach

            <!-- Befejező képernyő -->
            <div id="final-screen" class="play-card d-none" style="text-align: center; padding: 3rem 2rem;">
                <h2 style="color: #166534; font-size: 1.875rem; font-weight: 800; margin-bottom: 1rem;">🎉 Gratulálunk!</h2>
                <p style="font-size: 1.25rem; color: #374151; margin-bottom: 0.5rem;">Teljesítetted a kvízt!</p>
                <p style="font-size: 1.125rem; color: #4b5563; margin-bottom: 2rem;">
                    Eredményed: <span id="final-score" style="font-weight: 800; color: #4f46e5;">0</span> / {{ $questions->count() }} helyes válasz
                </p>
                <a href="{{ route('dashboard') }}" class="btn-primary-purple" style="display: inline-block; text-decoration: none;">
                    Vissza a Vezérlőpultra 🏠
                </a>
            </div>
        </div>
    @else
        <div class="alert-danger-custom" style="text-align: center;">Nincsenek elérhető kérdések az adatbázisban!</div>
    @endif
</div>

<script>
    let score = 0;
    let totalQuestions = {{ $questions->count() }};

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.option-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                let optionId = this.getAttribute('data-option-id');
                let qIndex = parseInt(this.getAttribute('data-question-index'));
                let currentBlock = document.getElementById(`question-${qIndex}`);
                let buttons = currentBlock.querySelectorAll('.option-btn');

                buttons.forEach(b => b.disabled = true);

                fetch('{{ route("quiz.check") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ option_id: optionId })
                })
                    .then(response => response.json())
                    .then(data => {
                        let msgDiv = currentBlock.querySelector('.result-message');
                        msgDiv.classList.remove('d-none');

                        if(data.is_correct) {
                            this.classList.add('btn-quiz-correct');
                            msgDiv.classList.add('alert-game-success');
                            msgDiv.innerText = "✅ Helyes válasz!";
                            score++;
                        } else {
                            this.classList.add('btn-quiz-wrong');
                            msgDiv.classList.add('alert-game-danger');
                            msgDiv.innerText = "❌ Helytelen válasz!";

                            buttons.forEach(b => {
                                if(b.getAttribute('data-option-id') == data.correct_option_id) {
                                    b.classList.add('btn-quiz-correct');
                                }
                            });
                        }

                        setTimeout(() => {
                            currentBlock.classList.add('d-none');
                            let nextIndex = qIndex + 1;

                            if(nextIndex < totalQuestions) {
                                document.getElementById(`question-${nextIndex}`).classList.remove('d-none');
                            } else {
                                document.getElementById('final-score').innerText = score;
                                document.getElementById('final-screen').classList.remove('d-none');
                            }
                        }, 1800);
                    });
            });
        });
    });
</script>

</body>
</html>
