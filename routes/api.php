<?php

use App\Http\Controllers\Api\BlockController;
use App\Http\Controllers\Api\BlockSchemaController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SitePublishController;
use App\Http\Middleware\AcceptJson;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', AcceptJson::class])->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/site/publish', [SitePublishController::class, 'store']);
    Route::get('/block-schemas', [BlockSchemaController::class, 'index']);

    Route::get('/blocks', [BlockController::class, 'index']);
    Route::post('/blocks', [BlockController::class, 'store']);
    Route::post('/blocks/reorder', [BlockController::class, 'reorder']);
    Route::post('/blocks/{block}/duplicate', [BlockController::class, 'duplicate']);
    Route::patch('/blocks/{block}/toggle', [BlockController::class, 'toggle']);
    Route::put('/blocks/{block}', [BlockController::class, 'update']);
    Route::delete('/blocks/{block}', [BlockController::class, 'destroy']);
});
