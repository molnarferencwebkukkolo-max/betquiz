<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

// Kezdőlap átirányítása a Loginra
Route::get('/', function () {
    return redirect()->route('login');
});

// Védett Útvonalak (Csak bejelentkezett felhasználóknak)
Route::middleware(['auth', 'verified'])->group(function () {

    // Játékos Dashboard
    Route::get('/dashboard', [QuizController::class, 'dashboard'])->name('dashboard');

    // Valódi Kvíz Játék
    Route::get('/quiz/play', [QuizController::class, 'start'])->name('quiz.start');
    Route::post('/quiz/check-answer', [QuizController::class, 'checkAnswer'])->name('quiz.check');

    // Profil kezelés
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin felület
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
    Route::resource('questions', QuestionController::class)->except(['create', 'edit', 'show']);
});

require __DIR__.'/auth.php';
