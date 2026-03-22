<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DraftPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('PublicHome');
})->name('home');

Route::get('/@{slug}', [PublicPageController::class, 'show'])->where('slug', '[a-z0-9\-]+');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
    /** Vista previa borrador (bloques activos, publicados o no). Cualquier usuario autenticado puede verla (sin restricción al dueño por ahora). */
    Route::get('/draft/@{slug}', [DraftPageController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('draft.preview');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
