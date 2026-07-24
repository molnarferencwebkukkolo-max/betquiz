<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idegen kulcs ellenőrzés kikapcsolása az beszúrás idejére
        Schema::disableForeignKeyConstraints();

        // 1. Biztosítjuk, hogy létezzen legalább egy kategória
        $firstCategoryId = DB::table('categories')->value('id');
        if (!$firstCategoryId) {
            $firstCategoryId = DB::table('categories')->insertGetId([
                'name' => json_encode(['hu' => 'Általános']),
                'slug' => 'altalanos', // 🎯 EZ HIÁNYZOTT!
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Biztosítjuk, hogy létezzen legalább egy user
        $firstUserId = DB::table('users')->value('id');
        if (!$firstUserId) {
            $firstUserId = DB::table('users')->insertGetId([
                'name' => 'Admin User',
                'email' => 'admin@test.com',
                'password' => bcrypt('password'),
                'points' => 100000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Létrehozzuk a DEMÓ Kvízt
        $demoQuizId = DB::table('quizzes')->insertGetId([
            'title' => '🎮 DEMÓ Kvíz (Általános kérdések)',
            'description' => 'A rendszer korábbi gyűjtőkérdései egy csomagban.',
            'category_id' => $firstCategoryId,
            'creator_id' => $firstUserId,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Minden olyan kérdést, aminek még nincs quiz_id-ja, átirányítunk a DEMÓ kvíz alá
        DB::table('questions')
            ->whereNull('quiz_id')
            ->update(['quiz_id' => $demoQuizId]);

        // Idegen kulcs ellenőrzés visszakapcsolása
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Visszarollback esetén nem törlünk adatot
    }
};
