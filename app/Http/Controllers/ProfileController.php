<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use App\Models\NotificationPreference;
use App\Models\Category;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->merge([
            'username' => mb_strtolower(trim((string) $request->input('username'))),
        ]);

        $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[\pL\pN_-]+$/u',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        // A szerepkör jogosultsági adat, azt a felhasználó saját
        // profilmentéssel nem módosíthatja.
        $user->update([
            // A legacy name mezőt az egyetlen nyilvános felhasználónévvel
            // szinkronban tartjuk a régi nézetek és kapcsolódások miatt.
            'name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
        ]);

        return back()->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $notificationPreferences = $user
            ->notificationPreferences()
            ->get()
            ->keyBy('event');

        // A profil fejlécében csak olyan összesítéseket mutatunk, amelyek a
        // felhasználó valóban rögzített válaszaiból számolhatók ki.
        $answerStats = DB::table('user_answers')
            ->where('user_id', $user->id)
            ->selectRaw('COUNT(*) as answers, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers, COUNT(DISTINCT quiz_id) as games')
            ->first();
        $answerCount = (int) ($answerStats->answers ?? 0);
        $correctCount = (int) ($answerStats->correct_answers ?? 0);

        return view('profile.show', [
            'user' => $user,
            'notificationEvents' => NotificationPreference::EVENTS,
            'notificationPreferences' => $notificationPreferences,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'profileStats' => [
                'answers' => $answerCount,
                'games' => (int) ($answerStats->games ?? 0),
                'accuracy' => $answerCount > 0 ? (int) round(($correctCount / $answerCount) * 100) : 0,
            ],
        ]);
    }

    public function updatePrivateDetails(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'country' => ['required', 'string', 'max:100'],
            'county' => ['required', 'string', 'max:100'],
            'favorite_category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'relationship_status' => ['required', Rule::in(['single', 'relationship', 'married'])],
            'children_count' => ['required', 'integer', 'min:0', 'max:30'],
        ]);

        $rewardGranted = DB::transaction(function () use ($request, $validated): bool {
            $user = $request->user()->newQuery()->lockForUpdate()->findOrFail($request->user()->id);
            $rewardGranted = $user->profile_details_rewarded_at === null;
            $user->fill($validated);

            if ($rewardGranted) {
                $user->points += 2000;
                $user->profile_details_rewarded_at = now();
            }

            $user->save();

            return $rewardGranted;
        });

        return back()->with('success', $rewardGranted
            ? 'A privát profiladatok mentve. Jóváírtunk 2 000 PT ajándékot!'
            : 'A privát profiladatok frissítve.');
    }

    /**
     * A bejelentkezett felhasználó tartós eredménykimutatása.
     */
    public function results(Request $request): View
    {
        $user = $request->user();
        $baseQuery = DB::table('user_answers')->where('user_id', $user->id);
        $totals = (clone $baseQuery)->selectRaw(
            'COUNT(*) as answers, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers, COUNT(DISTINCT quiz_id) as quiz_count'
        )->first();
        $answerCount = (int) ($totals->answers ?? 0);
        $correctAnswerCount = (int) ($totals->correct_answers ?? 0);
        $accuracy = $answerCount > 0 ? (int) round(($correctAnswerCount / $answerCount) * 100) : 0;

        $timezone = 'Europe/Budapest';
        $currentWeek = CarbonImmutable::now($timezone)->startOfWeek();
        $firstWeek = $currentWeek->subWeeks(11);
        $weeklyAnswers = (clone $baseQuery)
            ->where('created_at', '>=', $firstWeek->utc())
            ->orderBy('created_at')
            ->get(['created_at', 'is_correct'])
            ->groupBy(fn ($answer) => CarbonImmutable::parse($answer->created_at, 'UTC')->setTimezone($timezone)->startOfWeek()->toDateString());

        $weeklyResults = collect(range(0, 11))->map(function (int $offset) use ($firstWeek, $weeklyAnswers) {
            $weekStart = $firstWeek->addWeeks($offset);
            $answers = $weeklyAnswers->get($weekStart->toDateString(), collect());
            $count = $answers->count();
            $correct = $answers->where('is_correct', true)->count();

            return [
                'week_start' => $weekStart,
                'week_end' => $weekStart->endOfWeek(),
                'answers' => $count,
                'correct_answers' => $correct,
                'accuracy' => $count > 0 ? (int) round(($correct / $count) * 100) : 0,
            ];
        })->reverse()->values();

        $quizResults = DB::table('user_answers')
            ->join('quizzes', 'quizzes.id', '=', 'user_answers.quiz_id')
            ->where('user_answers.user_id', $user->id)
            ->selectRaw('quizzes.id, quizzes.slug, quizzes.title, COUNT(*) as answers, SUM(CASE WHEN user_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers, MAX(user_answers.created_at) as last_played_at')
            ->groupBy('quizzes.id', 'quizzes.slug', 'quizzes.title')
            ->orderByDesc('last_played_at')
            ->paginate(15);

        // Egy user_answers rekord egy felhasználó első, rögzített válasza
        // egy kérdésre. A saját játékot kizárjuk, mert az után nem jár
        // alkotói jutalom; minden fennmaradó rekord jelenleg +1 PT-t jelent.
        $creatorResults = DB::table('user_answers')
            ->join('quizzes', 'quizzes.id', '=', 'user_answers.quiz_id')
            ->where('quizzes.creator_id', $user->id)
            ->where('user_answers.user_id', '!=', $user->id)
            ->selectRaw('quizzes.id, quizzes.title, COUNT(*) as answered_questions, COUNT(DISTINCT user_answers.user_id) as players')
            ->groupBy('quizzes.id', 'quizzes.title')
            ->orderByDesc('answered_questions')
            ->get();
        $creatorAnsweredQuestions = (int) $creatorResults->sum('answered_questions');
        $creatorRewardPoints = $creatorAnsweredQuestions;

        return view('profile.results', compact(
            'user',
            'answerCount',
            'correctAnswerCount',
            'accuracy',
            'totals',
            'weeklyResults',
            'quizResults',
            'creatorResults',
            'creatorAnsweredQuestions',
            'creatorRewardPoints',
        ));
    }

    public function switchRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Közvetlen értékadás a tulajdonságra (ez megkerüli a fillable korlátokat is)
        $user->role = $request->input('role');

        // Explicit mentés az adatbázisba
        $user->save();

        return redirect()->back()->with('success', 'Szerepkör frissítve: ' . $user->role);
    }

    public function updateGameExperience(Request $request)
    {
        $validated = $request->validate([
            'time_travel_theme' => 'required|in:back_to_future,harry_potter',
        ]);

        $request->user()->update([
            'time_travel_theme' => $validated['time_travel_theme'],
        ]);

        return redirect()->back()->with('success', 'Játékélmény beállítások mentve.');
    }



    public function updatePassword(Request $request)
    {
        $hasPassword = filled($request->user()->getRawOriginal('password'));
        $validated = $request->validate([
            'current_password' => $hasPassword ? ['required', 'current_password'] : ['nullable'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
