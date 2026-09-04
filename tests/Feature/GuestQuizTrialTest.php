<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestQuizTrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_complete_ten_question_trial_without_points_or_statistics(): void
    {
        $category = Category::create(['name' => ['hu' => 'Próba'], 'slug' => 'proba', 'is_active' => true]);
        $owner = User::factory()->create(['points' => 4321]);
        $quiz = Quiz::create(['creator_id' => $owner->id, 'category_id' => $category->id, 'title' => 'Vendégkvíz', 'status' => 'approved', 'is_public' => true]);

        for ($index = 1; $index <= 12; $index++) {
            $question = Question::create(['quiz_id' => $quiz->id, 'category_id' => $category->id, 'difficulty' => 'medium', 'question_text' => ['hu' => "Kérdés {$index}"], 'is_approved' => true, 'is_active' => true, 'times_answered' => 7, 'times_correct' => 3]);
            Option::create(['question_id' => $question->id, 'option_text' => ['hu' => 'Helyes'], 'is_correct' => true]);
            Option::create(['question_id' => $question->id, 'option_text' => ['hu' => 'Helytelen'], 'is_correct' => false]);
        }

        $this->post(route('quizzes.trial.start', $quiz))->assertRedirect(route('quizzes.trial.show', $quiz));
        $this->assertCount(10, session('guest_quiz_trial.questions'));

        for ($step = 0; $step < 10; $step++) {
            $trial = session('guest_quiz_trial');
            $question = Question::with('options')->findOrFail($trial['questions'][$step]['id']);
            $response = $this->post(route('quizzes.trial.answer', $quiz), ['option_id' => $question->options->firstWhere('is_correct', true)->id]);
            $response->assertRedirect($step === 9 ? route('quizzes.trial.result', $quiz) : route('quizzes.trial.show', $quiz));
        }

        $this->get(route('quizzes.trial.result', $quiz))->assertOk()->assertSee('750 zsetont nyertél volna');
        $this->assertSame(4321, $owner->fresh()->points);
        $this->assertSame(84, (int) $quiz->questions()->sum('times_answered'));
        $this->assertDatabaseCount('user_answers', 0);
        $this->assertGuest();
    }

    public function test_private_quiz_cannot_be_tried(): void
    {
        $category = Category::create(['name' => ['hu' => 'Rejtett'], 'slug' => 'rejtett', 'is_active' => true]);
        $quiz = Quiz::create(['creator_id' => User::factory()->create()->id, 'category_id' => $category->id, 'title' => 'Rejtett', 'status' => 'approved', 'is_public' => false]);
        $this->post(route('quizzes.trial.start', $quiz))->assertNotFound();
    }
}
