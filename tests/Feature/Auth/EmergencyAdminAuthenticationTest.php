<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmergencyAdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'recaptcha.enabled' => false,
            'emergency_admin.enabled' => true,
            'emergency_admin.email' => 'emergency@example.test',
            'emergency_admin.password_hash' => Hash::make('very-strong-emergency-password'),
        ]);
    }

    public function test_env_admin_can_log_in_without_preexisting_user_record(): void
    {
        $this->assertDatabaseMissing('users', ['email' => 'emergency@example.test']);

        $this->post('/login', [
            'email' => 'emergency@example.test',
            'password' => 'very-strong-emergency-password',
        ])->assertRedirect('/dashboard');

        $user = User::query()->where('email', 'emergency@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('KwizzGo', $user->name);
        $this->assertSame('KwizzGo', $user->username);
        $this->assertSame('hostadmin', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_banned);
        $this->assertTrue($user->is_ad_free);
        $this->assertFalse(Hash::check('very-strong-emergency-password', $user->password));
    }

    public function test_wrong_emergency_password_is_rejected(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'emergency@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'emergency@example.test']);
    }

    public function test_disabled_emergency_admin_cannot_provision_account(): void
    {
        config(['emergency_admin.enabled' => false]);

        $this->from('/login')->post('/login', [
            'email' => 'emergency@example.test',
            'password' => 'very-strong-emergency-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'emergency@example.test']);
    }

    public function test_successful_login_restores_existing_emergency_account_state(): void
    {
        $user = User::factory()->create([
            'email' => 'emergency@example.test',
            'role' => 'player',
            'is_active' => false,
            'is_banned' => true,
        ]);

        $this->post('/login', [
            'email' => 'emergency@example.test',
            'password' => 'very-strong-emergency-password',
        ])->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertSame('hostadmin', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_banned);
    }

    public function test_emergency_admin_can_work_while_coming_soon_mode_is_enabled(): void
    {
        config(['app.coming_soon' => true]);

        $this->post('/login', [
            'email' => 'emergency@example.test',
            'password' => 'very-strong-emergency-password',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
    }

    public function test_emergency_admin_cannot_rename_the_env_controlled_identity(): void
    {
        $this->post('/login', [
            'email' => 'emergency@example.test',
            'password' => 'very-strong-emergency-password',
        ]);

        $this->patch('/profile', [
            'username' => 'masik_nev',
            'email' => 'other@example.test',
        ])->assertSessionHasErrors('username');

        $user = User::query()->where('email', 'emergency@example.test')->firstOrFail();
        $this->assertSame('KwizzGo', $user->username);
        $this->assertSame('KwizzGo', $user->name);
    }
}
