<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Keresünk egy elsődleges kategóriát és usert a DEMÓ kvízhez
        $firstCategoryId = DB::table('categories')->value('id') ?? 1;
        $firstUserId = DB::table('users')->value('id') ?? 1;

        // 2. Létrehozzuk a DEMÓ Kvízt
        $demoQuizId = DB::table('quizzes')->insertGetId([
            'title' => '🎮 DEMÓ Kvíz (Általános kérdések)',
            'description' => 'A rendszer korábbi gyűjtőkérdései egy csomagban.',
            'category_id' => $firstCategoryId,
            'creator_id' => $firstUserId,
            'status' => 'approved', // <-- ITT ÁTÍRTUK 'approved'-ra!
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Minden olyan kérdést, aminek még nincs quiz_id-ja, átirányítunk a DEMÓ kvíz alá
        DB::table('questions')
            ->whereNull('quiz_id')
            ->update(['quiz_id' => $demoQuizId]);
    }

    public function down(): void
    {
        // Visszarollback esetén nem törlünk adatot
    }
};
