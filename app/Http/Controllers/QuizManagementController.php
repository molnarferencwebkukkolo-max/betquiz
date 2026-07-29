<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            $quizzes = Quiz::with(['creator', 'category', 'tags'])
                ->withCount('questions')
                ->withSum('questions as total_answers', 'times_answered')
                ->withSum('questions as total_correct', 'times_correct')
                ->latest()
                ->paginate(15);
        } else {
            // Sima felhasználónak csak a saját kvízei kellenek
            $quizzes = Quiz::where('creator_id', $user->id)
                ->with(['category', 'tags'])
                ->withCount('questions')
                ->withSum('questions as total_answers', 'times_answered')
                ->withSum('questions as total_correct', 'times_correct')
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
            'seo_title' => $validated['title'],
            'seo_description' => Str::limit(strip_tags((string) $validated['description']), 160, ''),
            'category_id' => $validated['category_id'],
            'creator_id' => auth()->id(),
            'status' => 'pending', // Alapértelmezetten adminisztrátori bírálatra vár!
        ]);

        return redirect()->route('my-quizzes.show', $quiz)
            ->with('success', 'Kvíz koncepció sikeresen benyújtva! Adminisztrátori jóváhagyás után kezdheted meg a további kérdések feltöltését.');
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

        // Betöltjük a Kvízhez tartozó relációkat
        $quiz->load(['category', 'creator', 'questions.options', 'tags']);
        $quiz->loadSum('questions as total_answers', 'times_answered');
        $quiz->loadSum('questions as total_correct', 'times_correct');

        return view('creator.show', [
            'quiz' => $quiz,
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
        $allTags = Tag::query()->orderBy('name')->pluck('name');
        $quiz->load('tags');

        return view('creator.edit', compact('quiz', 'categories', 'allTags'));
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
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:160',
            'tags' => 'nullable|string|max:1000',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $previousDefaultSeoTitle = $quiz->title;
        $previousDefaultSeoDescription = Str::limit(strip_tags((string) $quiz->description), 160, '');

        $quizData = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
        ];

        if ($user->isUseradmin() || $user->isHostadmin()) {
            $quizData['seo_title'] = $validated['seo_title'] ?: $validated['title'];
            $quizData['seo_description'] = $validated['seo_description']
                ?: Str::limit(strip_tags((string) $validated['description']), 160, '');
        } else {
            if (!$quiz->seo_title || $quiz->seo_title === $previousDefaultSeoTitle) {
                $quizData['seo_title'] = $validated['title'];
            }

            if (!$quiz->seo_description || $quiz->seo_description === $previousDefaultSeoDescription) {
                $quizData['seo_description'] = Str::limit(strip_tags((string) $validated['description']), 160, '');
            }
        }

        if ($request->hasFile('cover_image')) {
            if ($quiz->cover_image) {
                Storage::disk('public')->delete($quiz->cover_image);
            }

            $quizData['cover_image'] = $request->file('cover_image')->store('quiz_covers', 'public');
        }

        $quiz->update($quizData);

        if ($user->isUseradmin() || $user->isHostadmin()) {
            $this->syncTags($quiz, $validated['tags'] ?? '');
        }

        return redirect()->route('my-quizzes.show', $quiz)
            ->with('success', 'Kvíz sikeresen frissítve!');
    }

    /**
     * Kvíz törlése
     */
    public function destroy(Quiz $quiz)
    {
        $user = Auth::user();

        // 🔒 Jogosultság ellenőrzése
        if (!$user->isUseradmin() && !$user->isHostadmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod törölni ezt a kvízt!');
        }

        // 💣 Kapcsolódó kérdések törlése (ha nincs cascade törlés beállítva a DB-ben)
        $quiz->questions()->delete();

        // 💣 Kvíz törlése
        $quiz->delete();

        return redirect()->route('my-quizzes.index')->with('success', 'A kvíz sikeresen törölve lett!');
    }

    /**
     * Admin altal megadott tag lista letrehozasa es osszekapcsolasa a kvizzel.
     */
    private function syncTags(Quiz $quiz, string $tagList): void
    {
        $tagNames = collect(preg_split('/[,;]/', $tagList))
            ->map(fn($tag) => trim($tag))
            ->filter()
            ->unique(fn($tag) => Str::lower($tag))
            ->values();

        $tagIds = $tagNames
            ->map(fn($name) => Tag::firstOrCreate(['name' => $name])->id)
            ->all();

        $quiz->tags()->sync($tagIds);
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
