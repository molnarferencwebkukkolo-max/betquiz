<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizManagementController extends Controller
{
    /**
     * Kvízek listázása (Kvízeim / Kvízek)
     */
    public function index()
    {
        $user = Auth::user();

        $query = Quiz::with(['category', 'creator'])->withCount('questions')->latest();

        // Ha sima user, csak a saját kvízeit látja
        if (!$user->isUseradmin()) {
            $query->where('creator_id', $user->id);
        }

        $quizzes = $query->paginate(12);

        return view('quizzes.index', compact('quizzes', 'user'));
    }

    /**
     * Egy konkrét Kvíz részletei és a benne lévő kérdések kilistázása statisztikával
     */
    public function show(Quiz $quiz)
    {
        $user = Auth::user();

        // Ellenőrizzük a jogosultságot
        if (!$user->isUseradmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez!');
        }

        // Betöltjük a Kvízhez tartozó kérdéseket az opcióikkal együtt
        $quiz->load(['category', 'creator', 'questions.options']);

        return view('quizzes.show', compact('quiz', 'user'));
    }

    /**
     * Kvíz publikálása vagy visszavonása
     */
    public function togglePublish(Quiz $quiz)
    {
        $user = Auth::user();

        // Jogosultság ellenőrzése
        if (!$user->isUseradmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez!');
        }

        $questionCount = $quiz->questions()->count();

        // Ha publikálni szeretné, de nincs meg a 100 kérdés
        if ($quiz->status !== 'approved' && $questionCount < 100) {
            return back()->withErrors(['publish' => 'A kvíz publikálásához legalább 100 kérdés feltöltése szükséges! (Jelenleg: ' . $questionCount . ' db)']);
        }

        // Státusz váltása az adatbázis által elfogadott értékekre ('approved' és 'pending')
        if ($quiz->status === 'approved') {
            $quiz->status = 'pending'; // Visszavonás
            $message = '🔒 Kvíz sikeresen visszavonva (szerkesztés alatt)!';
        } else {
            $quiz->status = 'approved'; // Publikálás
            $message = '🚀 Kvíz sikeresen publikálva! Mostantól elérhető a játékosok számára.';
        }

        $quiz->save();

        return back()->with('success', $message);
    }
}
