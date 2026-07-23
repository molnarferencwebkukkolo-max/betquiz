<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Support\Facades\Schema;

class QuizGameService
{
    public function __construct(private PointService $pointService) {}

    /**
     * Új játék munkamenetének összeállítása
     */
    public function initGameSession(Quiz $quiz, string $mode, string $difficulty, int $requestedCount, int $betAmount): array
    {
        // 1. Tét levonása
        $this->pointService->deductBet(auth()->id(), $betAmount);

        // 2. Kérdések kiválasztása nehézség és darabszám alapján
        $questionsQuery = $quiz->questions();
        if (Schema::hasColumn('questions', 'difficulty')) {
            $questionsQuery->where('difficulty', $difficulty);
        }

        $questionIds = $questionsQuery->inRandomOrder()->take($requestedCount)->pluck('id')->toArray();

        // Fallback, ha nem volt elég kérdés az adott nehézségből
        if (count($questionIds) < $requestedCount) {
            $questionIds = $quiz->questions()->inRandomOrder()->take($requestedCount)->pluck('id')->toArray();
        }

        $multipliers = ['easy' => 1.3, 'medium' => 1.5, 'hard' => 2.0];
        $multiplier = $multipliers[$difficulty] ?? 1.5;

        return [
            'quiz_id' => $quiz->id,
            'game_mode' => $mode,
            'difficulty' => $difficulty,
            'question_ids' => $questionIds,
            'answered_question_ids' => [],
            'total_questions' => count($questionIds),
            'multiplier' => $multiplier,
            'bet_amount' => $betAmount,
            'bet_per_question' => round($betAmount / max(count($questionIds), 1)),
            'current_index' => 0,
            'correct_answers' => 0,
            'total_won' => 0,
            'current_pot' => $betAmount,
            'failed' => false,
        ];
    }

    /**
     * Válasz kiértékelése
     */
    public function processAnswer(array &$quizSession, int $optionId, int $userId): array
    {
        $currentIndex = $quizSession['current_index'];
        $questionId = $quizSession['question_ids'][$currentIndex];

        $question = Question::with(['options', 'quiz'])->findOrFail($questionId);
        $selectedOption = $question->options->firstWhere('id', $optionId);

        if (!$selectedOption) {
            throw new \InvalidArgumentException('Ez a válasz nem ehhez a kérdéshez tartozik.');
        }

        $isCorrect = (bool) $selectedOption->is_correct;
        $reward = 0;

        // Statisztikák frissítése
        $question->increment('times_answered');
        if ($isCorrect) {
            $question->increment('times_correct');
        }

        // Készítői pont jóváírása (ha még nem válaszolt erre a kérdésre)
        if (!in_array($question->id, $quizSession['answered_question_ids'])) {
            $this->pointService->rewardCreator($question->quiz, $userId);
            $quizSession['answered_question_ids'][] = $question->id;
        }

        // Pontszámítás mód szerint
        if ($quizSession['game_mode'] === 'bet') {
            if ($isCorrect) {
                $reward = round($quizSession['bet_per_question'] * $quizSession['multiplier']);
                $this->pointService->rewardPlayer($userId, $reward);
                $quizSession['correct_answers']++;
                $quizSession['total_won'] += $reward;
            }
        } else {
            // Odds mód
            if ($isCorrect) {
                $quizSession['correct_answers']++;
                $quizSession['current_pot'] = round($quizSession['current_pot'] * $quizSession['multiplier']);
                $reward = $quizSession['current_pot'];

                if ($currentIndex + 1 >= $quizSession['total_questions']) {
                    $this->pointService->rewardPlayer($userId, $quizSession['current_pot']);
                }
            } else {
                $quizSession['failed'] = true;
                $quizSession['current_pot'] = 0;
            }
        }

        $quizSession['current_index']++;

        $correctOption = $question->options->firstWhere('is_correct', true);

        return [
            'is_correct' => $isCorrect,
            'correct_option_id' => $correctOption?->id,
            'reward' => $reward,
            'current_index' => $quizSession['current_index'],
            'total_questions' => $quizSession['total_questions'],
            'is_last_question' => $quizSession['current_index'] >= $quizSession['total_questions'],
        ];
    }
}
