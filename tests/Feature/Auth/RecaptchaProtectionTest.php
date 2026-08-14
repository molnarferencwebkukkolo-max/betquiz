<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'recaptcha.enabled' => true,
            'recaptcha.site_key' => 'test-site-key',
            'recaptcha.secret_key' => 'test-secret-key',
        ]);
    }

    public function test_login_and_registration_forms_render_v3_integration(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('api.js?render=test-site-key', false)
            ->assertSee('data-recaptcha-action="login"', false);
        $this->get('/register')->assertOk()
            ->assertSee('api.js?render=test-site-key', false)
            ->assertSee('data-recaptcha-action="register"', false);
    }

    public function test_login_is_rejected_without_recaptcha_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('g-recaptcha-response');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_valid_google_response_allows_login(): void
    {
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
            'score' => 0.9,
            'action' => 'login',
        ])]);
        $user = User::factory()->create(['password' => 'password']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'g-recaptcha-response' => 'valid-token',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_google_response_blocks_registration(): void
    {
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false])]);

        $this->from('/register')->post('/register', [
            'username' => 'tesztelo',
            'email' => 'teszt@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'g-recaptcha-response' => 'invalid-token',
        ])->assertRedirect('/register')->assertSessionHasErrors('g-recaptcha-response');

        $this->assertDatabaseMissing('users', ['email' => 'teszt@example.com']);
    }

    public function test_low_score_or_wrong_action_is_rejected(): void
    {
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
            'score' => 0.9,
            'action' => 'register',
        ])]);
        $user = User::factory()->create(['password' => 'password']);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'g-recaptcha-response' => 'token-for-another-action',
        ])->assertRedirect('/login')->assertSessionHasErrors('g-recaptcha-response');

        $this->assertGuest();
    }
}
