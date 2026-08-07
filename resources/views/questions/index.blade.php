<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kérdésbank - KwizzGo</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body style="padding-bottom: 3rem;">

@include('layouts.navigation')

<div class="nav-container" style="padding-top: 2rem; padding-bottom: 2rem;">

    <!-- Fejléc -->
    <div style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap;">
        <div>
            <h1 class="q-title">❓ Kérdésbank</h1>
            <p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                {{ $user->isUseradmin() ? 'Az adatbázisban szereplő összes kérdés és azok kvízei.' : 'A saját kvízeidhez feltöltött kérdések.' }}
            </p>
        </div>

        <div>
            <a href="{{ route('questions.create') }}" class="btn-primary-purple" style="font-size: 0.875rem; text-decoration: none;">
                ➕ Új Kérdés Hozzáadása
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            {{ session('success') }}
        </div>
    @endif

    <!-- Kérdések Táblázat -->
    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="table-custom">
                <thead>
                <tr>
                    <th>Kérdés Szövege</th>
                    <th>Tartozó Kvíz</th>
                    <th>Nehézség</th>
                    <th>Helyes Válasz</th>
                    <th style="text-align: right;">Műveletek</th>
                </tr>
                </thead>
                <tbody>
                @forelse($questions as $question)
                    @php
                        $qText = is_array($question->question_text)
                            ? ($question->question_text['hu'] ?? reset($question->question_text))
                            : $question->question_text;

                        $correctOpt = $question->options->firstWhere('is_correct', true);
                        $cText = $correctOpt ? (is_array($correctOpt->option_text) ? ($correctOpt->option_text['hu'] ?? reset($correctOpt->option_text)) : $correctOpt->option_text) : '-';
                    @endphp

                    <tr>
                        <!-- Kérdés -->
                        <td style="font-weight: 600; color: #1f2937; max-width: 24rem;">
                            {{ $qText }}
                        </td>

                        <!-- Tartozó Kvíz Badge -->
                        <td>
                            @if($question->quiz)
                                <span class="badge-quiz-title">
                                    🎯 {{ $question->quiz->title }}
                                </span>
                            @else
                                <span class="badge-no-quiz">
                                    Nincs Kvízhez kötve
                                </span>
                            @endif
                        </td>

                        <!-- Nehézség -->
                        <td>
                            @if($question->difficulty === 'easy')
                                <span class="badge-diff-easy">Könnyű</span>
                            @elseif($question->difficulty === 'hard')
                                <span class="badge-diff-hard">Nehéz</span>
                            @else
                                <span class="badge-diff-medium">Közepes</span>
                            @endif
                        </td>

                        <!-- Helyes Válasz -->
                        <td class="text-correct-answer">
                            ✓ {{ $cText }}
                        </td>

                        <!-- Műveletek -->
                        <td style="text-align: right;">
                            <a href="{{ route('questions.edit', $question->id) }}" style="color: #4f46e5; font-weight: 700; font-size: 0.75rem; text-decoration: none;">
                                ✏️ Szerkesztés
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center; color: #9ca3af; font-weight: 600;">
                            Még nincsenek kérdések az adatbázisban!
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <!-- Lapozó -->
        <div style="padding: 1rem; border-top: 1px solid #f3f4f6;">
            {{ $questions->links() }}
        </div>
    </div>

</div>

</body>
</html>
