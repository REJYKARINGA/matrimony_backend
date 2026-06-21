<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PartnerDashboardController;

Route::prefix('partner')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/stats', [PartnerDashboardController::class, 'getStats']);
    Route::get('/registrations', [PartnerDashboardController::class, 'getRegistrations']);
    Route::get('/agents', [PartnerDashboardController::class, 'getAgents']);
    Route::post('/agents', [PartnerDashboardController::class, 'addAgent']);

    // Bank Accounts
    Route::get('/bank-accounts', [PartnerDashboardController::class, 'getBankAccounts']);
    Route::post('/bank-accounts', [PartnerDashboardController::class, 'addBankAccount']);
    Route::put('/bank-accounts/{id}/primary', [PartnerDashboardController::class, 'setPrimaryBankAccount']);
    Route::delete('/bank-accounts/{id}', [PartnerDashboardController::class, 'deleteBankAccount']);

    // Payout
    Route::post('/request-payout', [PartnerDashboardController::class, 'requestPayout']);
});
