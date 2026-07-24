<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizManagementController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;

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
    // Éles kvízek katalógusa (JÁTÉK menüpont)
    Route::get('/quizzes', [QuizController::class, 'showBetForm'])->name('quizzes.index');

    // Egyedi kvíz indítási folyamata (Tét, Nehézség, Start, Játék, Válasz, Dobókocka, Cashout)
    Route::prefix('quiz')->name('quiz.')->group(function () {
        // Tétbeállító képernyő
        Route::get('/setup/{quiz}', [QuizController::class, 'setupQuizPlay'])->name('setup');

        // Játék indítása (Session inicializálás & Tét levonás)
        Route::post('/play/{quiz}', [QuizController::class, 'startPlay'])->name('play');

        // A tényleges játék képernyő
        Route::get('/play/{quiz}/screen', [QuizController::class, 'playScreen'])->name('play.screen');

        // Válasz beküldése
        Route::post('/play/{quiz}/answer', [QuizController::class, 'submitAnswer'])->name('submit_answer');

        // Dobókocka elgurítása (Rossz válasz mentőöv)
        Route::post('/play/{quiz}/roll-dice', [QuizController::class, 'rollDice'])->name('roll_dice');

        // Kiszállás (Nyeremény felvétele)
        Route::post('/play/{quiz}/cashout', [QuizController::class, 'cashout'])->name('cashout');
    });


    // ------------------------------------------------------------------------
    // 3. ALKOTÓI FELÜLET (Saját kvízek szerkesztése, létrehozása & CSV import)
    // ------------------------------------------------------------------------
    Route::resource('my-quizzes', QuizManagementController::class)->names('my-quizzes');

    // Kérdések szerkesztése és CSV Import a saját kvízekhez
    Route::post('/my-quizzes/{quiz}/questions/import', [QuestionController::class, 'importForQuiz'])->name('my-quizzes.questions.import');
    Route::post('/my-quizzes/{quiz}/questions/store', [QuestionController::class, 'storeForQuiz'])->name('questions.storeForQuiz');

    // Kérdések külön erőforrás-kezelője
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

        // DevTool szerepkörváltás
        Route::post('/switch-role', [ProfileController::class, 'switchRole'])->name('switch-role');
    });


    // ------------------------------------------------------------------------
    // 5. ADMINISZTRÁCIÓ (Kategóriák, Beállítások, Felhasználók, Bírálat)
    // ------------------------------------------------------------------------
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('settings', SettingController::class);

        // Kvíz jóváhagyása & elutasítása
        Route::post('/quizzes/{quiz}/approve', [QuizManagementController::class, 'approveQuiz'])->name('quizzes.approve');
        Route::post('/quizzes/{quiz}/reject', [QuizManagementController::class, 'rejectQuiz'])->name('quizzes.reject');

        // Felhasználók listája és kvíz tulajdonjog átadás
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/quizzes/{quiz}/transfer', [QuizManagementController::class, 'transferOwnership'])->name('quizzes.transfer');
    });

});

require __DIR__.'/auth.php';
