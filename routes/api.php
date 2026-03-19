<?php

use App\Http\Controllers\Api\BlockSchemaController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Middleware\AcceptJson;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', AcceptJson::class])->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::get('/block-schemas', [BlockSchemaController::class, 'index']);
});
