<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RollDiceController;
use App\Http\Controllers\TimeTravellerController;
use App\Http\Controllers\QuizManagementController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\QuizHelperController;

/*
|--------------------------------------------------------------------------
| Web Routes - BetQuiz
|--------------------------------------------------------------------------
*/

Route::get('/', [QuizController::class, 'dashboard']);

// Az e-mail-hitelesítés jelenleg nincs aktiválva a User modellen, ezért
// itt csak a valóban érvényes auth- és fiókállapot-feltételek szerepelnek.
Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    // ------------------------------------------------------------------------
    // 1. DASHBOARD
    // ------------------------------------------------------------------------
    Route::get('/dashboard', [QuizController::class, 'dashboard'])->name('dashboard');


    // ------------------------------------------------------------------------
    // 2. JÁTÉKOS FELÜLET (Katalógus & Játékmenet)
    // ------------------------------------------------------------------------
    Route::get('/quizzes', [QuizController::class, 'showBetForm'])->name('quizzes.index');

    Route::prefix('quiz')->name('quiz.')->group(function () {
        // Tétbeállító képernyő (quiz.setup)
        Route::get('/setup/{quiz}', [QuizController::class, 'setupQuizPlay'])->name('setup');

        // Játék indítása (quiz.start_play)
        Route::post('/play/{quiz}', [QuizController::class, 'startPlay'])->name('start_play');

        // Játék képernyő (quiz.play.screen)
        Route::get('/play/{quiz}/screen', [QuizController::class, 'playScreen'])->name('play.screen');
        Route::post('/play/{quiz}/next', [QuizController::class, 'nextQuestion'])->name('next_question');

        // Válasz beküldése (quiz.submit_answer)
        Route::post('/play/{quiz}/answer', [QuizController::class, 'submitAnswer'])->name('submit_answer');

        // Dobókocka mentőöv (quiz.roll_dice)
        Route::post('/play/{quiz}/roll-dice', [RollDiceController::class, 'rollDice'])->name('roll_dice');
        Route::post('/play/{quiz}/roll-dice/finish', [RollDiceController::class, 'finishDiceResult'])->name('roll_dice.finish');

        // Emmett Brown mentőöv (quiz.time_travel)
        Route::post('/play/{quiz}/time-travel', [TimeTravellerController::class, 'timeTravel'])->name('time_travel');

        // Kérdés közben használható KwizzGo-segítségek.
        Route::post('/play/{quiz}/helpers/blackjack', [QuizHelperController::class, 'startBlackjack'])->name('helpers.blackjack.start');
        Route::post('/play/{quiz}/helpers/blackjack/action', [QuizHelperController::class, 'blackjackAction'])->name('helpers.blackjack.action');
        Route::post('/play/{quiz}/helpers/blackjack/abandon', [QuizHelperController::class, 'abandonBlackjack'])->name('helpers.blackjack.abandon');
        Route::post('/play/{quiz}/helpers/resolve', [QuizHelperController::class, 'resolve'])->name('helpers.resolve');
        Route::post('/play/{quiz}/helpers/{helper}', [QuizHelperController::class, 'use'])->name('helpers.use');

        // Kiszállás (quiz.cashout)
        Route::post('/play/{quiz}/cashout', [QuizController::class, 'cashout'])->name('cashout');

        // 🟢 JAVÍTVA: Like, dislike, restart (A prefix miatt nem kell elé a /quiz/ és a name-be sem a quiz.)
        Route::post('/{quiz}/toggle-favorite', [QuizController::class, 'toggleFavorite'])->name('toggle-favorite');
        Route::post('/{quiz}/toggle-dislike', [QuizController::class, 'toggleDislike'])->name('toggle-dislike');
        Route::post('/{quiz}/reset-answers', [QuizController::class, 'resetQuizAnswers'])->name('reset-answers');
    });


    // ------------------------------------------------------------------------
    // 3. ALKOTÓI FELÜLET (Saját kvízek szerkesztése, létrehozása & CSV import)
    // ------------------------------------------------------------------------
    Route::resource('my-quizzes', QuizManagementController::class)
        ->names('my-quizzes')
        ->parameters(['my-quizzes' => 'quiz']);
    Route::get('/my-quizzes/{quiz}/preview', [QuizManagementController::class, 'preview'])
        ->name('my-quizzes.preview');

    Route::post('/my-quizzes/{quiz}/questions/import', [QuestionController::class, 'importForQuiz'])->name('my-quizzes.questions.import');
    Route::post('/my-quizzes/{quiz}/questions/store', [QuestionController::class, 'storeForQuiz'])->name('questions.storeForQuiz');
    Route::patch('/my-quizzes/{quiz}/questions/bulk', [QuestionController::class, 'bulkUpdate'])
        ->name('my-quizzes.questions.bulk-update');

    Route::resource('questions', QuestionController::class);


    // ------------------------------------------------------------------------
    // 4. PROFIL ÉS EGYÉB OLDALAK
    // ------------------------------------------------------------------------
    Route::get('/points', [PageController::class, 'points'])->name('pages.points');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/results', [ProfileController::class, 'results'])->name('results');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::post('/game-experience', [ProfileController::class, 'updateGameExperience'])->name('game-experience');
        Route::patch('/private-details', [ProfileController::class, 'updatePrivateDetails'])->name('private-details');
        Route::patch('/notification-preferences', [NotificationPreferenceController::class, 'update'])
            ->name('notification-preferences');
        Route::post('/password', [ProfileController::class, 'updatePassword'])->name('password');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');

        Route::post('/switch-role', [ProfileController::class, 'switchRole'])->name('switch-role');
    });


    // ------------------------------------------------------------------------
    // 5. ADMINISZTRÁCIÓ
    // ------------------------------------------------------------------------
    Route::prefix('admin')->name('admin.')->group(function () {
        // A kategóriáknál csak a ténylegesen használt kezelőműveleteket tesszük elérhetővé.
        Route::resource('categories', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('settings', SettingController::class);

        Route::post('/quizzes/{quiz}/approve', [QuizManagementController::class, 'approveQuiz'])->name('quizzes.approve');
        Route::post('/quizzes/{quiz}/reject', [QuizManagementController::class, 'rejectQuiz'])->name('quizzes.reject');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])
            ->name('users.status');
        Route::get('/quizzes/search', [QuizManagementController::class, 'search'])->name('quizzes.search');
        Route::post('/quizzes/{quiz}/transfer', [QuizManagementController::class, 'transferOwnership'])->name('quizzes.transfer');
        Route::patch('/quizzes/bulk', [QuizManagementController::class, 'bulkUpdate'])->name('quizzes.bulk-update');
    });

});

require __DIR__.'/auth.php';
