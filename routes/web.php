<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SettingController;

// Hostadmin útvonalak csoportja
Route::prefix('admin')->name('admin.')->group(function () {

    // Beállítások
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Kategóriák kezelése
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
});
