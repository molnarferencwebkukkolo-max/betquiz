<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\QuestionReportedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuestionReportController extends Controller
{
    public function store(Request $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:15', 'max:1500']]);
        $user = $request->user();
        $game = $request->session()->get('game_session');

        abort_unless($question->quiz_id === $quiz->id, 404);
        abort_unless($game && (int) ($game['quiz_id'] ?? 0) === $quiz->id
            && (int) ($game['current_question_id'] ?? 0) === $question->id
            && ($game['status'] ?? null) === 'active', 409, 'Csak az aktuális kérdés jelezhető hibásnak.');

        if ($user->questionReportStats()['restricted']) {
            return back()->with('error', 'A hibajelzési lehetőségedet korlátoztuk, mert a lezárt jelzéseid több mint 30%-a nem bizonyult valósnak.');
        }

        if ($user->questionReports()->where('question_id', $question->id)->where('status', QuestionReport::STATUS_PENDING)->exists()) {
            return back()->with('error', 'Ezt a kérdést már jelezted, a kivizsgálás folyamatban van.');
        }

        $report = DB::transaction(function () use ($question, $user, $validated) {
            $question->newQuery()->whereKey($question->id)->lockForUpdate()->firstOrFail()->update(['is_active' => false]);

            return QuestionReport::create([
                'question_id' => $question->id,
                'reporter_id' => $user->id,
                'reason' => $validated['reason'],
            ]);
        });

        $report->load('question.quiz');
        $recipients = User::query()
            ->where('is_active', true)->where('is_banned', false)
            ->where(fn ($query) => $query->where('id', $quiz->creator_id)->orWhereIn('role', ['useradmin', 'hostadmin']))
            ->get()->unique('id');
        $recipients->each(fn (User $recipient) => $recipient->notify(new QuestionReportedNotification($report)));

        // A jelentett kérdést átugorjuk, de nem számítjuk helyes vagy hibás válasznak.
        $game['answered_ids'][] = $question->id;
        $game['answered_ids'] = array_values(array_unique($game['answered_ids']));
        unset($game['current_question_id'], $game['helper_results']);
        $request->session()->put('game_session', $game);

        return redirect()->route('quiz.play.screen', $quiz)->with('success', 'Köszönjük a jelzést! A kérdést inaktiváltuk és értesítettük a kezelőket.');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $reports = QuestionReport::query()
            ->with(['question.quiz.creator', 'reporter', 'resolver'])
            ->when(! $user->isUseradmin(), fn ($query) => $query->whereHas('question.quiz', fn ($quiz) => $quiz->where('creator_id', $user->id)))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()->paginate(20);

        return view('question-reports.index', compact('reports'));
    }

    public function resolve(Request $request, QuestionReport $questionReport): RedirectResponse
    {
        $report = $questionReport->load('question.quiz');
        $user = $request->user();
        abort_unless($user->isUseradmin() || $report->question->quiz->creator_id === $user->id, 403);
        abort_unless($report->status === QuestionReport::STATUS_PENDING, 409);

        $validated = $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
            'resolution_note' => ['required', 'string', 'min:10', 'max:1500'],
        ]);

        if ($validated['decision'] === QuestionReport::STATUS_ACCEPTED
            && ! $report->question->updated_at->isAfter($report->created_at)) {
            throw ValidationException::withMessages([
                'decision' => 'Elfogadott hibajelzés csak akkor zárható le, ha előtte elmented a javított kérdést.',
            ]);
        }

        DB::transaction(function () use ($report, $user, $validated) {
            $report->update([
                'status' => $validated['decision'],
                'resolved_by' => $user->id,
                'resolution_note' => $validated['resolution_note'],
                'resolved_at' => now(),
            ]);
            // Ugyanarra a kérdésre több játékos is jelezhet hibát. A
            // kérdés csak az utolsó függőben lévő ügy lezárása után térhet vissza.
            $hasPendingReport = $report->question->reports()
                ->where('status', QuestionReport::STATUS_PENDING)
                ->exists();
            $report->question->update(['is_active' => ! $hasPendingReport]);
        });

        $reactivated = $report->question->fresh()->is_active;
        $message = $validated['decision'] === 'accepted'
            ? 'A hibát elfogadtuk, a javítást rögzítettük.'
            : 'A jelzést nem valós (FAKE) bejelentésként lezártuk.';

        $message .= $reactivated
            ? ' A kérdést újraaktiváltuk.'
            : ' A kérdés egy másik függő hibajelzés miatt inaktív marad.';

        return back()->with('success', $message);
    }
}
