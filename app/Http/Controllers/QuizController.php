<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Option;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    /**
     * Játékos Dashboard / Főoldal belépés után
     */
    public function dashboard()
    {
        return view('dashboard');
    }

    /**
     * Kvíz indítása (3 kérdés betöltése)
     */
    public function start()
    {
        // 3 véletlenszerűen kiválasztott, aktív és jóváhagyott kérdés
        $questions = Question::with(['category', 'options'])
            ->where('is_active', true)
            ->where('is_approved', true)
            ->inRandomOrder()
            ->take(3)
            ->get();

        return view('quiz.play', compact('questions'));
    }

    /**
     * Válasz ellenőrzése AJAX-szal
     */
    public function checkAnswer(Request $request)
    {
        $request->validate([
            'option_id' => 'required|exists:options,id'
        ]);

        $option = Option::find($request->option_id);

        $correctOption = Option::where('question_id', $option->question_id)
            ->where('is_correct', true)
            ->first();

        return response()->json([
            'is_correct' => $option->is_correct,
            'correct_option_id' => $correctOption ? $correctOption->id : null,
        ]);
    }
}
