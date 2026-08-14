<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Ez kizárólag régi, már létező adatbázisok adatmentő migrációja.
        // Friss telepítésen nem hozunk létre tesztfelhasználót vagy üres demókvízt.
        if (! DB::table('questions')->whereNull('quiz_id')->exists()) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            // Örökölt kérdések mellett jellemzően ezek a rekordok már léteznek.
            // A fallback rekordok csak valódi, árva adatok megmentéséhez készülnek.
            $firstCategoryId = DB::table('categories')->value('id');
            if (! $firstCategoryId) {
                $firstCategoryId = DB::table('categories')->insertGetId([
                    'name' => json_encode(['hu' => 'Örökölt tartalom'], JSON_UNESCAPED_UNICODE),
                    'slug' => 'orokolt-tartalom',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $firstUserId = DB::table('users')->value('id');
            if (! $firstUserId) {
                $firstUserId = DB::table('users')->insertGetId([
                    'name' => 'KwizzGo migráció',
                    'email' => 'migration@kwizzgo.invalid',
                    // Nem ismert és nem visszanyerhető jelszó: ez nem belépési fiók.
                    'password' => bcrypt(Str::password(64)),
                    'role' => 'user',
                    'points' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $legacyQuizId = DB::table('quizzes')->insertGetId([
                'title' => 'Örökölt kérdések',
                'description' => 'Korábbi, kvízhez még nem rendelt kérdések automatikus gyűjtője.',
                'category_id' => $firstCategoryId,
                'creator_id' => $firstUserId,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('questions')
                ->whereNull('quiz_id')
                ->update(['quiz_id' => $legacyQuizId]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // Visszarollback esetén nem törlünk adatot
    }
};
