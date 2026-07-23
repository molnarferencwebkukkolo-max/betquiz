<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizPlayController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizManagementController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // ------------------------------------------------------------------------
    // 1. DASHBOARD & KATALÓGUS
    // ------------------------------------------------------------------------
    Route::get('/dashboard', [QuizController::class, 'dashboard'])->name('dashboard');
    Route::get('/quiz/catalog', [QuizController::class, 'showBetForm'])->name('quiz.bet');

    // ------------------------------------------------------------------------
    // 2. EGYSÉGES JÁTÉKFOLYAMAT (QuizPlayController)
    // ------------------------------------------------------------------------
    // Tét- és nehézségbeállító képernyő
    Route::get('/quiz/setup/{quiz}', [QuizController::class, 'setupQuizPlay'])->name('quiz.setup');

    // Tét levonása, session inicializálása és játék indítása
    Route::post('/quiz/start/{quiz}', [QuizPlayController::class, 'start'])->name('quiz.start');

    // Tényleges játék felület (kérdések)
    Route::get('/quiz/play/{quiz}', [QuizPlayController::class, 'play'])->name('quiz.play');

    // Válasz ellenőrzése (AJAX JSON válasz)
    Route::post('/quiz/check', [QuizPlayController::class, 'answer'])->name('quiz.check');

    // ------------------------------------------------------------------------
    // 3. KVÍZ- ÉS KÉRDÉSSZERKESZTŐ (Saját kvízek & CSV Import)
    // ------------------------------------------------------------------------
    Route::post('/quizzes/{quiz}/questions/import', [QuestionController::class, 'importForQuiz'])->name('quizzes.questions.import');
    Route::post('/quizzes/{quiz}/questions/store', [QuestionController::class, 'storeForQuiz'])->name('questions.storeForQuiz');

    Route::resource('quizzes', QuizManagementController::class);
    Route::resource('questions', QuestionController::class);


    // ------------------------------------------------------------------------
    // 4. PROFIL ÉS EGYÉB OLDALAK
    // ------------------------------------------------------------------------
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/points', [PageController::class, 'points'])->name('pages.points');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    // ⚠️ EZ A SOR KELL A SZEREPKÖR-VÁLTÁSHOZ:
    // A profil útvonala a kártyás show nézetre mutasson:

    // 1. A kártyás nézet megjelenítése a /profile/show URL-en:
    Route::get('/profile/show', [ProfileController::class, 'show'])->name('profile.show');

    // 2. Opcionális: Ha valaki a sima /profile-ra tévedne, az is ide mutasson:
    Route::get('/profile', [ProfileController::class, 'show']);

    // 3. Adatfrissítés és a DevTool szerepkörváltó útvonalai:
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/switch-role', [ProfileController::class, 'switchRole'])->name('profile.switch-role');


    // ------------------------------------------------------------------------
    // 5. ADMINISZTRÁCIÓ (Admin jóváhagyások, Kategóriák, Beállítások)
    // ------------------------------------------------------------------------
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('settings', SettingController::class);

        // Kvíz jóváhagyása & elutasítása
        Route::post('/quizzes/{quiz}/approve', [QuizManagementController::class, 'approveQuiz'])->name('quizzes.approve');
        Route::post('/quizzes/{quiz}/reject', [QuizManagementController::class, 'rejectQuiz'])->name('quizzes.reject');

        // 🛡️ Felhasználók listája a Hostadmin számára:
        Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');

        // Kvíz tulajdonjogának átadása (Hostadmin)
        Route::post('/quizzes/{quiz}/transfer', [QuizManagementController::class, 'transferOwnership'])->name('quizzes.transfer');
    });
});

require __DIR__.'/auth.php';
