<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizManagementController extends Controller
{
    /**
     * Kvízek listázása (KVÍZEIM / Saját kvízek + Admin bíráló lista)
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->isUseradmin() || $user->isHostadmin()) {
            // Adminisztrátornak az összes kvíz kell (jóváhagyás/elutasítás miatt)
            $quizzes = Quiz::with(['creator', 'category'])->latest()->get();
        } else {
            // Sima felhasználónak csak a saját kvízei kellenek
            $quizzes = Quiz::where('creator_id', $user->id)
                ->with('category')
                ->latest()
                ->get();
        }

        return view('creator.index', compact('quizzes'));
    }

    /**
     * Új kvíz létrehozási űrlap
     */
    public function create()
    {
        $categories = Category::all();

        return view('creator.create', compact('categories'));
    }

    /**
     * Új kvíz mentése az adatbázisba
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        $quiz = Quiz::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'creator_id' => auth()->id(),
            'status' => 'pending', // Alapértelmezetten elbírálásra vár
        ]);

        return redirect()->route('my-quizzes.show', $quiz->id)
            ->with('success', 'Kvíz sikeresen létrehozva! Tölts fel kérdéseket vagy importálj CSV-ből.');
    }

    /**
     * Egy konkrét kvíz részletei (CSV feltöltés, kérdések listája, Admin bírálati panel)
     */
    public function show(Quiz $quiz)
    {
        $user = Auth::user();

        // Ellenőrizzük a jogosultságot
        if (!$user->isUseradmin() && !$user->isHostadmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez!');
        }

        // Betöltjük a Kvízhez tartozó kérdéseket az opcióikkal együtt
        $quiz->load(['category', 'creator', 'questions.options']);

        return view('creator.show', compact('quiz', 'user'));
    }

    /**
     * Kvíz szerkesztő űrlap megjelenítése
     */
    public function edit(Quiz $quiz)
    {
        $user = Auth::user();

        if (!$user->isUseradmin() && !$user->isHostadmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez!');
        }

        $categories = Category::all();

        return view('creator.edit', compact('quiz', 'categories'));
    }

    /**
     * Kvíz adatainak frissítése
     */
    public function update(Request $request, Quiz $quiz)
    {
        $user = Auth::user();

        if (!$user->isUseradmin() && !$user->isHostadmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez!');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        $quiz->update($validated);

        return redirect()->route('my-quizzes.show', $quiz->id)
            ->with('success', 'Kvíz sikeresen frissítve!');
    }

    /**
     * Kvíz törlése
     */
    public function destroy(Quiz $quiz)
    {
        $user = Auth::user();

        if (!$user->isUseradmin() && !$user->isHostadmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez!');
        }

        $quiz->delete();

        return redirect()->route('my-quizzes.index')
            ->with('success', 'Kvíz sikeresen törölve!');
    }

    /**
     * Kvíz publikálása / beküldése jóváhagyásra
     */
    public function togglePublish(Quiz $quiz)
    {
        $user = auth()->user();

        if ($user->id !== $quiz->creator_id && !$user->isUseradmin() && !$user->isHostadmin()) {
            abort(403, 'Nincs jogosultságod a kvíz státuszának módosításához.');
        }

        if ($user->isUseradmin() || $user->isHostadmin()) {
            $newStatus = ($quiz->status === 'approved') ? 'pending' : 'approved';
            $message = ($newStatus === 'approved')
                ? 'A kvíz sikeresen jóváhagyva és publikálva!'
                : 'A kvíz visszatéve a várakozási sorba.';
        } else {
            if ($quiz->status === 'approved') {
                return redirect()->back()->with('error', 'A már jóváhagyott kvízt csak adminisztrátor vonhatja vissza.');
            }

            $newStatus = 'pending';
            $message = 'A kvíz sikeresen beküldve az adminisztrátornak jóváhagyásra!';
        }

        $quiz->update([
            'status' => $newStatus,
            'rejection_reason' => null,
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

    /**
     * Admin: Kvíz elutasítása
     */
    public function rejectQuiz(Quiz $quiz)
    {
        $quiz->update(['status' => 'rejected']);
        return back()->with('error', 'Kvíz elutasítva!');
    }

    /**
     * KIZÁRÓLAG ADMIN / HOSTADMIN: Teljes kvíz átrendelése/átadása másik felhasználónak
     */
    public function transferOwnership(Request $request, Quiz $quiz)
    {
        $user = auth()->user();

        if (!$user->isUseradmin() && !$user->isHostadmin()) {
            abort(403, 'Kizárólag adminisztrátor adhat át kvízt más felhasználónak.');
        }

        $request->validate([
            'new_owner_id' => 'required|exists:users,id',
        ]);

        $quiz->update([
            'creator_id' => $request->new_owner_id,
        ]);

        return redirect()->back()->with('success', 'A kvíz tulajdonjoga sikeresen átadva az új felhasználónak!');
    }
}
