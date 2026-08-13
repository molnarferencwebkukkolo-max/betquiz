<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuizManagementIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_quiz_creation_form(): void
    {
        $this->makeCategory();
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('my-quizzes.create'))
            ->assertOk()
            ->assertSee('action="'.route('my-quizzes.store').'"', false);
    }

    public function test_regular_user_pays_the_quiz_creation_cost(): void
    {
        $category = $this->makeCategory();
        $user = User::factory()->create(['role' => 'user', 'points' => 60000]);

        $this->actingAs($user)
            ->post(route('my-quizzes.store'), $this->quizCreationData($category))
            ->assertSessionHasNoErrors();

        $this->assertSame(10000, $user->fresh()->points);
        $this->assertDatabaseHas('quizzes', ['creator_id' => $user->id]);
    }

    public function test_useradmin_and_hostadmin_create_quizzes_for_free(): void
    {
        $category = $this->makeCategory();

        foreach (['useradmin', 'hostadmin'] as $role) {
            $admin = User::factory()->create(['role' => $role, 'points' => 1234]);

            $this->actingAs($admin)
                ->post(route('my-quizzes.store'), $this->quizCreationData($category, $role))
                ->assertSessionHasNoErrors();

            $this->assertSame(1234, $admin->fresh()->points);
            $this->assertDatabaseHas('quizzes', [
                'creator_id' => $admin->id,
                'status' => 'approved',
            ]);
        }
    }

    public function test_admin_creation_form_skips_the_review_sample_questions(): void
    {
        $this->makeCategory();
        $admin = User::factory()->create(['role' => 'hostadmin']);

        $this->actingAs($admin)
            ->get(route('my-quizzes.create'))
            ->assertOk()
            ->assertSee('tetszőleges számú kérdést')
            ->assertDontSee('Minta Kérdések Feltöltése')
            ->assertDontSee('benyújtod a kvízt bírálatra');
    }

    public function test_quiz_edit_form_keeps_its_current_inactive_category_available(): void
    {
        $category = $this->makeCategory();
        $user = User::factory()->create(['role' => 'user']);
        $quiz = $this->makeQuiz($user, $category, 'Inaktív kategóriás kvíz');
        $category->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('my-quizzes.edit', $quiz))
            ->assertOk()
            ->assertSee('value="'.$category->id.'" selected', false);
    }

    public function test_admin_can_import_questions_from_csv_into_an_approved_quiz(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->create(['role' => 'hostadmin']);
        $quiz = $this->makeQuiz($admin, $category, 'CSV import kvíz', 'approved');
        $csv = "question,option_1,option_2,option_3,option_4,correct_index\n"
            ."Mennyi kettő meg kettő?,Négy,Három,Öt,Hat,1\n";
        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $this->actingAs($admin)
            ->post(route('my-quizzes.questions.import', $quiz), ['csv_file' => $file])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $question = Question::query()->where('quiz_id', $quiz->id)->firstOrFail();
        $this->assertSame('Mennyi kettő meg kettő?', $question->question_text['hu']);
        $this->assertCount(4, $question->options);
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());
    }

    public function test_admin_can_import_the_semicolon_csv_format_shown_on_the_page(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->create(['role' => 'useradmin']);
        $quiz = $this->makeQuiz($admin, $category, 'Excel CSV import', 'approved');
        $csv = "Kérdés;Helyes válasz;Hibás1;Hibás2;Hibás3;Nehézség\n"
            ."Magyarország fővárosa?;Budapest;Bécs;Prága;Pozsony;hard\n";

        $this->actingAs($admin)
            ->post(route('my-quizzes.questions.import', $quiz), [
                'csv_file' => UploadedFile::fake()->createWithContent('excel.csv', $csv),
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Sikeresen importálva 1 db kérdés!');

        $question = Question::query()->where('quiz_id', $quiz->id)->firstOrFail();
        $this->assertSame('hard', $question->difficulty);
        $this->assertSame('Budapest', $question->options()->where('is_correct', true)->firstOrFail()->translated_text);
    }

    public function test_regular_user_cannot_create_quiz_without_enough_points(): void
    {
        $category = $this->makeCategory();
        $user = User::factory()->create(['role' => 'user', 'points' => 49999]);

        $this->actingAs($user)
            ->post(route('my-quizzes.store'), $this->quizCreationData($category))
            ->assertSessionHasErrors('error');

        $this->assertSame(49999, $user->fresh()->points);
        $this->assertDatabaseMissing('quizzes', ['creator_id' => $user->id]);
    }

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

    private function quizCreationData(Category $category, string $suffix = 'user'): array
    {
        return [
            'title' => 'Létrehozási teszt '.$suffix,
            'description' => 'Tesztleírás',
            'category_id' => $category->id,
        ];
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
