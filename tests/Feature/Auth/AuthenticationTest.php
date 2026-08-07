<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Bejelentkezés');
    }

    public function test_user_can_authenticate_and_session_is_regenerated(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $oldSessionId = $this->app['session']->getId();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSessionId, $this->app['session']->getId());
    }

    public function test_invalid_credentials_are_visible_on_the_login_screen(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();

        // A redirectet ugyanabban a tesztkérés-láncban követjük, hogy a
        // flash sessionben tárolt validációs hiba biztosan elérhető legyen.
        $this->followingRedirects()->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])
            ->assertSee('A bejelentkezés nem sikerült.')
            ->assertSee(__('auth.failed'));
    }

    public function test_inactive_user_cannot_authenticate_and_sees_the_reason(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Ez a felhasználói fiók inaktív.']);

        $this->assertGuest();
    }

    public function test_inactive_authenticated_user_is_logged_out(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_log_out_and_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
