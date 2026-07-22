<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetQuiz - Kérdés</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

@include('layouts.navigation')

<div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl p-8 border border-gray-100">

    <!-- Fejléc info -->
    <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
    <span class="text-sm font-bold text-gray-500 uppercase tracking-wider">
        Kérdés: <span class="text-indigo-600 text-lg">{{ $quiz['current_index'] + 1 }}</span> / {{ $quiz['total_questions'] }}
    </span>

        @if($quiz['game_mode'] === 'odds')
            <span class="bg-amber-100 text-amber-800 font-bold px-3 py-1 rounded-lg text-sm border border-amber-200">
            🔥 Halmozott esély: {{ number_format($quiz['current_pot'], 0, ',', ' ') }} PT ({{ $quiz['multiplier'] }}x)
        </span>
        @else
            <span class="bg-amber-100 text-amber-800 font-bold px-3 py-1 rounded-lg text-sm border border-amber-200">
            💰 Tét: {{ number_format($quiz['bet_per_question'], 0, ',', ' ') }} PT ({{ $quiz['multiplier'] }}x)
        </span>
        @endif
    </div>

    <!-- Kérdés Szövege / Képe -->
    <div class="text-center mb-8">
        @if($question->image_path)
            <img src="{{ asset('storage/' . $question->image_path) }}" alt="Kérdés képe" class="max-h-64 mx-auto rounded-2xl shadow-md mb-4 border">
        @endif

        @php
            $qText = $question->question_text;
            if (is_array($qText)) {
                $qText = $qText['hu'] ?? reset($qText);
            }
        @endphp

        @if($qText)
            <h2 class="text-2xl font-bold text-gray-800">
                {{ $qText }}
            </h2>
        @endif
    </div>
    <!--  <pre class="text-xs bg-gray-200 p-2 rounded mb-4">{{ print_r($question->toArray(), true) }}</pre>-->

    <!-- Válaszlehetőségek form -->
    <form action="{{ route('quiz.answer') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="question_id" value="{{ $question->id }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($question->options as $option)
                @php
                    $optText = $option->option_text ?? $option->text ?? $option->name;
                    if (is_array($optText)) {
                        $optText = $optText['hu'] ?? reset($optText);
                    }
                @endphp

                <button type="submit" name="answer" value="{{ $optText ?? $option->id }}"
                        class="w-full p-4 text-left font-semibold text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 border-2 border-gray-200 rounded-xl transition duration-150 shadow-sm active:scale-95 flex flex-col items-center justify-center gap-2">

                    @if($option->image_path)
                        <img src="{{ asset('storage/' . $option->image_path) }}" alt="Opció képe" class="max-h-32 rounded-lg border">
                    @endif

                    @if($optText)
                        <span>{{ $optText }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </form>



</div>

</body>
</html>
