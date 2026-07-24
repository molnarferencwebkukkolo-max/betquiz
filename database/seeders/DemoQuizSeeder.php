<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;

class DemoQuizSeeder extends Seeder
{
    public function run(): void
    {
        // Keressünk vagy hozzunk létre egy alapértelmezett kategóriát
        $category = Category::first() ?? Category::create(['name' => 'Demó Kategória']);

        for ($i = 1; $i <= 5; $i++) {
            // 1. Felhasználó felvétele
            $user = User::firstOrCreate(
                ['email' => "demouser{$i}@test.com"],
                [
                    'name' => "Demouser#{$i}",
                    'password' => Hash::make('password'),
                    'points' => rand(5000, 150000),
                    'email_verified_at' => now(),
                ]
            );

            // 2. Kvíz létrehozása ('approved' státusszal)
            $quiz = Quiz::create([
                'title' => "DEMO KVÍZ #{$i}",
                'description' => "Ez egy automatikusan generált teszt kvíz 100 kérdéssel a játékmenet teszteléséhez.",
                'category_id' => $category->id,
                'creator_id' => $user->id,
                'status' => 'approved',
            ]);

            // 3. 100 darab kérdés és válaszlehetőségek legyártása
            for ($q = 1; $q <= 100; $q++) {
                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'category_id' => $category->id,
                    'question_text' => "Mi a helyes válasz? (#{$q})",
                ]);

                // Válaszlehetőségek beszúrása a reláción keresztül
                $question->options()->createMany([
                    [
                        'option_text' => 'helyes válasz',
                        'is_correct' => true,
                    ],
                    [
                        'option_text' => 'Rossz válasz',
                        'is_correct' => false,
                    ],
                    [
                        'option_text' => 'Nem jó válasz',
                        'is_correct' => false,
                    ],
                    [
                        'option_text' => 'Szerintem ne erre tippelj',
                        'is_correct' => false,
                    ],
                ]);
            }
        }
    }
}
