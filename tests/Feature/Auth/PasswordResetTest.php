<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_screen_can_be_rendered_from_login(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Elfelejtetted a jelszavad?')
            ->assertSee(route('password.request'));

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Jelszó visszaállítása');
    }

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_and_the_new_password_can_authenticate(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'password' => 'old-password',
            'is_active' => true,
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $response = $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

            $response->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });

        $this->assertFalse(Hash::check('old-password', $user->fresh()->password));
        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'new-secure-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_reset_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
