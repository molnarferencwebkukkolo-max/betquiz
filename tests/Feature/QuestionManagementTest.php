<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_question_owned_through_its_quiz(): void
    {
        [$question, $category] = $this->makeQuestion();
        $admin = User::factory()->create(['role' => 'useradmin']);

        $this->actingAs($admin)
            ->get(route('questions.edit', $question))
            ->assertOk()
            ->assertSee('Kérdés Szerkesztése')
            ->assertSee($question->quiz->title)
            ->assertDontSee('Kategória:');
    }

    public function test_quiz_owner_can_open_and_update_question(): void
    {
        [$question, $category, $owner] = $this->makeQuestion();

        $response = $this->actingAs($owner)->put(route('questions.update', $question), [
            'quiz_id' => $question->quiz_id,
            'difficulty' => 'hard',
            'question_text' => 'Frissített kérdés?',
            'correct_option' => 1,
            'options' => [
                ['text' => 'Első válasz'],
                ['text' => 'Második válasz'],
            ],
        ]);

        $response->assertRedirect(route('my-quizzes.show', $question->quiz));

        $question->refresh();
        $this->assertSame('hard', $question->difficulty);
        $this->assertSame('Frissített kérdés?', $question->question_text['hu']);
        $this->assertFalse($question->options()->orderBy('id')->first()->is_correct);
        $this->assertTrue($question->options()->orderBy('id')->skip(1)->first()->is_correct);
    }

    public function test_unrelated_user_cannot_edit_or_update_question(): void
    {
        [$question, $category] = $this->makeQuestion();
        $unrelatedUser = User::factory()->create(['role' => 'user']);

        $this->actingAs($unrelatedUser)
            ->get(route('questions.edit', $question))
            ->assertForbidden();

        $this->actingAs($unrelatedUser)
            ->put(route('questions.update', $question), [
                'quiz_id' => $question->quiz_id,
                'difficulty' => 'easy',
                'question_text' => 'Jogosulatlan módosítás',
                'correct_option' => 0,
                'options' => [
                    ['text' => 'Első'],
                    ['text' => 'Második'],
                ],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_move_question_to_another_quiz(): void
    {
        [$question, $category] = $this->makeQuestion();
        $admin = User::factory()->create(['role' => 'hostadmin']);
        $targetCategory = Category::create([
            'name' => ['hu' => 'Másik kategória'],
            'slug' => 'masik-'.uniqid(),
            'is_active' => true,
        ]);
        $targetQuiz = Quiz::create([
            'creator_id' => $admin->id,
            'category_id' => $targetCategory->id,
            'title' => 'Célkvíz '.uniqid(),
            'description' => 'Teszt',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)->put(route('questions.update', $question), [
            'quiz_id' => $targetQuiz->id,
            'difficulty' => 'medium',
            'question_text' => 'Áthelyezett kérdés?',
            'correct_option' => 0,
            'options' => [
                ['text' => 'Első'],
                ['text' => 'Második'],
            ],
        ])->assertRedirect(route('my-quizzes.show', $targetQuiz));

        $question->refresh();
        $this->assertSame($targetQuiz->id, $question->quiz_id);
        $this->assertSame($targetCategory->id, $question->category_id);
    }

    public function test_question_and_option_images_are_stored_and_persisted(): void
    {
        Storage::fake('public');
        [$question, $category, $owner] = $this->makeQuestion();

        $this->actingAs($owner)->put(route('questions.update', $question), [
            'quiz_id' => $question->quiz_id,
            'difficulty' => 'medium',
            'question_text' => 'Képes kérdés?',
            'question_image' => UploadedFile::fake()->image('question.jpg', 800, 600),
            'correct_option' => 0,
            'options' => [
                [
                    'text' => 'Képes válasz',
                    'image' => UploadedFile::fake()->image('answer.png', 400, 300),
                ],
                ['text' => 'Második válasz'],
            ],
        ])->assertRedirect();

        $question->refresh();
        $firstOption = $question->options()->orderBy('id')->first();

        $this->assertNotNull($question->image_path);
        $this->assertNotNull($firstOption->image_path);
        Storage::disk('public')->assertExists($question->image_path);
        Storage::disk('public')->assertExists($firstOption->image_path);
    }

    public function test_question_ownership_follows_quiz_ownership_transfer(): void
    {
        [$question, $category, $originalOwner] = $this->makeQuestion();
        $newOwner = User::factory()->create(['role' => 'user']);

        $question->quiz->update(['creator_id' => $newOwner->id]);

        $this->actingAs($originalOwner)
            ->get(route('questions.edit', $question))
            ->assertForbidden();

        $this->actingAs($newOwner)
            ->get(route('questions.edit', $question))
            ->assertOk();
    }

    public function test_quiz_owner_can_bulk_change_question_difficulty(): void
    {
        [$question, $category, $owner] = $this->makeQuestion();
        $secondQuestion = $question->replicate();
        $secondQuestion->question_text = ['hu' => 'Második kérdés?'];
        $secondQuestion->save();

        $this->actingAs($owner)->patch(route('my-quizzes.questions.bulk-update', $question->quiz), [
            'question_ids' => [$question->id, $secondQuestion->id],
            'bulk_action' => 'change_difficulty',
            'difficulty' => 'hard',
        ])->assertSessionHasNoErrors();

        $this->assertSame('hard', $question->fresh()->difficulty);
        $this->assertSame('hard', $secondQuestion->fresh()->difficulty);
    }

    public function test_admin_can_bulk_move_questions_to_another_quiz(): void
    {
        [$question, $category] = $this->makeQuestion();
        $admin = User::factory()->create(['role' => 'useradmin']);
        $targetCategory = Category::create([
            'name' => ['hu' => 'Bulk célkategória'],
            'slug' => 'bulk-cel-'.uniqid(),
            'is_active' => true,
        ]);
        $targetQuiz = Quiz::create([
            'creator_id' => $admin->id,
            'category_id' => $targetCategory->id,
            'title' => 'Bulk célkvíz '.uniqid(),
            'description' => 'Teszt',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)->patch(route('my-quizzes.questions.bulk-update', $question->quiz), [
            'question_ids' => [$question->id],
            'bulk_action' => 'move_to_quiz',
            'target_quiz_id' => $targetQuiz->id,
        ])->assertSessionHasNoErrors();

        $question->refresh();
        $this->assertSame($targetQuiz->id, $question->quiz_id);
        $this->assertSame($targetCategory->id, $question->category_id);
    }

    /**
     * @return array{Question, Category, User}
     */
    private function makeQuestion(): array
    {
        $owner = User::factory()->create(['role' => 'user']);
        $category = Category::create([
            'name' => ['hu' => 'Általános'],
            'slug' => 'altalanos-'.uniqid(),
            'is_active' => true,
        ]);
        $quiz = Quiz::create([
            'creator_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Szerkeszthető kvíz '.uniqid(),
            'description' => 'Teszt',
            'status' => 'approved',
        ]);
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'question_text' => ['hu' => 'Eredeti kérdés?'],
            'is_approved' => false,
            'is_active' => true,
            'times_answered' => 0,
            'times_correct' => 0,
        ]);
        Option::create([
            'question_id' => $question->id,
            'option_text' => ['hu' => 'Első'],
            'is_correct' => true,
        ]);
        Option::create([
            'question_id' => $question->id,
            'option_text' => ['hu' => 'Második'],
            'is_correct' => false,
        ]);

        return [$question, $category, $owner];
    }
}
