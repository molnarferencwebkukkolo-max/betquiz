<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\AdPlacement;
use App\Models\User;
use App\Services\AdSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvertisingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_hostadmin_can_open_advertising_admin(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $useradmin = User::factory()->create(['role' => 'useradmin']);

        $this->actingAs($hostadmin)
            ->get(route('admin.advertisements.index'))
            ->assertOk()
            ->assertSee('Hirdetések kezelése');

        $this->actingAs($useradmin)
            ->get(route('admin.advertisements.index'))
            ->assertForbidden();
    }

    public function test_hostadmin_can_create_an_image_ad_for_multiple_placements(): void
    {
        Storage::fake('public');
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $placements = AdPlacement::query()->take(2)->pluck('id')->all();

        $this->actingAs($hostadmin)->post(route('admin.advertisements.store'), [
            'name' => 'Partner banner',
            'type' => 'image',
            'image' => UploadedFile::fake()->image('banner.jpg', 970, 250),
            'target_url' => 'https://example.com/ajanlat',
            'alt_text' => 'Partner ajánlat',
            'weight' => 3,
            'is_active' => '1',
            'placements' => $placements,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $advertisement = Advertisement::query()->firstOrFail();
        $this->assertSame(2, $advertisement->placements()->count());
        $this->assertSame(3, $advertisement->weight);
        Storage::disk('public')->assertExists($advertisement->image_path);
    }

    public function test_only_recognizable_adsense_code_is_accepted(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $placement = AdPlacement::query()->firstOrFail();
        $base = [
            'name' => 'AdSense egység',
            'type' => 'adsense',
            'weight' => 1,
            'is_active' => '1',
            'placements' => [$placement->id],
        ];

        $this->actingAs($hostadmin)->post(route('admin.advertisements.store'), $base + [
            'adsense_code' => '<script src="https://evil.example/payload.js"></script><ins class="adsbygoogle" data-ad-client="ca-pub-123"></ins>',
        ])->assertSessionHasErrors('adsense_code');

        $validCode = '<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-123456789" data-ad-slot="123456"></ins>'
            .'<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-123456789" crossorigin="anonymous"></script>'
            .'<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';

        $this->actingAs($hostadmin)->post(route('admin.advertisements.store'), $base + [
            'adsense_code' => $validCode,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('advertisements', ['type' => 'adsense', 'name' => 'AdSense egység']);
    }

    public function test_ad_free_user_never_receives_an_ad(): void
    {
        $placement = AdPlacement::query()->where('key', 'content_horizontal')->firstOrFail();
        $advertisement = Advertisement::create([
            'name' => 'Teszt banner',
            'type' => 'image',
            'image_path' => 'advertisements/test.jpg',
            'target_url' => 'https://example.com',
            'weight' => 1,
            'is_active' => true,
        ]);
        $advertisement->placements()->attach($placement);

        $regularUser = User::factory()->create(['is_ad_free' => false]);
        $this->actingAs($regularUser);
        $this->assertSame($advertisement->id, app(AdSelector::class)->forPlacement('content_horizontal')?->id);

        $adFreeUser = User::factory()->create(['is_ad_free' => true]);
        $this->actingAs($adFreeUser);
        request()->attributes->remove('kwizzgo.selected-ad.content_horizontal');
        $this->assertNull(app(AdSelector::class)->forPlacement('content_horizontal'));
        $this->assertStringNotContainsString('Teszt banner', Blade::render('<x-ad-slot position="content_horizontal" />'));
    }

    public function test_only_hostadmin_can_change_ad_free_status(): void
    {
        $player = User::factory()->create();
        $useradmin = User::factory()->create(['role' => 'useradmin']);
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);

        $this->actingAs($useradmin)->patch(route('admin.users.status', $player), [
            'action' => 'enable_ad_free',
        ])->assertForbidden();

        $this->actingAs($hostadmin)->patch(route('admin.users.status', $player), [
            'action' => 'enable_ad_free',
        ])->assertRedirect();

        $this->assertTrue($player->fresh()->is_ad_free);
    }
}
