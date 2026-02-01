<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MediatorPromotionController;

Route::prefix('mediator')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/promotions', [MediatorPromotionController::class, 'index']);
    Route::post('/promotions', [MediatorPromotionController::class, 'store']);
    Route::post('/request-payout', [MediatorPromotionController::class, 'requestPayout']);

    // Bank Accounts Management
    Route::get('/bank-accounts', [MediatorPromotionController::class, 'getBankAccounts']);
    Route::post('/bank-accounts', [MediatorPromotionController::class, 'addBankAccount']);
    Route::put('/bank-accounts/{id}/primary', [MediatorPromotionController::class, 'setPrimaryBankAccount']);
    Route::delete('/bank-accounts/{id}', [MediatorPromotionController::class, 'deleteBankAccount']);
});
