<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ez a pozíció csak a helyes válasz utáni döntési képernyőhöz tartozik,
        // ezért a kreatívjai a többi oldalsávos reklámtól függetlenül kezelhetők.
        DB::table('ad_placements')->updateOrInsert(
            ['key' => 'game_decision_square'],
            [
                'name' => 'Játék – döntési képernyő négyzet',
                'format' => 'square',
                'description' => 'A helyes válasz utáni döntési kártya jobb oldalán megjelenő négyzetes hirdetés.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('ad_placements')->where('key', 'game_decision_square')->delete();
    }
};
