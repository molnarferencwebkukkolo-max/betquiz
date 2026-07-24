<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Category;
use App\Models\Quiz;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Idegen kulcs ellenőrzés kikapcsolása SQLite-nál
        DB::statement('PRAGMA foreign_keys = OFF;');

        // 2. Felhasználó létrehozása (ID: 1)
        $user = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'points' => 100000,
                'role' => 'hostadmin',
            ]
        );

        // 3. Kategória létrehozása (ID: 1)
        $category = Category::firstOrCreate(
            ['id' => 1],
            [
                'name' => ['hu' => 'Általános'],
            ]
        );

        // 4. Demó Kvíz létrehozása
        Quiz::firstOrCreate(
            ['title' => '🎮 DEMÓ Kvíz (Általános kérdések)'],
            [
                'description' => 'A rendszer korábbi gyűjtőkérdései egy csomagban.',
                'category_id' => $category->id,
                'creator_id' => $user->id,
                'status' => 'approved',
            ]
        );

        // 5. Idegen kulcs ellenőrzés visszakapcsolása
        DB::statement('PRAGMA foreign_keys = ON;');
    }
}
