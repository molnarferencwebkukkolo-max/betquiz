<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use App\Models\AdPlacement;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAdvertisementSeeder extends Seeder
{
    /**
     * A demók fix belső nevekkel frissülnek, ezért a seeder többször is
     * biztonságosan lefuttatható anélkül, hogy duplikálná a hirdetéseket.
     */
    public function run(): void
    {
        $creatorId = User::query()->where('role', 'hostadmin')->value('id');
        $definitions = [
            ['top_horizontal', 'DEMÓ – Napi kvízkaland', 'advertisements/demo/demo-top-napi-kvizkaland.jpg', 'Napi kvízkaland demó hirdetés'],
            ['top_horizontal', 'DEMÓ – Turbózd fel a tudásod', 'advertisements/demo/demo-top-tudas-turbo.jpg', 'Turbózd fel a tudásod demó hirdetés'],
            ['content_horizontal', 'DEMÓ – Alkoss saját kvízt', 'advertisements/demo/demo-content-alkoss-kvizt.jpg', 'Alkoss saját kvízt demó hirdetés'],
            ['content_horizontal', 'DEMÓ – Hétvégi kvízbajnokság', 'advertisements/demo/demo-content-hetvegi-bajnoksag.jpg', 'Hétvégi kvízbajnokság demó hirdetés'],
            ['right_sidebar', 'DEMÓ – VIP kvízklub', 'advertisements/demo/demo-sidebar-vip-klub.jpg', 'VIP kvízklub demó hirdetés'],
            ['right_sidebar', 'DEMÓ – Napi kihívás', 'advertisements/demo/demo-sidebar-napi-kihivas.jpg', 'Napi kihívás demó hirdetés'],
            ['game_decision_square', 'DEMÓ – Nyerj még több PT-t', 'advertisements/demo/demo-game-nyerj-tobb-pt.jpg', 'Nyerj még több PT-t demó hirdetés'],
            ['game_decision_square', 'DEMÓ – Napi bónusz', 'advertisements/demo/demo-game-napi-bonusz.jpg', 'Napi bónusz demó hirdetés'],
        ];

        foreach ($definitions as [$placementKey, $name, $imagePath, $altText]) {
            $placement = AdPlacement::query()->where('key', $placementKey)->firstOrFail();
            $advertisement = Advertisement::query()->updateOrCreate(
                ['name' => $name],
                [
                    'created_by' => $creatorId,
                    'type' => 'image',
                    'image_path' => $imagePath,
                    'target_url' => url('/'),
                    'alt_text' => $altText,
                    'adsense_code' => null,
                    'weight' => 1,
                    'is_active' => true,
                    'starts_at' => null,
                    'ends_at' => null,
                ]
            );
            $advertisement->placements()->sync([$placement->id]);
        }
    }
}
