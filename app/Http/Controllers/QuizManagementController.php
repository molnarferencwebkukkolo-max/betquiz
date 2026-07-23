<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;

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


        return view('quiz.show', compact('quiz', 'user'));
    }

    /**
     * Kvíz publikálása / beküldése jóváhagyásra
     */
    public function togglePublish(Quiz $quiz)
    {
        $user = auth()->user();

        // 1. Ellenőrizzük, hogy a felhasználó a tulajdonos vagy admin-e
        if ($user->id !== $quiz->creator_id && $user->role !== 'admin') {
            abort(403, 'Nincs jogosultságod a kvíz státuszának módosításához.');
        }

        // 2. SZABÁLY ALAPJÁN TÖRTÉNŐ STÁTUSZVÁLTÁS:
        if ($user->role === 'admin') {
            // AZ ADMIN közvetlenül jóváhagyhatja / publikálhatja
            $newStatus = ($quiz->status === 'approved') ? 'pending' : 'approved';
            $message = ($newStatus === 'approved')
                ? 'A kvíz sikeresen jóváhagyva és publikálva!'
                : 'A kvíz visszatéve a várakozási sorba.';
        } else {
            // A TULAJDONOS csak jóváhagyásra küldheti be (pending)
            // Közvetlenül approved-ba NEM teheti!
            if ($quiz->status === 'approved') {
                return redirect()->back()->with('error', 'A már jóváhagyott kvízt csak adminisztrátor vonhatja vissza.');
            }

            $newStatus = 'pending';
            $message = 'A kvíz sikeresen beküldve az adminisztrátornak jóváhagyásra!';
        }

        $quiz->update([
            'status' => $newStatus,
            'rejection_reason' => null, // Státuszváltáskor töröljük az esetleges korábbi elutasítási indokot
        ]);

        return redirect()->back()->with('success', $message);
    }

    /**
     * Admin: Kvíz jóváhagyása
     */
    public function approveQuiz(Quiz $quiz)
    {
        $quiz->update(['status' => 'approved']);
        return back()->with('success', 'Kvíz elfogadva!');
    }

    public function rejectQuiz(Quiz $quiz)
    {
        $quiz->update(['status' => 'rejected']);
        return back()->with('error', 'Kvíz elutasítva!');
    }


    /**
     * Kvíz szerkesztő űrlap megjelenítése
     */
    public function edit(Quiz $quiz)
    {
        $categories = Category::all();

        return view('quizzes.edit', compact('quiz', 'categories'));
        // Ha egyes számú mappában van az szerkesztő nézeted, akkor: view('quiz.edit', ...)
    }

    /**
     * Kvíz adatinak frissítése
     */
    public function update(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        $quiz->update($validated);

        return redirect()->route('quizzes.show', $quiz->id)
            ->with('success', 'Kvíz sikeresen frissítve!');
    }

    /**
     * KIZÁRÓLAG ADMIN / HOSTADMIN: Teljes kvíz átrendelése/átadása másik felhasználónak
     */
    public function transferOwnership(Request $request, Quiz $quiz)
    {
        $user = auth()->user();

        // Szigorú Hostadmin / Admin jogosultság ellenőrzése
        if ($user->role !== 'admin') {
            abort(403, 'Kizárólag adminisztrátor adhat át kvízt más felhasználónak.');
        }

        $request->validate([
            'new_owner_id' => 'required|exists:users,id',
        ]);

        // Kvíz tulajdonosának módosítása 1/1-ben
        $quiz->update([
            'creator_id' => $request->new_owner_id,
        ]);

        return redirect()->back()->with('success', 'A kvíz tulajdonjoga sikeresen átadva az új felhasználónak!');
    }
}
