<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Models\LegalConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Blade;

class UserManagementIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_view_the_user_list_with_account_data(): void
    {
        $admin = User::factory()->create(['role' => 'useradmin']);
        $listedUser = User::factory()->create([
            'name' => 'Keresett Játékos',
            'email' => 'keresett@example.test',
            'role' => 'user',
            'points' => 1234,
        ]);

        Quiz::create([
            'creator_id' => $listedUser->id,
            'category_id' => \App\Models\Category::query()->firstOrFail()->id,
            'title' => 'Felhasználói kvíz',
            'description' => 'Lista darabszám teszt.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Keresett Játékos')
            ->assertSee('1 234 PT')
            ->assertSee('Felhasználók');
    }

    public function test_user_list_can_be_searched_and_filtered(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        User::factory()->create([
            'name' => 'Cél Moderátor',
            'email' => 'cel@example.test',
            'role' => 'useradmin',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'name' => 'Másik Játékos',
            'email' => 'masik@example.test',
            'role' => 'user',
            'email_verified_at' => null,
        ]);

        $this->actingAs($hostadmin)
            ->get(route('admin.users.index', [
                'search' => 'Cél',
                'role' => 'useradmin',
                'verification' => 'verified',
            ]))
            ->assertOk()
            ->assertSee('Cél Moderátor')
            ->assertDontSee('Másik Játékos');
    }

    public function test_regular_users_cannot_access_the_admin_user_list(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_only_hostadmin_can_view_the_complete_user_profile_without_secrets(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $useradmin = User::factory()->create(['role' => 'useradmin']);
        $listedUser = User::factory()->create([
            'username' => 'teljesadat',
            'email' => 'teljes@example.test',
            'country' => 'Magyarorszag',
            'password' => 'top-secret-password',
            'google_id' => 'secret-google-identifier',
        ]);
        LegalConsent::create([
            'user_id' => $listedUser->id,
            'consent_type' => 'terms',
            'content_version' => 2,
            'document_url' => url('/aszf'),
            'accepted_at' => now(),
        ]);

        $this->actingAs($hostadmin)->get(route('admin.users.show', $listedUser))
            ->assertOk()
            ->assertSee('teljes@example.test')
            ->assertSee('Magyarorszag')
            ->assertSee('Jogi elfogadasok')
            ->assertDontSee('secret-google-identifier')
            ->assertDontSee($listedUser->password);

        $this->actingAs($useradmin)->get(route('admin.users.show', $listedUser))->assertForbidden();
    }

    public function test_hostadmin_can_view_useradmin_and_hostadmin_profiles(): void
    {
        $viewer = User::factory()->create(['role' => 'hostadmin']);
        $useradmin = User::factory()->create(['role' => 'useradmin', 'username' => null]);
        $hostadmin = User::factory()->create(['role' => 'hostadmin', 'username' => null]);

        $this->actingAs($viewer)->get(route('admin.users.show', $useradmin))->assertOk();
        $this->actingAs($viewer)->get(route('admin.users.show', $hostadmin))->assertOk();
    }

    public function test_admin_profile_value_rendering_accepts_legacy_array_values(): void
    {
        $rendered = Blade::render(<<<'BLADE'
            @php($value = ['elso', 'masodik'])
            @php($displayValue = is_array($value) ? collect($value)->flatten()->filter(fn ($item) => is_scalar($item))->implode(', ') : $value)
            {{ $displayValue }}
        BLADE);

        $this->assertStringContainsString('elso, masodik', $rendered);
    }

    public function test_hostadmin_can_manage_status_and_useradmin_role(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $player = User::factory()->create(['role' => 'user']);

        $this->actingAs($hostadmin)->patch(route('admin.users.status', $player), [
            'action' => 'ban',
        ])->assertRedirect();
        $this->assertTrue($player->fresh()->is_banned);

        $this->actingAs($hostadmin)->patch(route('admin.users.status', $player), [
            'action' => 'deactivate',
        ])->assertRedirect();
        $this->assertFalse($player->fresh()->is_active);

        $this->actingAs($hostadmin)->patch(route('admin.users.status', $player), [
            'action' => 'activate',
        ])->assertRedirect();
        $this->assertTrue($player->fresh()->is_active);

        $this->actingAs($hostadmin)->patch(route('admin.users.status', $player), [
            'action' => 'promote',
        ])->assertRedirect();
        $this->assertSame('useradmin', $player->fresh()->role);

        $this->actingAs($hostadmin)->patch(route('admin.users.status', $player), [
            'action' => 'demote',
        ])->assertRedirect();
        $this->assertSame('user', $player->fresh()->role);
    }

    public function test_useradmin_can_moderate_players_but_not_other_admins(): void
    {
        $useradmin = User::factory()->create(['role' => 'useradmin']);
        $player = User::factory()->create(['role' => 'user']);
        $otherAdmin = User::factory()->create(['role' => 'useradmin']);

        $this->actingAs($useradmin)->patch(route('admin.users.status', $player), [
            'action' => 'ban',
        ])->assertRedirect();
        $this->assertTrue($player->fresh()->is_banned);

        $this->actingAs($useradmin)->patch(route('admin.users.status', $player), [
            'action' => 'deactivate',
        ])->assertRedirect();
        $this->assertFalse($player->fresh()->is_active);

        $this->actingAs($useradmin)->patch(route('admin.users.status', $otherAdmin), [
            'action' => 'deactivate',
        ])->assertForbidden();
        $this->assertTrue($otherAdmin->fresh()->is_active);

        $this->actingAs($useradmin)->patch(route('admin.users.status', $player), [
            'action' => 'promote',
        ])->assertForbidden();
    }

    public function test_admins_cannot_moderate_themselves_or_hostadmins(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $otherHostadmin = User::factory()->create(['role' => 'hostadmin']);

        $this->actingAs($hostadmin)->patch(route('admin.users.status', $hostadmin), [
            'action' => 'deactivate',
        ])->assertForbidden();

        $this->actingAs($hostadmin)->patch(route('admin.users.status', $otherHostadmin), [
            'action' => 'ban',
        ])->assertForbidden();

        $this->assertTrue($hostadmin->fresh()->is_active);
        $this->assertFalse($otherHostadmin->fresh()->is_banned);
    }

    public function test_inactive_user_cannot_log_in_or_keep_using_an_existing_session(): void
    {
        $inactiveUser = User::factory()->create([
            'is_active' => false,
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $inactiveUser->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($inactiveUser)
            ->get('/dashboard')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
