<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Próbajáték: {{ $quiz->title }} - BetQuiz</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen pb-12">

@include('layouts.navigation')

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('my-quizzes.index', ['view' => 'table']) }}"
               class="text-sm font-bold text-gray-500 hover:text-gray-800 transition">
                ← Vissza a kvízekhez
            </a>
            <h1 class="text-3xl font-black text-gray-900 mt-3">{{ $quiz->title }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                Admin próbajáték · {{ $questions->count() }} véletlen kérdés
            </p>
        </div>
        <span class="self-start px-4 py-2 rounded-full bg-amber-100 text-amber-800 text-sm font-extrabold">
            0 PT · nincs statisztikamentés
        </span>
    </div>

    @if($questions->isEmpty())
        <section class="bg-white rounded-3xl shadow-md border border-gray-100 p-12 text-center">
            <h2 class="text-2xl font-black text-gray-800 mb-2">Ehhez a kvízhez még nincs aktív kérdés.</h2>
            <a href="{{ route('my-quizzes.show', $quiz) }}"
               class="inline-flex mt-5 px-5 py-3 bg-indigo-600 text-white font-bold rounded-2xl">
                Kvíz kezelése
            </a>
        </section>
    @else
        <section class="bg-white rounded-3xl shadow-md border border-gray-100 p-5 mb-5">
            <div class="flex justify-between text-sm font-extrabold text-gray-600 mb-2">
                <span id="preview-progress-label">1 / {{ $questions->count() }}. kérdés</span>
                <span id="preview-score">0 helyes</span>
            </div>
            <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                <div id="preview-progress-bar" class="h-full bg-indigo-600 rounded-full transition-all duration-300"
                     style="width: {{ 100 / $questions->count() }}%"></div>
            </div>
        </section>

        <div id="preview-questions">
            @foreach($questions as $questionIndex => $question)
                @php
                    $questionText = is_array($question->question_text)
                        ? ($question->question_text['hu'] ?? $question->question_text['en'] ?? reset($question->question_text))
                        : $question->question_text;
                @endphp

                <section class="preview-question bg-white rounded-3xl shadow-xl border border-gray-100 p-6 sm:p-8 {{ $questionIndex > 0 ? 'hidden' : '' }}"
                         data-question-index="{{ $questionIndex }}">
                    <div class="flex justify-between items-center gap-3 mb-5">
                        <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-extrabold uppercase">
                            {{ $question->difficulty ?? 'medium' }}
                        </span>
                        <span class="text-xs font-bold text-gray-400">Kérdés #{{ $question->id }}</span>
                    </div>

                    @if($question->image_path)
                        <img src="{{ asset('storage/'.$question->image_path) }}" alt=""
                             class="w-full max-h-72 object-contain rounded-2xl bg-gray-50 border mb-6">
                    @endif

                    <h2 class="text-2xl font-black text-gray-900 mb-7">{{ $questionText }}</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($question->options->shuffle() as $option)
                            @php
                                $optionText = is_array($option->option_text)
                                    ? ($option->option_text['hu'] ?? $option->option_text['en'] ?? reset($option->option_text))
                                    : $option->option_text;
                            @endphp
                            <button type="button"
                                    class="preview-option text-left p-4 rounded-2xl border-2 border-gray-200 bg-white hover:border-indigo-400 hover:bg-indigo-50 transition font-bold text-gray-800 disabled:cursor-default"
                                    data-correct="{{ $option->is_correct ? '1' : '0' }}">
                                @if($option->image_path)
                                    <img src="{{ asset('storage/'.$option->image_path) }}" alt=""
                                         class="w-full h-32 object-contain rounded-xl bg-gray-50 mb-3">
                                @endif
                                <span>{{ $optionText ?: 'Képes válasz' }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="preview-feedback hidden mt-6 p-4 rounded-2xl font-extrabold"></div>

                    <div class="flex justify-end mt-6">
                        <button type="button"
                                class="preview-next hidden px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-2xl transition">
                            {{ $loop->last ? 'Eredmény megtekintése' : 'Következő kérdés →' }}
                        </button>
                    </div>
                </section>
            @endforeach
        </div>

        <section id="preview-result" class="hidden bg-white rounded-3xl shadow-xl border border-gray-100 p-10 text-center">
            <div class="text-6xl mb-4">🏁</div>
            <h2 class="text-3xl font-black text-gray-900 mb-3">Próbajáték vége</h2>
            <p id="preview-result-text" class="text-xl font-bold text-indigo-700 mb-2"></p>
            <p class="text-sm text-gray-500 mb-7">Pont és statisztika nem változott.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('my-quizzes.preview', $quiz) }}"
                   class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-2xl">
                    Új véletlen kérdéssor
                </a>
                <a href="{{ route('my-quizzes.edit', $quiz) }}"
                   class="px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold rounded-2xl">
                    Kvíz szerkesztése
                </a>
            </div>
        </section>
    @endif
</main>

@if($questions->isNotEmpty())
    <script>
        (() => {
            const questions = [...document.querySelectorAll('.preview-question')];
            const total = questions.length;
            let current = 0;
            let score = 0;

            const progressLabel = document.getElementById('preview-progress-label');
            const progressBar = document.getElementById('preview-progress-bar');
            const scoreLabel = document.getElementById('preview-score');
            const result = document.getElementById('preview-result');

            questions.forEach((question) => {
                const options = [...question.querySelectorAll('.preview-option')];
                const feedback = question.querySelector('.preview-feedback');
                const nextButton = question.querySelector('.preview-next');

                options.forEach((option) => {
                    option.addEventListener('click', () => {
                        if (question.dataset.answered === '1') {
                            return;
                        }

                        question.dataset.answered = '1';
                        const isCorrect = option.dataset.correct === '1';

                        if (isCorrect) {
                            score++;
                            option.classList.add('border-emerald-500', 'bg-emerald-50', 'text-emerald-800');
                            feedback.textContent = 'Helyes válasz!';
                            feedback.classList.add('bg-emerald-100', 'text-emerald-800');
                        } else {
                            option.classList.add('border-red-500', 'bg-red-50', 'text-red-800');
                            const correctOption = options.find((item) => item.dataset.correct === '1');
                            correctOption?.classList.add('border-emerald-500', 'bg-emerald-50', 'text-emerald-800');
                            feedback.textContent = 'Helytelen válasz. A helyes megoldást zölddel jelöltük.';
                            feedback.classList.add('bg-red-100', 'text-red-800');
                        }

                        options.forEach((item) => item.disabled = true);
                        feedback.classList.remove('hidden');
                        nextButton.classList.remove('hidden');
                        scoreLabel.textContent = `${score} helyes`;
                    });
                });

                nextButton.addEventListener('click', () => {
                    question.classList.add('hidden');
                    current++;

                    if (current >= total) {
                        document.getElementById('preview-questions').classList.add('hidden');
                        result.classList.remove('hidden');
                        document.getElementById('preview-result-text').textContent =
                            `${score} / ${total} helyes válasz`;
                        progressLabel.textContent = `${total} / ${total}. kérdés`;
                        progressBar.style.width = '100%';
                        return;
                    }

                    questions[current].classList.remove('hidden');
                    progressLabel.textContent = `${current + 1} / ${total}. kérdés`;
                    progressBar.style.width = `${((current + 1) / total) * 100}%`;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        })();
    </script>
@endif

</body>
</html>
