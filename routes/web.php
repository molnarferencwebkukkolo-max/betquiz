<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\QuizManagementController;


// Kezdőlap átirányítása a Dashboardra
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Védett Útvonalak (Csak bejelentkezett felhasználóknak)


    Route::middleware(['auth', 'verified'])->group(function () {

        // 1) JÁTÉK (Dashboard & Kvíz folyamat)
        Route::get('/dashboard', [QuizController::class, 'dashboard'])->name('dashboard');
// Játék beállítása konkrét kvíz kiválasztásával
        Route::get('/quiz/play/{quiz}', [QuizController::class, 'setupQuizPlay'])->name('quiz.setup');


       // Játék főoldal (Kvíz Választó)
       Route::get('/quiz/play', [QuizController::class, 'showBetForm'])->name('quiz.bet');

       // Kedvencnek jelölés AJAX/POST route (opcionális, de hasznos)
       Route::post('/quizzes/{quiz}/favorite', [QuizController::class, 'toggleFavorite'])->name('quizzes.favorite');


        // ⚠️ ITT A HIÁNYZÓ NEVŰ ÚTVONAL:
        Route::get('/quiz', [QuizController::class, 'showBetForm'])->name('quiz.bet');
        Route::post('/quiz/start', [QuizController::class, 'start'])->name('quiz.start');
        Route::get('/quiz/next', [QuizController::class, 'nextQuestion'])->name('quiz.next');
        Route::post('/quiz/answer', [QuizController::class, 'answer'])->name('quiz.answer');
        Route::get('/quiz/summary', [QuizController::class, 'summary'])->name('quiz.summary');

        // 🛡️ Admin Bírálati Útvonalak
        Route::post('/quizzes/{quiz}/approve', [QuizManagementController::class, 'approveQuiz'])->name('quizzes.approve');
        Route::post('/quizzes/{quiz}/reject', [QuizManagementController::class, 'rejectQuiz'])->name('quizzes.reject');

        // 🎯 Kvíz válaszellenőrző / beküldő route
        Route::post('/quiz/check', [QuizController::class, 'checkAnswer'])->name('quiz.check');
        // 1. Tétbeállító képernyő (Form)
        Route::get('/quiz/setup/{quiz}', [QuizController::class, 'setupQuizPlay'])->name('quiz.setup');

        // 2. Tét levonása & játék indítása (POST)
        Route::post('/quiz/start/{quiz}', [QuizController::class, 'startQuizPlay'])->name('quiz.start');
        // 2) KVÍZEK
        Route::get('/quizzes', [QuizManagementController::class, 'index'])->name('quizzes.index');
        Route::get('/quizzes/create', [QuizController::class, 'createQuiz'])->name('quizzes.create');
        Route::post('/quizzes', [QuizController::class, 'storeQuiz'])->name('quizzes.store');
        Route::get('/quizzes/{quiz}', [QuizManagementController::class, 'show'])->name('quizzes.show');
        Route::post('/quizzes/{quiz}/toggle-publish', [QuizManagementController::class, 'togglePublish'])->name('quizzes.toggle-publish');
        Route::post('/quizzes/{quiz}/import-questions', [QuestionController::class, 'importForQuiz'])->name('quizzes.questions.import');
        Route::get('/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
        Route::put('/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');

        // 3) KÉRDÉSBANK
        Route::resource('questions', QuestionController::class);
        Route::post('/questions/import', [QuestionController::class, 'import'])->name('questions.import');

// Kérdés hozzáadása és importálása egy konkrét Kvízhez
        Route::get('/quizzes/{quiz}/questions/create', [QuestionController::class, 'createForQuiz'])->name('quizzes.questions.create');
        Route::post('/quizzes/{quiz}/questions', [QuestionController::class, 'storeForQuiz'])->name('quizzes.questions.store');

// 🎯 EZ AZ ÚJ SOR HIÁNYZOTT:
        Route::post('/quizzes/{quiz}/import-questions', [QuestionController::class, 'importForQuiz'])->name('quizzes.questions.import');

        // 4) FELHASZNÁLÓK
        Route::get('/users', [PageController::class, 'usersComingSoon'])->name('users.index');

        // 5) PROFILOM
        Route::get('/profile', [UserController::class, 'profile'])->name('profile.show');
        Route::post('/profile/password', [UserController::class, 'updatePassword'])->name('profile.password');
        Route::post('/profile/switch-role', [UserController::class, 'switchRole'])->name('profile.switch-role');

        // 6) SZEREZZ PONTOT
        Route::get('/get-points', [PageController::class, 'points'])->name('pages.points');

    });

// Admin felület (Beállítások és Kategóriák)
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
});

// Kvíznyitó útvonalak
Route::get('/quizzes/create', [QuizController::class, 'createQuiz'])->name('quizzes.create');
Route::post('/quizzes', [QuizController::class, 'storeQuiz'])->name('quizzes.store');

require __DIR__.'/auth.php';
