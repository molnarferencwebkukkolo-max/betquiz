<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuizManagementIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_can_search_and_only_sees_own_quizzes(): void
    {
        $category = $this->makeCategory();
        $user = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        $ownQuiz = $this->makeQuiz($user, $category, 'Saját történelmi kvíz');
        $this->makeQuiz($otherUser, $category, 'Más történelmi kvíze');

        $response = $this->actingAs($user)->get(route('my-quizzes.index', [
            'q' => 'történelmi',
        ]));

        $response->assertOk()
            ->assertSee($ownQuiz->title)
            ->assertDontSee('Más történelmi kvíze');
    }

    public function test_admin_can_filter_quizzes_and_switch_to_table_view(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->create(['role' => 'useradmin']);
        $creator = User::factory()->create(['role' => 'user']);

        $matchingQuiz = $this->makeQuiz($creator, $category, 'Jóváhagyott teszt', 'approved');
        $this->makeQuiz($creator, $category, 'Függő teszt', 'pending');

        $response = $this->actingAs($admin)->get(route('my-quizzes.index', [
            'view' => 'table',
            'status' => 'approved',
        ]));

        $response->assertOk()
            ->assertSee('Táblázat')
            ->assertSee($matchingQuiz->title)
            ->assertSee('Próbajáték')
            ->assertDontSee('Függő teszt');

        $this->assertSame('table', session('quiz_management_view'));
    }

    public function test_regular_user_cannot_open_admin_preview(): void
    {
        $category = $this->makeCategory();
        $user = User::factory()->create(['role' => 'user']);
        $quiz = $this->makeQuiz($user, $category, 'Saját kvíz');

        $this->actingAs($user)
            ->get(route('my-quizzes.preview', $quiz))
            ->assertForbidden();
    }

    public function test_admin_preview_does_not_change_points_or_question_statistics(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->create([
            'role' => 'useradmin',
            'points' => 1234,
        ]);
        $quiz = $this->makeQuiz($admin, $category, 'Próbajáték kvíz', 'approved');
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'question_text' => ['hu' => 'Tesztkérdés?'],
            'is_approved' => true,
            'is_active' => true,
            'times_answered' => 7,
            'times_correct' => 4,
        ]);
        Option::create([
            'question_id' => $question->id,
            'option_text' => ['hu' => 'Helyes'],
            'is_correct' => true,
        ]);
        Option::create([
            'question_id' => $question->id,
            'option_text' => ['hu' => 'Helytelen'],
            'is_correct' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('my-quizzes.preview', $quiz))
            ->assertOk()
            ->assertSee('0 PT')
            ->assertSee('Tesztkérdés?');

        $this->assertSame(1234, $admin->fresh()->points);
        $this->assertSame(7, $question->fresh()->times_answered);
        $this->assertSame(4, $question->fresh()->times_correct);
    }

    public function test_admin_can_bulk_change_quiz_owner(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->create(['role' => 'useradmin']);
        $oldOwner = User::factory()->create(['role' => 'user']);
        $newOwner = User::factory()->create(['role' => 'user']);
        $firstQuiz = $this->makeQuiz($oldOwner, $category, 'Első bulk kvíz');
        $secondQuiz = $this->makeQuiz($oldOwner, $category, 'Második bulk kvíz');

        $this->actingAs($admin)->patch(route('admin.quizzes.bulk-update'), [
            'quiz_ids' => [$firstQuiz->id, $secondQuiz->id],
            'bulk_action' => 'change_owner',
            'owner_id' => $newOwner->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($newOwner->id, $firstQuiz->fresh()->creator_id);
        $this->assertSame($newOwner->id, $secondQuiz->fresh()->creator_id);
    }

    public function test_only_complete_approved_quiz_can_be_made_public_in_bulk(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->create(['role' => 'hostadmin']);
        $quiz = $this->makeQuiz($admin, $category, 'Publikálható kvíz', 'approved');

        $questions = [];
        for ($index = 1; $index <= 100; $index++) {
            $questions[] = [
                'quiz_id' => $quiz->id,
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'question_text' => json_encode(['hu' => "Kérdés {$index}"]),
                'is_approved' => true,
                'is_active' => true,
                'times_answered' => 0,
                'times_correct' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('questions')->insert($questions);

        $this->actingAs($admin)->patch(route('admin.quizzes.bulk-update'), [
            'quiz_ids' => [$quiz->id],
            'bulk_action' => 'make_public',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($quiz->fresh()->is_public);
    }

    public function test_regular_user_cannot_use_quiz_bulk_actions(): void
    {
        $category = $this->makeCategory();
        $user = User::factory()->create(['role' => 'user']);
        $quiz = $this->makeQuiz($user, $category, 'Tiltott bulk kvíz');

        $this->actingAs($user)->patch(route('admin.quizzes.bulk-update'), [
            'quiz_ids' => [$quiz->id],
            'bulk_action' => 'approve',
        ])->assertForbidden();
    }

    public function test_rejection_requires_and_stores_the_edited_moderation_reason(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->create(['role' => 'useradmin']);
        $creator = User::factory()->create(['role' => 'user']);
        $quiz = $this->makeQuiz($creator, $category, 'Elutasítandó kvíz', 'pending');

        $this->actingAs($admin)->post(route('admin.quizzes.reject', $quiz), [
            'moderation_reason' => '   ',
        ])->assertSessionHasErrors('moderation_reason');

        $this->assertSame('pending', $quiz->fresh()->status);

        $this->actingAs($admin)->post(route('admin.quizzes.reject', $quiz), [
            'moderation_reason' => '  A leírás még nem elég részletes.  ',
        ])->assertSessionHasNoErrors();

        $quiz->refresh();
        $this->assertSame('rejected', $quiz->status);
        $this->assertFalse($quiz->is_public);
        $this->assertSame('A leírás még nem elég részletes.', $quiz->rejection_reason);
    }

    public function test_withdrawing_publication_requires_a_reason_visible_to_the_owner(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->create(['role' => 'hostadmin']);
        $creator = User::factory()->create(['role' => 'user']);
        $quiz = $this->makeQuiz($creator, $category, 'Visszavont kvíz', 'approved');
        $quiz->update(['is_public' => true]);

        $this->actingAs($admin)->patch(route('admin.quizzes.bulk-update'), [
            'quiz_ids' => [$quiz->id],
            'bulk_action' => 'make_private',
        ])->assertSessionHasErrors('moderation_reason');

        $this->assertTrue($quiz->fresh()->is_public);

        $this->actingAs($admin)->patch(route('admin.quizzes.bulk-update'), [
            'quiz_ids' => [$quiz->id],
            'bulk_action' => 'make_private',
            'moderation_reason' => 'További tartalmi ellenőrzés szükséges.',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($quiz->fresh()->is_public);

        $this->actingAs($creator)
            ->get(route('my-quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('További tartalmi ellenőrzés szükséges.');
    }

    public function test_approval_clears_an_earlier_moderation_reason(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->create(['role' => 'useradmin']);
        $quiz = $this->makeQuiz($admin, $category, 'Újra jóváhagyott kvíz', 'rejected');
        $quiz->update(['rejection_reason' => 'Korábbi indok']);

        $this->actingAs($admin)
            ->post(route('admin.quizzes.approve', $quiz))
            ->assertSessionHasNoErrors();

        $quiz->refresh();
        $this->assertSame('approved', $quiz->status);
        $this->assertNull($quiz->rejection_reason);
    }

    public function test_admin_quiz_search_returns_limited_matching_results(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->create(['role' => 'useradmin']);
        $quiz = $this->makeQuiz($admin, $category, 'Kereshető célkvíz');

        $this->actingAs($admin)
            ->getJson(route('admin.quizzes.search', ['q' => 'Kereshető']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $quiz->id,
                'title' => $quiz->title,
            ]);
    }

    public function test_private_quiz_cannot_be_opened_directly_by_unrelated_user(): void
    {
        $category = $this->makeCategory();
        $owner = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);
        $quiz = $this->makeQuiz($owner, $category, 'Privát kvíz', 'approved');
        $quiz->update(['is_public' => false]);

        $this->actingAs($otherUser)
            ->get(route('quiz.setup', $quiz))
            ->assertForbidden();
    }

    private function makeCategory(): Category
    {
        return Category::create([
            'name' => ['hu' => 'Általános'],
            'slug' => 'altalanos-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function makeQuiz(
        User $creator,
        Category $category,
        string $title,
        string $status = 'approved'
    ): Quiz {
        return Quiz::create([
            'creator_id' => $creator->id,
            'category_id' => $category->id,
            'title' => $title,
            'description' => 'Tesztleírás',
            'status' => $status,
        ]);
    }
}
