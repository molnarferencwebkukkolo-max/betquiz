<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_profile_results(): void
    {
        $this->get(route('profile.results'))->assertRedirect(route('login'));
    }

    public function test_user_sees_only_own_aggregated_results(): void
    {
        $category = Category::create([
            'name' => ['hu' => 'Általános'],
            'slug' => 'eredmenyek-'.uniqid(),
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $quiz = Quiz::create([
            'creator_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Saját eredménykvíz',
            'status' => 'approved',
        ]);

        foreach ([true, false, true] as $index => $correct) {
            $questionId = DB::table('questions')->insertGetId([
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
            ]);
            DB::table('user_answers')->insert([
                'user_id' => $index === 2 ? $otherUser->id : $user->id,
                'quiz_id' => $quiz->id,
                'question_id' => $questionId,
                'is_correct' => $correct,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($user)
            ->get(route('profile.results'))
            ->assertOk()
            ->assertSee('Eredményeim')
            ->assertSee('Saját eredménykvíz')
            ->assertSee('50%');
    }

    public function test_profile_links_to_results_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee(route('profile.results'), false);
    }

    public function test_creator_sees_other_players_answer_count_and_reward_but_not_own_play(): void
    {
        $category = Category::create([
            'name' => ['hu' => 'Általános'],
            'slug' => 'alkotoi-'.uniqid(),
            'is_active' => true,
        ]);
        $creator = User::factory()->create();
        $player = User::factory()->create();
        $quiz = Quiz::create([
            'creator_id' => $creator->id,
            'category_id' => $category->id,
            'title' => 'Alkotói jutalom kvíz',
            'status' => 'approved',
        ]);

        foreach ([$player, $player, $creator] as $index => $answeringUser) {
            $questionId = DB::table('questions')->insertGetId([
                'quiz_id' => $quiz->id,
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'question_text' => json_encode(['hu' => "Alkotói kérdés {$index}"]),
                'is_approved' => true,
                'is_active' => true,
                'times_answered' => 0,
                'times_correct' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('user_answers')->insert([
                'user_id' => $answeringUser->id,
                'quiz_id' => $quiz->id,
                'question_id' => $questionId,
                'is_correct' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($creator)
            ->get(route('profile.results'))
            ->assertOk()
            ->assertSee('Alkotói eredmények')
            ->assertSee('Alkotói jutalom kvíz')
            ->assertSee('+2 PT');
    }
}
