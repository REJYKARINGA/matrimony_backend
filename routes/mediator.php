<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MediatorPromotionController;

Route::prefix('mediator')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/promotions', [MediatorPromotionController::class, 'index']);
    Route::post('/promotions', [MediatorPromotionController::class, 'store']);
});
