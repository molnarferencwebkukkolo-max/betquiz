<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        namespace Database\Seeders;

        use App\Models\Category;
        use Illuminate\Database\Seeder;
        use Illuminate\Support\Facades\DB;
        use Illuminate\Support\Str;

        class QuizSeeder extends Seeder
        {
            public function run(): void
            {
                // 1. Kategóriák létrehozása
                $sport = Category.create([
                        'name' => 'Sport',
                        'slug' => 'sport'
                    ]);

                $tech = Category.create([
                        'name' => 'Technológia & IT',
                        'slug' => 'technologia-it'
                    ]);

                // 2. Sima kérdés hozzáadása (Sport kategória, Közepes nehézség)
                $q1 = DB::table('questions')->insertGetId([
                    'category_id' => $sport->id,
                    'difficulty' => 'medium',
                    'question_text' => 'Melyik ország nyerte a 2026-os Labdarúgó-világbajnokságot?',
                    'image_path' => null,
                    'is_approved' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Válaszok a sporthoz (1 jó, 3 rossz)
                DB::table('options')->insert([
                    ['question_id' => $q1, 'option_text' => 'Argentína', 'is_correct' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['question_id' => $q1, 'option_text' => 'Franciaország', 'is_correct' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['question_id' => $q1, 'option_text' => 'Brazília', 'is_correct' => true, 'created_at' => now(), 'updated_at' => now()], // Tegyük fel, hogy ők nyerték :)
                    ['question_id' => $q1, 'option_text' => 'Spanyolország', 'is_correct' => false, 'created_at' => now(), 'updated_at' => now()],
                ]);

                // 3. Képes kérdés hozzáadása (Tech kategória, Nehéz szint)
                $q2 = DB::table('questions')->insertGetId([
                    'category_id' => $tech->id,
                    'difficulty' => 'hard',
                    'question_text' => 'Milyen programozási nyelv logója látható a képen?',
                    'image_path' => 'questions/laravel-logo.png', // Erre majd teszünk egy tesztképet
                    'is_approved' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Válaszok a tech kérdéshez
                DB::table('options')->insert([
                    ['question_id' => $q2, 'option_text' => 'PHP (Laravel)', 'is_correct' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['question_id' => $q2, 'option_text' => 'Python', 'is_correct' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['question_id' => $q2, 'option_text' => 'JavaScript', 'is_correct' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['push_id' => $q2, 'option_text' => 'Ruby', 'is_correct' => false, 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
        }
    }
}
