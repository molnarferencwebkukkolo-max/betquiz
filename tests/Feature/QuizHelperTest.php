<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuizHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_three_uses_are_free_and_fourth_costs_one_hundred_points(): void
    {
        [$user, $quiz, $question] = $this->game();
        foreach (range(1, 4) as $use) {
            $this->withSession(['game_session' => $this->gameSession($quiz, $question)])
                ->actingAs($user)->post(route('quiz.helpers.use', [$quiz, 'audience']))->assertRedirect();
        }
        $this->assertSame(400, $user->fresh()->points);
        $this->assertSame(4, DB::table('user_quiz_helper_usages')->where('user_id', $user->id)->where('helper', 'audience')->count());
    }

    public function test_fifty_fifty_removes_exactly_two_wrong_options(): void
    {
        [$user, $quiz, $question] = $this->game();
        $response = $this->withSession(['game_session' => $this->gameSession($quiz, $question)])
            ->actingAs($user)->post(route('quiz.helpers.use', [$quiz, 'fifty_fifty']));
        $response->assertRedirect();
        $removed = session('game_session.helper_results.fifty_fifty');
        $this->assertCount(2, $removed);
        $this->assertSame(2, $question->options()->whereIn('id', $removed)->where('is_correct', false)->count());
    }

    public function test_bear_reveals_the_correct_option(): void
    {
        [$user, $quiz, $question] = $this->game();
        $this->withSession(['game_session' => $this->gameSession($quiz, $question)])
            ->actingAs($user)->post(route('quiz.helpers.use', [$quiz, 'bear']));
        $this->assertSame($question->options()->where('is_correct', true)->value('id'), session('game_session.helper_results.bear'));
    }

    public function test_blackjack_starts_with_two_cards_each_and_pauses_in_overlay(): void
    {
        [$user, $quiz, $question] = $this->game();
        $this->withSession(['game_session' => $this->gameSession($quiz, $question)])
            ->actingAs($user)->post(route('quiz.helpers.blackjack.start', $quiz))->assertRedirect();
        $this->assertSame('blackjack', session('game_session.helper_overlay'));
        $this->assertCount(2, session('game_session.blackjack.player'));
        $this->assertCount(2, session('game_session.blackjack.dealer'));
    }

    public function test_abandoning_an_unfinished_blackjack_counts_as_a_wrong_answer(): void
    {
        [$user, $quiz, $question] = $this->game();
        $game = $this->gameSession($quiz, $question);
        $game['helper_overlay'] = 'blackjack';
        $game['blackjack'] = [
            'player' => [['rank' => 10, 'suit' => '♠'], ['rank' => 7, 'suit' => '♥']],
            'dealer' => [['rank' => 9, 'suit' => '♦'], ['rank' => 8, 'suit' => '♣']],
            'deck' => [],
            'finished' => false,
            'player_value' => 17,
            'dealer_value' => 17,
        ];

        $this->withSession(['game_session' => $game])->actingAs($user)
            ->post(route('quiz.helpers.blackjack.abandon', $quiz))
            ->assertRedirect();

        // A játék a többi hibás válaszhoz hasonlóan előbb felajánlja a
        // kockás mentést; a feladás tehát nem kerülheti meg ezt az állapotot.
        $this->assertTrue(session('game_session.awaiting_dice'));
        $this->assertNull(session('game_session.helper_overlay'));
        $this->assertNull(session('game_session.blackjack'));
    }

    public function test_blackjack_tie_is_resolved_in_the_players_favour(): void
    {
        [$user, $quiz, $question] = $this->game();
        $game = $this->gameSession($quiz, $question);
        $game['helper_overlay'] = 'blackjack';
        $game['blackjack'] = [
            'player' => [['rank' => 10, 'suit' => '♠'], ['rank' => 10, 'suit' => '♥']],
            'dealer' => [['rank' => 13, 'suit' => '♦'], ['rank' => 12, 'suit' => '♣']],
            'deck' => [],
            'finished' => false,
            'player_value' => 20,
            'dealer_value' => 20,
        ];

        $this->withSession(['game_session' => $game])->actingAs($user)
            ->post(route('quiz.helpers.blackjack.action', $quiz), ['action' => 'stand'])
            ->assertRedirect();

        $this->assertTrue(session('game_session.blackjack.player_won'));
    }

    public function test_answer_order_is_shuffled_once_and_stays_stable_during_the_question(): void
    {
        [$user, $quiz, $question] = $this->game();
        $originalIds = $question->options()->pluck('id')->all();

        $this->withSession(['game_session' => $this->gameSession($quiz, $question)])
            ->actingAs($user)->get(route('quiz.play.screen', $quiz))->assertOk();

        $firstOrder = session("game_session.answer_orders.{$question->id}");
        $this->assertEqualsCanonicalizing($originalIds, $firstOrder);

        $this->get(route('quiz.play.screen', $quiz))->assertOk();
        $this->assertSame($firstOrder, session("game_session.answer_orders.{$question->id}"));
    }

    public function test_failed_dice_result_stays_on_the_game_screen_until_user_leaves(): void
    {
        [$user, $quiz, $question] = $this->game();
        $game = $this->gameSession($quiz, $question);
        $game['dice_result'] = ['roll' => 3, 'success' => false];

        $this->withSession(['game_session' => $game])->actingAs($user)
            ->get(route('quiz.play.screen', $quiz))
            ->assertOk()
            ->assertSee('Sajnos rosszat dobtál')
            ->assertSee('Vissza a kvízekhez');

        $this->post(route('quiz.roll_dice.finish', $quiz))->assertRedirect(route('quizzes.index'));
        $this->assertNull(session('game_session'));
    }

    private function game(): array
    {
        $user = User::factory()->create(['points' => 500]);
        $category = Category::create(['name' => ['hu' => 'Teszt'], 'slug' => 'helper-'.uniqid(), 'is_active' => true]);
        $quiz = Quiz::create(['creator_id' => $user->id, 'category_id' => $category->id, 'title' => 'Segítség teszt', 'status' => 'approved']);
        $question = Question::create(['quiz_id' => $quiz->id, 'category_id' => $category->id, 'difficulty' => 'medium', 'question_text' => ['hu' => 'Kérdés'], 'is_approved' => true, 'is_active' => true]);
        foreach ([true, false, false, false] as $correct) Option::create(['question_id' => $question->id, 'option_text' => ['hu' => 'Válasz'], 'is_correct' => $correct]);
        return [$user, $quiz, $question];
    }

    private function gameSession(Quiz $quiz, Question $question): array
    {
        return ['quiz_id' => $quiz->id, 'status' => 'active', 'current_question_id' => $question->id, 'answered_ids' => [], 'game_mode' => 'normal', 'time_limit' => 30, 'difficulty' => 'mixed', 'time_modifier' => 1, 'initial_bet' => 10, 'target_count' => 10];
    }
}
