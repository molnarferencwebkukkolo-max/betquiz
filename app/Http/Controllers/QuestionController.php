<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\Option;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    /**
     * Kérdések listázása
     */
    public function index()
    {
        $user = Auth::user();

        $query = Question::with(['quiz.creator', 'category', 'options'])->latest();

        if (!$user->isUseradmin()) {
            $query->whereHas('quiz', fn ($quizQuery) => $quizQuery->where('creator_id', $user->id));
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

        // 1. Validáció kibővítése a képfájlra (PNG, JPG, WEBP, max 2MB)
        $request->validate([
            'question_text' => 'required|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'options' => 'required|array|min:2|max:4',
            'options.*.text' => 'required|string|max:255',
            'options.*.is_correct' => 'required|boolean',
        ]);

        try {
            DB::transaction(function () use ($request, $quiz) {

                // 2. Kép feltöltése (ha küldött a felhasználó)
                $imagePath = null;
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('questions', 'public');
                }

                // 3. Kérdés mentése az kép elérési útjával
                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    // A kategória legacy mező, mindig a kvízből öröklődik.
                    'category_id' => $quiz->category_id,
                    'question_text' => ['hu' => $request->question_text],
                    'image_path' => $imagePath,
                ]);

                // 4. Opciók mentése
                foreach ($request->options as $opt) {
                    Option::create([
                        'question_id' => $question->id,
                        'option_text' => $opt['text'],
                        'is_correct' => (bool)$opt['is_correct'],
                    ]);
                }
            });

        } catch (\Exception $e) {
            Log::error('Hiba a kérdés és kép mentésekor (storeForQuiz): ' . $e->getMessage());
            return back()->withInput()->with('error', 'Adatbázis- vagy fájlmentési hiba történt.');
        }

        return back()->with('success', 'A kérdés és a kép sikeresen elmentve!');
    }

    /**
     * CSV Importálás egy adott kvízhez tranzakcióval és golyóálló hibakezeléssel
     */
    public function importForQuiz(Request $request, Quiz $quiz)
    {
        // 1. Jogosultság ellenőrzése
        $this->authorizeQuizAccess($quiz);

        // 2. SZIGORÚ FÁJL- ÉS MIME-TÍPUS VALIDÁCIÓ (csv, txt, max 2MB)
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|mimetype:text/csv,text/plain|max:2048',
        ], [
            'csv_file.mimes' => 'Kizárólag .csv vagy .txt kiterjesztésű fájl tölthető fel!',
            'csv_file.mimetype' => 'A feltöltött fájl formátuma érvénytelen.',
            'csv_file.max' => 'A fájl mérete nem haladhatja meg a 2 MB-ot.',
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getRealPath();

        // 3. UTF-8 BOM KARAKTEREK ELTÁVOLÍTÁSA (Ha az Excel/Notepad++ tette bele)
        $content = file_get_contents($filePath);
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
        file_put_contents($filePath, $content);

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return back()->with('error', 'A feltöltött fájl nem olvasható.');
        }

        $errors = [];
        $rowsToInsert = [];
        $rowNumber = 0;

        // Fejléc beolvasása és átugrása
        $header = fgetcsv($handle, 1000, ',');

        // 4. SORONKÉNTI SZIGORÚ ELLENŐRZÉS
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $rowNumber++;

            // ÜRES SOROK KISZŰRÉSE
            if (empty(array_filter($data, 'trim'))) {
                continue;
            }

            // HIBÁS OSZLOPSZÁM ELLENŐRZÉSE (Pontosan 6 oszlopnak kell lennie)
            if (count($data) < 6) {
                $errors[] = "A(z) {$rowNumber}. sor hiányos: kevesebb mint 6 oszlopot tartalmaz.";
                continue;
            }

            $questionText = trim($data[0]);
            $opt1 = trim($data[1]);
            $opt2 = trim($data[2]);
            $opt3 = trim($data[3] ?? '');
            $opt4 = trim($data[4] ?? '');
            $correctIndex = trim($data[5]);

            // STRUKTURÁLIS ÉS MÉRATKISEBBÍTTŐ VALIDÁCIÓ
            $validator = Validator::make([
                'question_text' => $questionText,
                'option_1' => $opt1,
                'option_2' => $opt2,
                'correct_index' => $correctIndex,
            ], [
                'question_text' => 'required|string|max:1000', // Túl hosszú mező kivédve
                'option_1' => 'required|string|max:255',
                'option_2' => 'required|string|max:255',
                'correct_index' => 'required|integer|min:1|max:4', // Pontosan 1 helyes válasz lehetséges (1-4)
            ]);

            if ($validator->fails()) {
                $errors[] = "A(z) {$rowNumber}. sor érvénytelen: hiányzó adat, túl hosszú szöveg vagy hibás helyes válasz megjelölés (1-4 lehet).";
                continue;
            }

            // AZONOS VÁLASZLEHETŐSÉGEK KISZŰRÉSE
            $optionsList = array_filter([$opt1, $opt2, $opt3, $opt4], fn($value) => $value !== '');
            if (count($optionsList) !== count(array_unique($optionsList))) {
                $errors[] = "A(z) {$rowNumber}. sorban azonos válaszlehetőségek szerepelnek.";
                continue;
            }

            // HELYES VÁLASZ INDEXÉNEK ELLENŐRZÉSE (Ne lehessen több vagy 0 helyes válasz)
            $optionsData = [
                ['text' => $opt1, 'is_correct' => ($correctIndex == 1)],
                ['text' => $opt2, 'is_correct' => ($correctIndex == 2)],
            ];

            if ($opt3 !== '') {
                $optionsData[] = ['text' => $opt3, 'is_correct' => ($correctIndex == 3)];
            }
            if ($opt4 !== '') {
                $optionsData[] = ['text' => $opt4, 'is_correct' => ($correctIndex == 4)];
            }

            // Ellenőrizzük, hogy a megadott helyes indexű válasz ténylegesen létezik-e (pl. ha correct_index=4, de csak 2 opció van)
            $hasCorrectOption = false;
            foreach ($optionsData as $opt) {
                if ($opt['is_correct']) {
                    $hasCorrectOption = true;
                    break;
                }
            }

            if (!$hasCorrectOption) {
                $errors[] = "A(z) {$rowNumber}. sorban megadott helyes válasz indexe ({$correctIndex}) olyan opcióra mutat, ami üres.";
                continue;
            }

            $rowsToInsert[] = [
                'question_text' => $questionText,
                'options' => $optionsData,
            ];
        }

        fclose($handle);

        // Ha akár egyetlen tartalmi/módszertani hiba is volt, megszakítjuk az importot
        if (!empty($errors)) {
            return back()->with('import_errors', $errors)->with('error', 'Az importálás megszakadt, mert a fájl hibás vagy érvénytelen sorokat tartalmaz.');
        }

        if (empty($rowsToInsert)) {
            return back()->with('error', 'A CSV fájl nem tartalmazott feldolgozható kérdéseket.');
        }

        // 5. ATOMI TRANZAKCIÓ MENTÉS
        try {
            DB::transaction(function () use ($rowsToInsert, $quiz) {
                foreach ($rowsToInsert as $item) {
                    $question = Question::create([
                        'quiz_id' => $quiz->id,
                        // A kategória legacy mező, mindig a kvízből öröklődik.
                        'category_id' => $quiz->category_id,
                        'question_text' => ['hu' => $item['question_text']],
                    ]);

                    foreach ($item['options'] as $opt) {
                        Option::create([
                            'question_id' => $question->id,
                            'option_text' => $opt['text'],
                            'is_correct' => $opt['is_correct'],
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            Log::error('CSV Importálási hiba (importForQuiz): ' . $e->getMessage(), [
                'quiz_id' => $quiz->id,
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Adatbázis-hiba történt az importálás során. A művelet megszakadt, nem maradtak sérült adatok.');
        }

        return back()->with('success', "Sikeresen importálva " . count($rowsToInsert) . " db kérdés!");
    }

    /**
     * Kérdés szerkesztése form.
     */
    public function edit(Question $question)
    {
        $this->authorizeQuestionAccess($question);

        $user = Auth::user();

        $quizzesQuery = Quiz::with('category');
        if (!$user->isUseradmin()) {
            $quizzesQuery->where('creator_id', $user->id);
        }
        $quizzes = $quizzesQuery->get();
        $question->load(['options', 'quiz']);

        return view('questions.edit', compact('question', 'quizzes'));
    }


    public function update(Request $request, Question $question)
    {
        $this->authorizeQuestionAccess($question);

        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'required|string|max:1000',
            'question_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'correct_option' => 'required|integer|min:0',
            'options' => 'required|array|min:2|max:4',
            'options.*.text' => 'nullable|string|max:255',
            'options.*.image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $question->load(['options', 'quiz']);
        $user = Auth::user();
        $targetQuiz = Quiz::findOrFail($validated['quiz_id']);

        // Kvízt csak admin válthat; normál felhasználó a saját kérdését sem
        // mozgathatja át másik kvízbe ezen a szerkesztőfelületen.
        if (!$user->isUseradmin() && !$user->isHostadmin() && $targetQuiz->id !== $question->quiz_id) {
            abort(403, 'Csak adminisztrátor helyezhet át kérdést másik kvízbe.');
        }

        $correctOptionIndex = (int) $validated['correct_option'];

        if (!array_key_exists($correctOptionIndex, $validated['options'])) {
            return back()->withInput()->withErrors([
                'correct_option' => 'A kiválasztott helyes válasz nem létezik.',
            ]);
        }

        DB::transaction(function () use ($request, $question, $targetQuiz, $validated, $correctOptionIndex) {
            $questionData = [
                'quiz_id' => $targetQuiz->id,
                // A legacy adatbázisoszlop még kötelező, ezért a kérdés mindig
                // a kvíz kategóriáját örökli, önálló kategóriát nem kezelünk.
                'category_id' => $targetQuiz->category_id,
                'difficulty' => $validated['difficulty'],
                'question_text' => ['hu' => $validated['question_text']],
            ];

            if ($request->hasFile('question_image')) {
                if ($question->image_path) {
                    Storage::disk('public')->delete($question->image_path);
                }

                $questionData['image_path'] = $request->file('question_image')->store('questions', 'public');
            }

            $question->update($questionData);

            foreach ($validated['options'] as $index => $optionData) {
                $option = $question->options->values()->get((int) $index);

                if (!$option) {
                    continue;
                }

                $updateData = [
                    'option_text' => ['hu' => $optionData['text'] ?? ''],
                    'is_correct' => (int) $index === $correctOptionIndex,
                ];

                if ($request->hasFile("options.{$index}.image")) {
                    if ($option->image_path) {
                        Storage::disk('public')->delete($option->image_path);
                    }

                    $updateData['image_path'] = $request
                        ->file("options.{$index}.image")
                        ->store('options', 'public');
                }

                $option->update($updateData);
            }
        });

        return redirect()
            ->route('my-quizzes.show', $targetQuiz)
            ->with('success', 'Kérdés sikeresen frissítve!');
    }

    /**
     * Kérdés törlése.
     */
    public function destroy(Question $question)
    {
        $this->authorizeQuestionAccess($question);

        // 💣 Opciók törlése
        $question->options()->delete();

        // 💣 Kérdés törlése
        $question->delete();

        return back()->with('success', 'A kérdés sikeresen törölve lett!');
    }

    /**
     * Több kérdés nehézségének vagy kapcsolódó kvízének módosítása.
     */
    public function bulkUpdate(Request $request, Quiz $quiz)
    {
        $this->authorizeQuizAccess($quiz);
        $user = Auth::user();

        $validated = $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer',
            'bulk_action' => 'required|in:change_difficulty,move_to_quiz',
            'difficulty' => 'nullable|required_if:bulk_action,change_difficulty|in:easy,medium,hard',
            'target_quiz_id' => 'nullable|required_if:bulk_action,move_to_quiz|exists:quizzes,id',
        ]);

        $questionIds = array_values(array_unique(array_map('intval', $validated['question_ids'])));
        $questionsQuery = Question::query()
            ->where('quiz_id', $quiz->id)
            ->whereIn('id', $questionIds);

        if ((clone $questionsQuery)->count() !== count($questionIds)) {
            abort(422, 'A kijelölt kérdések között másik kvízhez tartozó elem is van.');
        }

        if ($validated['bulk_action'] === 'change_difficulty') {
            $updatedCount = $questionsQuery->update([
                'difficulty' => $validated['difficulty'],
            ]);
        } else {
            if (!$user->isUseradmin() && !$user->isHostadmin()) {
                abort(403, 'Csak adminisztrátor helyezhet át kérdéseket másik kvízbe.');
            }

            $targetQuiz = Quiz::findOrFail($validated['target_quiz_id']);
            $updatedCount = $questionsQuery->update([
                'quiz_id' => $targetQuiz->id,
                // A legacy kategória mindig a célkvíz kategóriáját követi.
                'category_id' => $targetQuiz->category_id,
            ]);
        }

        return back()->with('success', "{$updatedCount} kérdés tömeges módosítása elkészült.");
    }

    /**
     * Admin minden kvízt kezelhet, normál felhasználó csak a sajátját.
     */
    private function authorizeQuizAccess(Quiz $quiz): void
    {
        $user = Auth::user();

        if (!$user->isUseradmin() && !$user->isHostadmin() && $quiz->creator_id !== $user->id) {
            abort(403, 'Nincs jogosultságod ehhez a kvízhez.');
        }
    }

    /**
     * A kérdés jogosultsága a hozzá tartozó kvíz tulajdonosából következik.
     */
    private function authorizeQuestionAccess(Question $question): void
    {
        $question->loadMissing('quiz');

        if (!$question->quiz) {
            abort(403, 'A kérdés nem tartozik kezelhető kvízhez.');
        }

        $this->authorizeQuizAccess($question->quiz);
    }
}
