<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_confirmed(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->post('/confirm-password', [
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_wrong_confirmation_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->post('/confirm-password', [
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('password');
    }
}
