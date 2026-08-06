<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuizManagementController extends Controller
{
    /**
     * Kvízek listázása (KVÍZEIM / Saját kvízek + Admin bíráló lista)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->isUseradmin() || $user->isHostadmin();
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $categoryId = $request->integer('category_id');
        $allowedStatuses = ['pending', 'approved', 'rejected'];

        $quizzesQuery = Quiz::query()
            ->with(['creator', 'category', 'tags'])
            ->withCount('questions')
            ->withSum('questions as total_answers', 'times_answered')
            ->withSum('questions as total_correct', 'times_correct');

        if (!$isAdmin) {
            $quizzesQuery->where('creator_id', $user->id);
        }

        if ($search !== '') {
            $quizzesQuery->where(function ($query) use ($search, $isAdmin) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'like', "%{$search}%"));

                if ($isAdmin) {
                    $query->orWhereHas('creator', function ($creatorQuery) use ($search) {
                        $creatorQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            });
        }

        if (in_array($status, $allowedStatuses, true)) {
            $quizzesQuery->where('status', $status);
        } else {
            $status = '';
        }

        if ($categoryId > 0) {
            $quizzesQuery->where('category_id', $categoryId);
        }

        if ($isAdmin && in_array($request->query('view'), ['cards', 'table'], true)) {
            session()->put('quiz_management_view', $request->query('view'));
        }

        $viewMode = $isAdmin ? session('quiz_management_view', 'cards') : 'cards';
        $quizzes = $quizzesQuery->latest()->paginate(15)->withQueryString();
        $categories = Category::query()->orderBy('name')->get();
        $owners = $isAdmin
            ? User::query()->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('creator.index', compact(
            'quizzes',
            'categories',
            'isAdmin',
            'viewMode',
            'search',
            'status',
            'categoryId',
            'owners'
        ));
    }

    /**
     * Admin tömeges kvízműveletek.
     */
    public function bulkUpdate(Request $request)
    {
        $user = Auth::user();

        if (!$user->isUseradmin() && !$user->isHostadmin()) {
            abort(403, 'Csak adminisztrátor módosíthat egyszerre több kvízt.');
        }

        $validated = $request->validate([
            'quiz_ids' => 'required|array|min:1',
            'quiz_ids.*' => 'integer|exists:quizzes,id',
            'bulk_action' => 'required|in:approve,reject,make_public,make_private,change_owner',
            'owner_id' => 'nullable|required_if:bulk_action,change_owner|exists:users,id',
        ]);

        $quizIds = array_values(array_unique(array_map('intval', $validated['quiz_ids'])));
        $changes = match ($validated['bulk_action']) {
            'approve' => ['status' => 'approved'],
            'reject' => ['status' => 'rejected', 'is_public' => false],
            'make_public' => ['is_public' => true],
            'make_private' => ['is_public' => false],
            'change_owner' => ['creator_id' => (int) $validated['owner_id']],
        };

        // Publikussá csak jóváhagyott kvíz tehető.
        if ($validated['bulk_action'] === 'make_public') {
            $notApprovedCount = Quiz::query()
                ->whereIn('id', $quizIds)
                ->where('status', '!=', 'approved')
                ->count();

            if ($notApprovedCount > 0) {
                return back()->withErrors([
                    'bulk_action' => 'Publikussá csak jóváhagyott kvízek tehetők.',
                ]);
            }

            $incompleteCount = Quiz::query()
                ->whereIn('id', $quizIds)
                ->whereHas('questions', null, '<', 100)
                ->count();

            if ($incompleteCount > 0) {
                return back()->withErrors([
                    'bulk_action' => 'Publikussá csak legalább 100 kérdést tartalmazó kvízek tehetők.',
                ]);
            }
        }

        $updatedCount = DB::transaction(
            fn () => Quiz::query()->whereIn('id', $quizIds)->update($changes)
        );

        return back()->with('success', "{$updatedCount} kvíz tömeges módosítása elkészült.");
    }

    /**
     * Könnyű, szerveroldali kvízkereső admin autocomplete mezőkhöz.
     */
    public function search(Request $request)
    {
        $user = Auth::user();

        if (!$user->isUseradmin() && !$user->isHostadmin()) {
            abort(403);
        }

        $search = trim((string) $request->query('q', ''));

        $quizzes = Quiz::query()
            ->with('creator:id,name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('title', 'like', "%{$search}%")
                        ->when(is_numeric($search), fn ($idQuery) => $idQuery->orWhere('id', (int) $search))
                        ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                            $creatorQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'creator_id']);

        return response()->json($quizzes->map(fn (Quiz $quiz) => [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'creator' => $quiz->creator?->name,
        ]));
    }

    /**
     * Pont- és statisztikamentes admin próbajáték.
     */
    public function preview(Quiz $quiz)
    {
        $user = Auth::user();

        if (!$user->isUseradmin() && !$user->isHostadmin()) {
            abort(403, 'Csak adminisztrátor indíthat próbajátékot.');
        }

        $quiz->load(['creator', 'category']);
        $questions = $quiz->questions()
            ->with('options')
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        return view('creator.preview', compact('quiz', 'questions'));
    }

    /**
     * Új kvíz létrehozási űrlap
     */
    public function create()
    {
        $categories = Category::query()->where('is_active', true)->get();

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
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
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

        // A kvíz jelenlegi kategóriája inaktiválás után is szerkeszthető
        // marad, de másik inaktív kategóriát nem lehet kiválasztani.
        $categories = Category::query()
            ->where('is_active', true)
            ->orWhereKey($quiz->category_id)
            ->get();
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

        $coverImage = $request->file('cover_image');

        // Laravel's generic validation message hides PHP's actual upload error.
        // Keep the diagnostic in the log and show a useful message to the user.
        if ($coverImage && !$coverImage->isValid()) {
            Log::warning('Quiz cover image upload failed before validation.', [
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'upload_error_code' => $coverImage->getError(),
                'upload_error_message' => $coverImage->getErrorMessage(),
            ]);

            throw ValidationException::withMessages([
                'cover_image' => 'A borítókép feltöltése sikertelen: '.$coverImage->getErrorMessage(),
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->orWhere('id', $quiz->category_id)
                ),
            ],
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

        // A jóváhagyás és a publikusság külön mező; 100 kérdésnél publikussá tesszük.
        if ($questionCount >= 100 && $quiz->status === 'approved') {
            $quiz->update([
                'is_public' => true,
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
