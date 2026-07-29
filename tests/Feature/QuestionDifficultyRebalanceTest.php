<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionDifficultyRebalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_rebalance_before_one_hundred_answers(): void
    {
        $question = $this->makeQuestion([
            'difficulty' => 'medium',
            'times_answered' => 99,
            'times_correct' => 90,
        ]);

        $this->assertFalse($question->rebalanceDifficultyIfNeeded());
        $this->assertSame('medium', $question->fresh()->difficulty);
    }

    public function test_it_moves_question_one_level_easier_above_eighty_percent(): void
    {
        $question = $this->makeQuestion([
            'difficulty' => 'medium',
            'times_answered' => 100,
            'times_correct' => 81,
        ]);

        $this->assertTrue($question->rebalanceDifficultyIfNeeded());

        $question->refresh();
        $this->assertSame('easy', $question->difficulty);
        $this->assertSame(0, $question->times_answered);
        $this->assertSame(0, $question->times_correct);
    }

    public function test_it_moves_question_one_level_harder_below_twenty_percent(): void
    {
        $question = $this->makeQuestion([
            'difficulty' => 'medium',
            'times_answered' => 100,
            'times_correct' => 19,
        ]);

        $this->assertTrue($question->rebalanceDifficultyIfNeeded());

        $question->refresh();
        $this->assertSame('hard', $question->difficulty);
        $this->assertSame(0, $question->times_answered);
        $this->assertSame(0, $question->times_correct);
    }

    public function test_it_keeps_boundary_values_on_current_difficulty(): void
    {
        $question = $this->makeQuestion([
            'difficulty' => 'medium',
            'times_answered' => 100,
            'times_correct' => 80,
        ]);

        $this->assertFalse($question->rebalanceDifficultyIfNeeded());
        $this->assertSame('medium', $question->fresh()->difficulty);

        $question->update([
            'times_answered' => 100,
            'times_correct' => 20,
        ]);

        $this->assertFalse($question->rebalanceDifficultyIfNeeded());
        $this->assertSame('medium', $question->fresh()->difficulty);
    }

    public function test_it_does_not_reset_stats_when_question_cannot_move_further(): void
    {
        $easyQuestion = $this->makeQuestion([
            'difficulty' => 'easy',
            'times_answered' => 100,
            'times_correct' => 90,
        ]);

        $hardQuestion = $this->makeQuestion([
            'difficulty' => 'hard',
            'times_answered' => 100,
            'times_correct' => 10,
        ]);

        $this->assertFalse($easyQuestion->rebalanceDifficultyIfNeeded());
        $this->assertFalse($hardQuestion->rebalanceDifficultyIfNeeded());
        $this->assertSame(100, $easyQuestion->fresh()->times_answered);
        $this->assertSame(100, $hardQuestion->fresh()->times_answered);
    }

    private function makeQuestion(array $attributes = []): Question
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => ['hu' => 'Altalanos'],
            'slug' => 'altalanos-' . uniqid(),
        ]);
        $quiz = Quiz::create([
            'creator_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Teszt kviz ' . uniqid(),
            'description' => 'Teszt leiras',
            'status' => 'approved',
        ]);

        return Question::create(array_merge([
            'quiz_id' => $quiz->id,
            'category_id' => $category->id,
            'creator_id' => $user->id,
            'difficulty' => 'medium',
            'question_text' => ['hu' => 'Teszt kerdes?'],
            'is_approved' => true,
            'is_active' => true,
            'times_answered' => 0,
            'times_correct' => 0,
        ], $attributes));
    }
}
