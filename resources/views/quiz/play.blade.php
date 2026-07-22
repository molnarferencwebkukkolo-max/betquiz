<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BetQuiz - Játék</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0f172a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .quiz-card { background: #1e293b; border-radius: 16px; border: 1px solid #334155; }
        .btn-option { background-color: #334155; color: #fff; border: 2px solid transparent; transition: all 0.2s; font-size: 1.1rem; text-align: left; }
        .btn-option:hover { background-color: #475569; color: #fff; }
        .btn-correct { background-color: #15803d !important; color: white !important; border-color: #22c55e !important; }
        .btn-wrong { background-color: #b91c1c !important; color: white !important; border-color: #ef4444 !important; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">

<div class="container" style="max-width: 650px;">
    <div class="text-center mb-4">
        <h1 class="fw-bold text-warning">🎯 BetQuiz Live</h1>
        <p class="text-secondary">Játékos: {{ auth()->user()->name }}</p>
    </div>

    @if($questions->count() > 0)
        <div id="quiz-container">
            @foreach($questions as $index => $question)
                <div class="quiz-card p-4 mb-4 question-block {{ $index !== 0 ? 'd-none' : '' }}" id="question-{{ $index }}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary px-3 py-2">{{ $question->category->translated_name }}</span>
                        <span class="text-secondary fw-bold">Kérdés {{ $index + 1 }} / {{ $questions->count() }}</span>
                    </div>

                    <h4 class="mb-4 fs-5">{{ $question->translated_text }}</h4>

                    <div class="row g-3">
                        @foreach($question->options->shuffle() as $option)
                            <div class="col-12">
                                <button type="button"
                                        class="btn btn-option w-100 p-3 rounded-3 option-btn"
                                        data-option-id="{{ $option->id }}"
                                        data-question-index="{{ $index }}">
                                    {{ $option->translated_text }}
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="alert alert-dismissible mt-4 d-none result-message fw-bold text-center"></div>
                </div>
            @endforeach

            <!-- Befejező képernyő -->
            <div id="final-screen" class="quiz-card p-5 text-center d-none">
                <h2 class="text-success mb-3">🎉 Gratulálunk!</h2>
                <p class="fs-4">Teljesítetted a kvízt!</p>
                <p class="fs-5">Eredményed: <span id="final-score" class="fw-bold text-warning">0</span> / {{ $questions->count() }} helyes válasz</p>
                <a href="{{ route('dashboard') }}" class="btn btn-warning btn-lg fw-bold mt-3">Vissza a Vezérlőpultra 🏠</a>
            </div>
        </div>
    @else
        <div class="alert alert-danger text-center">Nincsenek elérhető kérdések az adatbázisban!</div>
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

                // Összes gomb letiltása ebben a blokkban, hogy ne lehessen többször kattintani
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
                            this.classList.add('btn-correct');
                            msgDiv.classList.add('alert-success');
                            msgDiv.innerText = "✅ Helyes válasz!";
                            score++;
                        } else {
                            this.classList.add('btn-wrong');
                            msgDiv.classList.add('alert-danger');
                            msgDiv.innerText = "❌ Helytelen válasz!";

                            buttons.forEach(b => {
                                if(b.getAttribute('data-option-id') == data.correct_option_id) {
                                    b.classList.add('btn-correct');
                                }
                            });
                        }

                        // 1.8 másodperc múlva áttérés a következő kérdésre
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
