<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrivateProfileDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_receives_two_thousand_points_once_for_completing_private_details(): void
    {
        $user = User::factory()->create(['points' => 1000]);
        $category = Category::query()->where('is_active', true)->firstOrFail();
        $details = $this->details($category);

        $this->actingAs($user)
            ->patch(route('profile.private-details'), $details)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'A privát profiladatok mentve. Jóváírtunk 2 000 PT ajándékot!');

        $user->refresh();
        $this->assertSame(3000, $user->points);
        $this->assertNotNull($user->profile_details_rewarded_at);
        $this->assertSame($category->id, $user->favorite_category_id);

        $this->actingAs($user)
            ->patch(route('profile.private-details'), array_merge($details, ['children_count' => 2]))
            ->assertSessionHasNoErrors();

        $this->assertSame(3000, $user->fresh()->points);
        $this->assertSame(2, $user->fresh()->children_count);
    }

    public function test_incomplete_or_invalid_private_details_do_not_grant_reward(): void
    {
        $user = User::factory()->create(['points' => 500]);

        $this->actingAs($user)->patch(route('profile.private-details'), [
            'birth_date' => now()->addDay()->toDateString(),
            'gender' => 'invalid',
            'children_count' => -1,
        ])->assertSessionHasErrors();

        $this->assertSame(500, $user->fresh()->points);
        $this->assertNull($user->fresh()->profile_details_rewarded_at);
    }

    public function test_google_only_user_can_set_first_password_without_current_password(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'google-password-test']);

        $this->actingAs($user)->post(route('profile.password'), [
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('StrongPassword123!', $user->fresh()->password));
    }

    public function test_profile_explains_that_details_are_private_and_lists_active_categories(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('sehol nem jelenítjük meg')
            ->assertSee('+2 000 PT')
            ->assertSee($category->translated_name);
    }

    private function details(Category $category): array
    {
        return [
            'birth_date' => '1990-05-12',
            'gender' => 'prefer_not_to_say',
            'country' => 'Magyarország',
            'county' => 'Pest',
            'favorite_category_id' => $category->id,
            'relationship_status' => 'relationship',
            'children_count' => 0,
        ];
    }
}
