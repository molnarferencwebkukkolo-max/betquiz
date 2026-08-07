<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_password(): void
    {
        $user = User::factory()->create(['password' => 'current-password']);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'current-password',
            'password' => 'updated-secure-password',
            'password_confirmation' => 'updated-secure-password',
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('updated-secure-password', $user->fresh()->password));
    }
}
