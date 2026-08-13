<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_google_user_can_create_an_account_and_login(): void
    {
        $this->mockGoogleUser('google-123', 'new@example.com', 'New User');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'google-onboarding');

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame(1000, $user->points);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->getRawOriginal('password'));
        $this->assertNull($user->username);
    }

    public function test_verified_google_email_safely_links_an_existing_account(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.com', 'google_id' => null]);
        $this->mockGoogleUser('google-456', 'existing@example.com', 'Google Name');

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google-456', $existing->fresh()->google_id);
        $this->assertSame(1, User::query()->where('email', 'existing@example.com')->count());
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->mockGoogleUser('google-unverified', 'unverified@example.com', 'User', false);

        $this->get(route('auth.google.callback'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'unverified@example.com']);
    }

    public function test_inactive_or_banned_account_cannot_login_or_be_linked(): void
    {
        foreach ([['is_active' => false], ['is_banned' => true]] as $index => $state) {
            $user = User::factory()->create(array_merge([
                'email' => "blocked{$index}@example.com",
                'google_id' => null,
            ], $state));
            $this->mockGoogleUser("blocked-google-{$index}", $user->email, 'Blocked User');

            $this->get(route('auth.google.callback'))->assertSessionHasErrors('email');

            $this->assertGuest();
            $this->assertNull($user->fresh()->google_id);
        }
    }

    public function test_login_and_registration_pages_offer_google_authentication(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('auth.google.redirect'), false);
        $this->get(route('register'))->assertOk()->assertSee(route('auth.google.redirect'), false);
    }

    private function mockGoogleUser(string $id, string $email, string $name, bool $verified = true): void
    {
        $googleUser = (new GoogleUser())->map([
            'id' => $id,
            'email' => $email,
            'name' => $name,
        ])->setRaw(['email_verified' => $verified]);
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
    }
}
