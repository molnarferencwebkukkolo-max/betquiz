<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizManagementController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes - BetQuiz
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {

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
        Route::post('/play/{quiz}/roll-dice', [QuizController::class, 'rollDice'])->name('roll_dice');

        //Emett Brown mentőőv
        Route::post('/play/{quiz}/time-travel', [QuizController::class, 'timeTravel'])->name('time_travel');

        // Kiszállás (quiz.cashout)
        Route::post('/play/{quiz}/cashout', [QuizController::class, 'cashout'])->name('cashout');
    });


    // ------------------------------------------------------------------------
    // 3. ALKOTÓI FELÜLET (Saját kvízek szerkesztése, létrehozása & CSV import)
    // ------------------------------------------------------------------------
    Route::resource('my-quizzes', QuizManagementController::class)
        ->names('my-quizzes')
        ->parameters(['my-quizzes' => 'quiz']);

    Route::post('/my-quizzes/{quiz}/questions/import', [QuestionController::class, 'importForQuiz'])->name('my-quizzes.questions.import');
    Route::post('/my-quizzes/{quiz}/questions/store', [QuestionController::class, 'storeForQuiz'])->name('questions.storeForQuiz');

    Route::resource('questions', QuestionController::class);


    // ------------------------------------------------------------------------
    // 4. PROFIL ÉS EGYÉB OLDALAK
    // ------------------------------------------------------------------------
    Route::get('/points', [PageController::class, 'points'])->name('pages.points');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::post('/password', [ProfileController::class, 'updatePassword'])->name('password');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');

        Route::post('/switch-role', [ProfileController::class, 'switchRole'])->name('switch-role');
    });


    // ------------------------------------------------------------------------
    // 5. ADMINISZTRÁCIÓ
    // ------------------------------------------------------------------------
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('settings', SettingController::class);

        Route::post('/quizzes/{quiz}/approve', [QuizManagementController::class, 'approveQuiz'])->name('quizzes.approve');
        Route::post('/quizzes/{quiz}/reject', [QuizManagementController::class, 'rejectQuiz'])->name('quizzes.reject');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/quizzes/{quiz}/transfer', [QuizManagementController::class, 'transferOwnership'])->name('quizzes.transfer');
    });

});

require __DIR__.'/auth.php';
