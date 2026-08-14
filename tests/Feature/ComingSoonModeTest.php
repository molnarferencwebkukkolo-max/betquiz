<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComingSoonModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_coming_soon_page_when_mode_is_enabled(): void
    {
        config(['app.coming_soon' => true]);

        $this->get('/')
            ->assertServiceUnavailable()
            ->assertHeader('Retry-After', '3600')
            ->assertSee('hamarosan indul');
    }

    public function test_login_remains_available_in_coming_soon_mode(): void
    {
        config(['app.coming_soon' => true]);

        $this->get('/login')->assertOk();
    }

    public function test_hostadmin_can_use_application_in_coming_soon_mode(): void
    {
        config(['app.coming_soon' => true]);
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);

        $this->actingAs($hostadmin)->get('/dashboard')->assertOk();
    }

    public function test_regular_user_still_sees_coming_soon_page(): void
    {
        config(['app.coming_soon' => true]);
        $player = User::factory()->create(['role' => 'player']);

        $this->actingAs($player)->get('/dashboard')->assertServiceUnavailable();
    }

    public function test_site_operates_normally_when_mode_is_disabled(): void
    {
        config(['app.coming_soon' => false]);

        $this->get('/')->assertOk();
    }
}
