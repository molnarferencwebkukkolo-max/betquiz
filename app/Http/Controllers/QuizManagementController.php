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
            // Adminisztrátornak az összes kvíz kell
            $quizzes = Quiz::with(['creator', 'category'])
                ->withCount('questions')
                ->latest()
                ->paginate(15);
        } else {
            // Sima felhasználónak csak a saját kvízei kellenek
            $quizzes = Quiz::where('creator_id', $user->id)
                ->with('category')
                ->withCount('questions')
                ->latest()
                ->paginate(15);
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
     * Új kvíz mentése az adatbázisba (Első 10 kérdéssel -> Pending)
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
            'status' => 'pending', // Alapértelmezetten adminisztrátori bírálatra vár!
        ]);

        return redirect()->route('my-quizzes.show', $quiz->id)
            ->with('success', 'Kvíz koncepció sikeresen benyújtva! Adminisztrátori jóváhagyás után kezdheted meg a további kérdések feltöltését.');
    }

    /**
     * Egy konkrét kvíz részletei (CSV feltöltés, kérdések listája, Admin bírálati panel)
     */
    public function show(Quiz $myQuiz) // <-- $quiz helyett $myQuiz!
    {
        $user = Auth::user();

        // Ellenőrizzük a jogosultságot
        if (!$user->isUseradmin() && !$user->isHostadmin() && $myQuiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez!');
        }

        // Betöltjük a Kvízhez tartozó relációkat
        $myQuiz->load(['category', 'creator', 'questions.options']);

        // 'quiz' néven adjuk át a $myQuiz-t a Blade sablonnak!
        return view('creator.show', [
            'quiz' => $myQuiz,
            'user' => $user
        ]);
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

        return redirect()->route('my-my-quizzes.show', $quiz->id)
            ->with('success', 'Kvíz sikeresen frissítve!');
    }

    /**
     * Kvíz törlése
     */
    public function destroy(Quiz $myQuiz)
    {
        $user = Auth::user();

        // 🔒 Jogosultság ellenőrzése
        if (!$user->isUseradmin() && !$user->isHostadmin() && $myQuiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod törölni ezt a kvízt!');
        }

        // 💣 Kapcsolódó kérdések törlése (ha nincs cascade törlés beállítva a DB-ben)
        $myQuiz->questions()->delete();

        // 💣 Kvíz törlése
        $myQuiz->delete();

        return redirect()->route('my-quizzes.index')->with('success', 'A kvíz sikeresen törölve lett!');
    }

    /**
     * Admin: Kvíz jóváhagyása (Engedélyezi a kérdések gyűjtését 100-ig)
     */
    public function approveQuiz(Quiz $quiz)
    {
        $user = auth()->user();

        if (!$user->isUseradmin() && !$user->isHostadmin()) {
            abort(403, 'Csak adminisztrátor hagyhatja jóvá a kvízt.');
        }

        $quiz->update([
            'status' => 'approved',
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Kvíz koncepció jóváhagyva! A készítő mostantól feltöltheti a maradék kérdéseket (100 db-ig).');
    }

    /**
     * Admin: Kvíz elutasítása
     */
    public function rejectQuiz(Quiz $quiz)
    {
        $user = auth()->user();

        if (!$user->isUseradmin() && !$user->isHostadmin()) {
            abort(403, 'Csak adminisztrátor utasíthatja el a kvízt.');
        }

        $quiz->update([
            'status' => 'rejected'
        ]);

        return back()->with('error', 'Kvíz koncepció elutasítva!');
    }

    /**
     * Segéd-metódus: Ellenőrzi, hogy elértük-e a 100 kérdést, és ha igen, automatikusan publikálja (`published`)
     */
    public static function checkAndPublish(Quiz $quiz)
    {
        $questionCount = $quiz->questions()->count();

        // Ha eléri a 100 kérdést és jóvá volt hagyva (approved), élesítjük!
        if ($questionCount >= 100 && $quiz->status === 'approved') {
            $quiz->update([
                'status' => 'published'
            ]);
            return true;
        }

        return false;
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
