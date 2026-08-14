<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileBasicDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_edit_basic_data_and_username(): void
    {
        $user = User::factory()->create(['role' => 'useradmin']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'username' => 'Teszt_Jatekos',
            'email' => 'updated@example.com',
            'role' => 'user',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', 'profile-updated');

        $user->refresh();
        $this->assertSame('teszt_jatekos', $user->name);
        $this->assertSame('teszt_jatekos', $user->username);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame('useradmin', $user->role);
    }

    public function test_username_must_be_unique(): void
    {
        User::factory()->create(['username' => 'foglalt']);
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'username' => 'FOGLALT',
            'email' => $user->email,
        ])->assertSessionHasErrors('username');
    }

    public function test_profile_shows_username_and_basic_data_edit_link(): void
    {
        $user = User::factory()->create(['username' => 'kwizzmester']);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('kwizzmester')
            ->assertSee(route('profile.edit'), false);
    }

    public function test_profile_does_not_offer_or_route_self_service_role_switching(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertDontSee('Szerepkör váltás');

        $this->actingAs($user)
            ->post('/profile/switch-role', ['role' => 'hostadmin'])
            ->assertNotFound();

        $this->assertSame('user', $user->fresh()->role);
    }
}
