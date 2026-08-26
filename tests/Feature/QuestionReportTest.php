<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Option;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\QuestionReportedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuestionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_report_current_question_and_all_moderators_are_notified(): void
    {
        Notification::fake();
        [$player, $owner, $question, $quiz] = $this->gameData();
        $useradmin = User::factory()->create(['role' => 'useradmin']);
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);

        $response = $this->actingAs($player)
            ->withSession(['game_session' => $this->gameSession($quiz, $question)])
            ->post(route('quiz.questions.report', [$quiz, $question]), [
                'reason' => 'A megjelölt helyes válasz tényszerűen nem helyes.',
            ]);

        $response->assertRedirect(route('quiz.play.screen', $quiz));
        $this->assertFalse($question->fresh()->is_active);
        $this->assertDatabaseHas('question_reports', ['question_id' => $question->id, 'reporter_id' => $player->id, 'status' => 'pending']);
        Notification::assertSentTo([$owner, $useradmin, $hostadmin], QuestionReportedNotification::class);
    }

    public function test_report_enters_continuation_state_without_points_answers_or_statistics(): void
    {
        Notification::fake();
        [$player, $owner, $question, $quiz] = $this->gameData();
        $player->update(['points' => 500]);
        $question->update(['times_answered' => 7, 'times_correct' => 4]);
        $replacement = Question::create([
            'quiz_id' => $quiz->id,
            'category_id' => $quiz->category_id,
            'difficulty' => 'medium',
            'question_text' => ['hu' => 'Következő kérdés?'],
            'is_approved' => true,
            'is_active' => true,
        ]);
        Option::create(['question_id' => $replacement->id, 'option_text' => ['hu' => 'Igen'], 'is_correct' => true]);
        Option::create(['question_id' => $replacement->id, 'option_text' => ['hu' => 'Nem'], 'is_correct' => false]);

        $game = $this->gameSession($quiz, $question) + [
            'game_mode' => 'normal',
            'difficulty' => 'mixed',
            'time_limit' => 30,
            'time_modifier' => 1,
            'initial_bet' => 10,
            'target_count' => 10,
        ];

        $this->actingAs($player)
            ->withSession(['game_session' => $game])
            ->post(route('quiz.questions.report', [$quiz, $question]), [
                'reason' => 'A kérdés megfogalmazása tényszerű hibát tartalmaz.',
            ])
            ->assertRedirect(route('quiz.play.screen', $quiz));

        $this->assertSame(500, $player->fresh()->points);
        $this->assertDatabaseCount('user_answers', 0);
        $this->assertSame(7, $question->fresh()->times_answered);
        $this->assertSame(4, $question->fresh()->times_correct);
        $this->assertSame([], session('game_session.answered_ids'));
        $this->assertTrue(session('game_session.awaiting_decision'));
        $this->assertSame('question_reported', session('game_session.decision_type'));
        $this->assertNull(session('game_session.current_question_id'));

        $this->get(route('quiz.play.screen', $quiz))
            ->assertOk()
            ->assertSee('KÖSZÖNJÜK A JELZÉST!')
            ->assertSee('nem számítottuk helyes vagy hibás válasznak')
            ->assertSee('Következő kérdés')
            ->assertDontSee('HELYES VÁLASZ!');
    }

    public function test_owner_can_mark_report_as_fake_and_reactivate_question(): void
    {
        [$player, $owner, $question] = $this->gameData();
        $question->update(['is_active' => false]);
        $report = QuestionReport::create(['question_id' => $question->id, 'reporter_id' => $player->id, 'reason' => 'Legalább tizenöt karakteres indoklás.']);

        $this->actingAs($owner)->patch(route('question-reports.resolve', $report), [
            'decision' => 'rejected',
            'resolution_note' => 'A források alapján a kérdés és a válasz helyes.',
        ])->assertSessionHas('success');

        $this->assertSame('rejected', $report->fresh()->status);
        $this->assertTrue($question->fresh()->is_active);
    }

    public function test_more_than_thirty_percent_fake_rate_restricts_reporting_after_three_decisions(): void
    {
        $player = User::factory()->create();
        [$unused, $owner, $question] = $this->gameData();

        foreach (['rejected', 'accepted', 'accepted'] as $status) {
            QuestionReport::create([
                'question_id' => $question->id,
                'reporter_id' => $player->id,
                'reason' => 'Korábbi részletes hibajelzés indoklása.',
                'status' => $status,
                'resolved_by' => $owner->id,
                'resolved_at' => now(),
            ]);
        }

        $stats = $player->questionReportStats();
        $this->assertEqualsWithDelta(33.33, $stats['fake_rate'], 0.01);
        $this->assertTrue($stats['restricted']);
    }

    private function gameData(): array
    {
        $player = User::factory()->create();
        $owner = User::factory()->create();
        $category = Category::create(['name' => 'Teszt', 'slug' => 'teszt-'.uniqid(), 'is_active' => true]);
        $quiz = Quiz::create(['creator_id' => $owner->id, 'category_id' => $category->id, 'title' => 'Jelenthető kvíz', 'slug' => 'jelentheto-'.uniqid(), 'description' => 'Teszt', 'status' => 'approved', 'is_public' => true]);
        $question = Question::create(['quiz_id' => $quiz->id, 'category_id' => $category->id, 'difficulty' => 'medium', 'question_text' => ['hu' => 'Tesztkérdés?'], 'is_approved' => true, 'is_active' => true]);

        return [$player, $owner, $question, $quiz];
    }

    private function gameSession(Quiz $quiz, Question $question): array
    {
        return ['quiz_id' => $quiz->id, 'status' => 'active', 'current_question_id' => $question->id, 'answered_ids' => [], 'helper_results' => []];
    }
}
