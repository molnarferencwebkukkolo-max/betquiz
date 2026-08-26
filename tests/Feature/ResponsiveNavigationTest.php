<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_navigation_contains_accessible_mobile_controls_and_auth_actions(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-mobile-navigation', false)
            ->assertSee('aria-controls="kwizzgo-navigation"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('class="nav-menu-overlay"', false)
            ->assertSee('Menü bezárása')
            ->assertSee('Bejelentkezés')
            ->assertSee('Regisztráció')
            ->assertSee("event.key === 'Escape'", false)
            ->assertSee('panel.inert', false);
    }

    public function test_regular_user_navigation_contains_account_actions_without_admin_links(): void
    {
        $user = User::factory()->create(['role' => 'user', 'points' => 1250]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kvízeim')
            ->assertSee('Értesítések')
            ->assertSee('1,250 PT')
            ->assertSee('Kijelentkezés')
            ->assertDontSee('class="nav-group-label">Adminisztráció', false);
    }

    public function test_useradmin_and_hostadmin_receive_their_role_specific_mobile_links(): void
    {
        $useradmin = User::factory()->create(['role' => 'useradmin']);
        $this->actingAs($useradmin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Adminisztráció')
            ->assertSee('Kérdésbank')
            ->assertSee('Felhasználók')
            ->assertDontSee('Tartalomkezelő');

        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $this->actingAs($hostadmin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kérdésbank')
            ->assertSee('Felhasználók')
            ->assertSee('Tartalomkezelő')
            ->assertSee('Hirdetések')
            ->assertSee('Kategóriák');
    }
}
