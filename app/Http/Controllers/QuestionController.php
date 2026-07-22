<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\Option;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    /**
     * Kérdések listázása
     */
    public function index()
    {
        $user = Auth::user();

        $query = Question::with(['quiz', 'category', 'options', 'creator'])->latest();

        if (!$user->isUseradmin()) {
            $query->where('creator_id', $user->id);
        }

        $questions = $query->paginate(15);

        return view('questions.index', compact('questions', 'user'));
    }

    /**
     * Kérdés létrehozása egy KONKRÉT Kvízen belül
     */
    public function createForQuiz(Quiz $quiz)
    {
        $this->authorizeQuizAccess($quiz);

        return view('questions.create_for_quiz', compact('quiz'));
    }

    /**
     * Kérdés mentése a konkrét Kvíz alá
     */
    public function storeForQuiz(Request $request, Quiz $quiz)
    {
        $this->authorizeQuizAccess($quiz);

        $request->validate([
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'nullable|string',
            'question_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'options' => 'required|array|min:4|max:4',
            'options.*.text' => 'nullable|string',
            'options.*.image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'correct_option' => 'required|integer|in:0,1,2,3',
        ]);

        if (!$request->filled('question_text') && !$request->hasFile('question_image')) {
            return back()->withErrors(['question_text' => 'A kérdésnek tartalmaznia kell legalább szöveget vagy képet!'])->withInput();
        }

        $questionImagePath = null;
        if ($request->hasFile('question_image')) {
            $questionImagePath = $request->file('question_image')->store('questions', 'public');
        }

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'category_id' => $quiz->category_id,
            'difficulty' => $request->difficulty,
            'question_text' => $request->filled('question_text') ? ['hu' => $request->question_text] : null,
            'image_path' => $questionImagePath,
            'is_approved' => true,
            'is_active' => true,
            'creator_id' => Auth::id(),
        ]);

        foreach ($request->options as $index => $optData) {
            $optImagePath = null;
            if (isset($optData['image']) && $request->hasFile("options.{$index}.image")) {
                $optImagePath = $request->file("options.{$index}.image")->store('options', 'public');
            }

            Option::create([
                'question_id' => $question->id,
                'option_text' => !empty($optData['text']) ? ['hu' => $optData['text']] : null,
                'image_path' => $optImagePath,
                'is_correct' => ($index == $request->correct_option),
            ]);
        }

        return redirect()->route('quizzes.show', $quiz->id)->with('success', '🎯 Kérdés sikeresen hozzáadva a kvízhez!');
    }

    /**
     * 🎯 CSV / TXT Importálás KONKRÉT Kvízhez (A KERESETT METÓDUS)
     */
    public function importForQuiz(Request $request, Quiz $quiz)
    {
        $this->authorizeQuizAccess($quiz);

        $request->validate([
            'csv_file' => 'required|file|max:10240'
        ]);

        try {
            $file = $request->file('csv_file');
            $handle = fopen($file->getRealPath(), 'r');

            if (!$handle) {
                return back()->withErrors(['csv_file' => 'Nem sikerült megnyitni a fájlt.']);
            }

            $importedCount = 0;
            $rowNumber = 0;

            while (($row = fgetcsv($handle, 1000, ';')) !== FALSE) {
                $rowNumber++;

                // Fejléc átugrása
                if ($rowNumber === 1 && (str_contains(strtolower($row[0] ?? ''), 'kateg') || str_contains(strtolower($row[1] ?? ''), 'kérd') || str_contains(strtolower($row[0] ?? ''), 'kérd'))) {
                    continue;
                }

                // Minimum 5 oszlop ellenőrzése
                if (count($row) < 5) {
                    continue;
                }

                $questionText = trim($row[0]);
                $correctAnswer = trim($row[1]);
                $wrong1 = trim($row[2]);
                $wrong2 = trim($row[3]);
                $wrong3 = trim($row[4]);
                $difficultyRaw = strtolower(trim($row[5] ?? 'medium'));

                if (empty($questionText) || empty($correctAnswer)) {
                    continue;
                }

                $difficulty = 'medium';
                if (in_array($difficultyRaw, ['easy', 'könnyű', 'konnyu', '1'])) $difficulty = 'easy';
                if (in_array($difficultyRaw, ['hard', 'nehéz', 'nehez', '3'])) $difficulty = 'hard';

                // Kérdés rögzítése
                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'category_id' => $quiz->category_id,
                    'difficulty' => $difficulty,
                    'question_text' => ['hu' => $questionText],
                    'is_approved' => true,
                    'is_active' => true,
                    'creator_id' => Auth::id(),
                ]);

                $options = [
                    ['text' => $correctAnswer, 'correct' => true],
                    ['text' => $wrong1, 'correct' => false],
                    ['text' => $wrong2, 'correct' => false],
                    ['text' => $wrong3, 'correct' => false],
                ];
                shuffle($options);

                foreach ($options as $opt) {
                    Option::create([
                        'question_id' => $question->id,
                        'option_text' => ['hu' => $opt['text']],
                        'is_correct' => $opt['correct'],
                    ]);
                }

                $importedCount++;
            }

            fclose($handle);

            return back()->with('success', "📊 Sikeresen importálva {$importedCount} db kérdés ehhez a kvízhez!");

        } catch (\Throwable $e) {
            return back()->withErrors(['csv_file' => 'Hiba történt az importálás során: ' . $e->getMessage()]);
        }
    }

    /**
     * Kérdés szerkesztése form.
     */
    public function edit(Question $question)
    {
        $this->authorizeAccess($question);

        $user = Auth::user();

        $quizzesQuery = Quiz::with('category');
        if (!$user->isUseradmin()) {
            $quizzesQuery->where('creator_id', $user->id);
        }
        $quizzes = $quizzesQuery->get();

        $question->load(['options', 'quiz']);

        return view('questions.edit', compact('question', 'quizzes'));
    }

    /**
     * Kérdés frissítése.
     */
    public function update(Request $request, Question $question)
    {
        $this->authorizeAccess($question);

        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'nullable|string',
            'question_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'options' => 'required|array|min:4|max:4',
            'options.*.text' => 'nullable|string',
            'options.*.image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'correct_option' => 'required|integer|in:0,1,2,3',
        ]);

        $quiz = Quiz::findOrFail($request->quiz_id);

        if ($request->hasFile('question_image')) {
            if ($question->image_path) {
                Storage::disk('public')->delete($question->image_path);
            }
            $question->image_path = $request->file('question_image')->store('questions', 'public');
        }

        $question->update([
            'quiz_id' => $quiz->id,
            'category_id' => $quiz->category_id,
            'difficulty' => $request->difficulty,
            'question_text' => $request->filled('question_text') ? ['hu' => $request->question_text] : null,
            'image_path' => $question->image_path,
        ]);

        $existingOptions = $question->options;
        foreach ($request->options as $index => $optData) {
            $opt = $existingOptions[$index] ?? new Option(['question_id' => $question->id]);

            if (isset($optData['image']) && $request->hasFile("options.{$index}.image")) {
                if ($opt->image_path) {
                    Storage::disk('public')->delete($opt->image_path);
                }
                $opt->image_path = $request->file("options.{$index}.image")->store('options', 'public');
            }

            $opt->option_text = !empty($optData['text']) ? ['hu' => $optData['text']] : null;
            $opt->is_correct = ($index == $request->correct_option);
            $opt->save();
        }

        return redirect()->route('quizzes.show', $question->quiz_id)->with('success', '✏️ Kérdés sikeresen frissítve!');
    }

    /**
     * Kérdés törlése.
     */
    public function destroy(Question $question)
    {
        $this->authorizeAccess($question);

        $quizId = $question->quiz_id;

        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }
        foreach ($question->options as $opt) {
            if ($opt->image_path) {
                Storage::disk('public')->delete($opt->image_path);
            }
        }

        $question->options()->delete();
        $question->delete();

        return redirect()->route('quizzes.show', $quizId)->with('success', '🗑️ Kérdés sikeresen törölve!');
    }

    /**
     * Jogosultság ellenőrző segédfüggvény (Saját kérdéshez/adminisztrációhoz).
     */
    private function authorizeAccess(Question $question)
    {
        $user = Auth::user();

        if (!$user->canManageQuestion($question)) {
            abort(403, 'Nincs jogosultságod ehhez a kérdéshez!');
        }
    }

    /**
     * Jogosultság ellenőrző segédfüggvény a Kvízhez
     */
    private function authorizeQuizAccess(Quiz $quiz)
    {
        $user = Auth::user();
        if (!$user->isUseradmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez kérdést hozzáadni!');
        }
    }
}
