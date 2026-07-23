<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Category;


class QuizController extends Controller
{
    /**
     * Műszerfal nézet (Főoldal - /dashboard)
     */


    public function dashboard()
    {
        $user = Auth::user();
        $userId = $user->id;

        // Alaplekérdezés: csak éles, jóváhagyott kvízek, amiket NEM a bejelentkezett user készített
        $baseQuery = Quiz::where('status', 'approved')
            ->where('creator_id', '!=', $userId)
            ->with(['category', 'creator'])
            ->withCount('questions');

        // 1. KIEMELT KVÍZEK (Admin jelölte meg - pl. is_featured = true, vagy status = 'featured')
        // (Ha még nincs is_featured meződ, az utolsó approved-okat hozza)
        $featuredQuizzes = (clone $baseQuery)
            ->when(Schema::hasColumn('quizzes', 'is_featured'), fn($q) => $q->where('is_featured', true))
            ->latest()
            ->get();

        // 2. LEGÚJABB KVÍZEK
        $latestQuizzes = (clone $baseQuery)
            ->latest()
            ->take(10)
            ->get();

        // 3. KEDVENC KVÍZEK (A user által kedvencnek jelöltek)
        // Megjegyzés: Feltételezi a favorites kapcsolódó táblát a User modelben (favorites())
        $favoriteQuizzes = method_exists($user, 'favorites')
            ? $user->favorites()->with(['category', 'creator'])->withCount('questions')->get()
            : collect();

        // 4. LEGNEHEZEBB KVÍZEK (Statisztikailag a legtöbb rossz válasz / rossz válaszok aránya)
        // A questions tábla times_answered és times_correct mezői alapján kiszámítva
        $hardestQuizzes = (clone $baseQuery)
            ->withSum('questions as total_answers', 'times_answered')
            ->withSum('questions as total_correct', 'times_correct')
            ->get()
            ->filter(fn($quiz) => $quiz->total_answers > 0) // Csak amivel már játszottak
            ->sortByDesc(function ($quiz) {
                $wrongAnswers = $quiz->total_answers - $quiz->total_correct;
                return $wrongAnswers / $quiz->total_answers; // Rossz válaszok aránya
            })
            ->sortBy(fn($quiz) => $quiz->created_at) // Egyenlőség esetén a régebbi előre
            ->take(10)
            ->values();

        // 5. EZZEL MÉG NEM JÁTSZOTTÁL (Biztonságos ellenőrzés)
        $playedQuizIds = [];

// Ha létezik a quiz_sessions vagy user_answers tábla, kigyűjtjük a játszott ID-kat
        if (Schema::hasTable('quiz_sessions')) {
            $playedQuizIds = DB::table('quiz_sessions')->where('user_id', $userId)->pluck('quiz_id')->toArray();
        } elseif (Schema::hasTable('user_answers')) {
            // Alternatív lehetőség: Ha a válaszok táblából tudjuk kiszűrni
            $playedQuizIds = DB::table('user_answers')->where('user_id', $userId)->pluck('quiz_id')->toArray();
        }

        $unplayedQuizzes = (clone $baseQuery)
            ->when(!empty($playedQuizIds), fn($q) => $q->whereNotIn('id', $playedQuizIds))
            ->inRandomOrder()
            ->take(10)
            ->get();

        // 6. KATEGÓRIA FAVORIT (Beállított kedvenc kategóriából 10 random, ha nincs beállítva: full random 10)
        $preferredCategoryId = $user->favorite_category_id ?? null;
        $categoryFavoriteQuizzes = (clone $baseQuery)
            ->when($preferredCategoryId, fn($q) => $q->where('category_id', $preferredCategoryId))
            ->inRandomOrder()
            ->take(10)
            ->get();

        // 7. MÁSOK SZERINT NÉPSZERŰ (A 10 kvíz, amit a legtöbbször töltöttek ki / legtöbb válasz érkezett rá)
        $popularQuizzes = (clone $baseQuery)
            ->withSum('questions as total_answers', 'times_answered')
            ->orderByDesc('total_answers')
            ->take(10)
            ->get();

        // Saját kvízek (Külön a felhasználó alkotói áttekintéséhez, ha szükséges)
        $myQuizzes = Quiz::where('creator_id', $userId)->with('category')->latest()->get();

        return view('dashboard', compact(
            'user',
            'featuredQuizzes',
            'latestQuizzes',
            'favoriteQuizzes',
            'hardestQuizzes',
            'unplayedQuizzes',
            'categoryFavoriteQuizzes',
            'popularQuizzes',
            'myQuizzes'
        ));
    }

    /**
     * Katalógus nézet (JÁTÉK menüpont - /quizzes)
     */
    public function showBetForm(Request $request)
    {
        $user = Auth::user();

        // 1. Kategóriák lekérése a szűrőhöz
        $categories = Category::all();

        // 2. Kvízek lekérése (csak a jóváhagyottak)
        $quizzesQuery = Quiz::where('status', 'approved')
            ->where('creator_id', '!=', $user->id)
            ->with(['category', 'creator'])
            ->withCount('questions');

        // Keresés/Szűrés (ha van kategória kiválasztva a kérésben)
        if ($request->has('category_id') && $request->category_id) {
            $quizzesQuery->where('category_id', $request->category_id);
        }

        $quizzes = $quizzesQuery->latest()->paginate(12)->withQueryString();

        // 3. Átadjuk a $categories változót is a compact()-ban!
        return view('play.catalog', compact('quizzes', 'categories', 'user'));
    }

    /**
     * Tétbeállító képernyő (/quiz/setup/{quiz})
     */
    public function setupQuizPlay(Quiz $quiz)
    {
        if ($quiz->status !== 'approved') {
            return redirect()->route('dashboard')->with('error', 'Ez a kvíz jelenleg nem elérhető.');
        }

        return view('play.setup', compact('quiz'));
    }
}
