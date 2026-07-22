<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use App\Models\Option;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Kategóriák biztonságos létrehozása (firstOrCreate)
        $sport = Category::firstOrCreate(
            ['slug' => 'sport'],
            [
                'name' => ['hu' => 'Sport', 'en' => 'Sports'],
                'icon' => 'fa-trophy',
                'is_active' => true
            ]
        );

        $science = Category::firstOrCreate(
            ['slug' => 'tudomany'],
            [
                'name' => ['hu' => 'Tudomány', 'en' => 'Science'],
                'icon' => 'fa-flask',
                'is_active' => true
            ]
        );

        $user = User::first();
        $userId = $user ? $user->id : null;

        // Töröljük a régi kérdéseket a seed előtt, hogy tiszta lappal induljunk
        Question::query()->delete();

        // 2. Kérdés #1
        $q1 = Question::create([
            'category_id' => $sport->id,
            'difficulty' => 'easy',
            'question_text' => ['hu' => 'Hány percig tart egy szabályos labdarúgó-mérkőzés (hosszabbítás nélkül)?', 'en' => 'How many minutes does a regular football match last?'],
            'is_approved' => true,
            'is_active' => true,
            'creator_id' => $userId
        ]);
        Option::create(['question_id' => $q1->id, 'option_text' => ['hu' => '90 perc', 'en' => '90 minutes'], 'is_correct' => true]);
        Option::create(['question_id' => $q1->id, 'option_text' => ['hu' => '80 perc', 'en' => '80 minutes'], 'is_correct' => false]);
        Option::create(['question_id' => $q1->id, 'option_text' => ['hu' => '60 perc', 'en' => '60 minutes'], 'is_correct' => false]);
        Option::create(['question_id' => $q1->id, 'option_text' => ['hu' => '100 perc', 'en' => '100 minutes'], 'is_correct' => false]);

        // 3. Kérdés #2
        $q2 = Question::create([
            'category_id' => $science->id,
            'difficulty' => 'medium',
            'question_text' => ['hu' => 'Melyik a Naprendszer legnagyobb bolygója?', 'en' => 'Which is the largest planet in the Solar System?'],
            'is_approved' => true,
            'is_active' => true,
            'creator_id' => $userId
        ]);
        Option::create(['question_id' => $q2->id, 'option_text' => ['hu' => 'Jupiter', 'en' => 'Jupiter'], 'is_correct' => true]);
        Option::create(['question_id' => $q2->id, 'option_text' => ['hu' => 'Szaturnusz', 'en' => 'Saturn'], 'is_correct' => false]);
        Option::create(['question_id' => $q2->id, 'option_text' => ['hu' => 'Mars', 'en' => 'Mars'], 'is_correct' => false]);
        Option::create(['question_id' => $q2->id, 'option_text' => ['hu' => 'Föld', 'en' => 'Earth'], 'is_correct' => false]);

        // 4. Kérdés #3
        $q3 = Question::create([
            'category_id' => $science->id,
            'difficulty' => 'easy',
            'question_text' => ['hu' => 'Mi a víz vegyjele?', 'en' => 'What is the chemical formula of water?'],
            'is_approved' => true,
            'is_active' => true,
            'creator_id' => $userId
        ]);
        Option::create(['question_id' => $q3->id, 'option_text' => ['hu' => 'H2O', 'en' => 'H2O'], 'is_correct' => true]);
        Option::create(['question_id' => $q3->id, 'option_text' => ['hu' => 'CO2', 'en' => 'CO2'], 'is_correct' => false]);
        Option::create(['question_id' => $q3->id, 'option_text' => ['hu' => 'O2', 'en' => 'O2'], 'is_correct' => false]);
        Option::create(['question_id' => $q3->id, 'option_text' => ['hu' => 'NaCl', 'en' => 'NaCl'], 'is_correct' => false]);
    }
}
