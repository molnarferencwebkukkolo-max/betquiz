<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create([
            'key' => 'registration_bonus_tokens',
            'value' => '100',
            'description' => [
                'hu' => 'Új regisztrációért járó ajándék zsetonok száma.',
                'en' => 'Number of gift tokens awarded for new registration.'
            ]
        ]);

        Setting::create([
            'key' => 'daily_bonus_tokens',
            'value' => '20',
            'description' => [
                'hu' => 'Napi bejelentkezésért járó ingyenes zseton bónusz.',
                'en' => 'Free token bonus for daily login.'
            ]
        ]);
    }
}
