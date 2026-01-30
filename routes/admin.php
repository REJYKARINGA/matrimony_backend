<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminPromotionSettingController;
use App\Http\Controllers\Api\AdminMediatorPromotionController;

Route::prefix('admin')->middleware(['auth:sanctum', 'is_admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardStats']);

    Route::get('/verifications/pending', [AdminController::class, 'getPendingVerifications']);
    Route::post('/verifications/{id}/approve', [AdminController::class, 'approveVerification']);
    Route::post('/verifications/{id}/reject', [AdminController::class, 'rejectVerification']);

    // Users
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::post('/users/{id}/toggle-block', [AdminController::class, 'toggleBlockUser']);

    // User Profiles
    Route::get('/user-profiles', [AdminController::class, 'getUserProfiles']);

    // Family Details
    Route::get('/family-details', [AdminController::class, 'getFamilyDetails']);

    // Preferences
    Route::get('/preferences', [AdminController::class, 'getPreferences']);

    // Reports
    Route::get('/reports', [AdminController::class, 'getReports']);
    Route::post('/reports/{id}/resolve', [AdminController::class, 'resolveReport']);

    // Success Stories
    Route::get('/success-stories', [AdminController::class, 'getSuccessStories']);
    Route::post('/success-stories/{id}/approve', [AdminController::class, 'approveSuccessStory']);
    Route::post('/success-stories/{id}/reject', [AdminController::class, 'rejectSuccessStory']);

    // Payments
    Route::get('/payments', [AdminController::class, 'getPayments']);

    // Option Management
    Route::get('/education', [AdminController::class, 'getEducations']);
    Route::post('/education', [AdminController::class, 'storeEducation']);
    Route::put('/education/{id}', [AdminController::class, 'updateEducation']);
    Route::delete('/education/{id}', [AdminController::class, 'deleteEducation']);

    Route::get('/occupations', [AdminController::class, 'getOccupations']);
    Route::post('/occupations', [AdminController::class, 'storeOccupation']);
    Route::put('/occupations/{id}', [AdminController::class, 'updateOccupation']);
    Route::delete('/occupations/{id}', [AdminController::class, 'deleteOccupation']);

    // Wallet Transactions
    Route::get('/wallet/stats', [AdminController::class, 'getWalletStats']);
    Route::get('/wallet/transactions', [AdminController::class, 'getWalletTransactions']);

    // Promotion Settings
    Route::get('/promotion-settings', [AdminPromotionSettingController::class, 'index']);
    Route::post('/promotion-settings', [AdminPromotionSettingController::class, 'store']);
    Route::put('/promotion-settings/{id}', [AdminPromotionSettingController::class, 'update']);
    Route::delete('/promotion-settings/{id}', [AdminPromotionSettingController::class, 'destroy']);
    Route::put('/promotion-settings/{id}/set-default', [AdminPromotionSettingController::class, 'setDefault']);

    // Mediator Promotions
    Route::get('/mediator-promotions', [AdminMediatorPromotionController::class, 'index']);
    Route::put('/mediator-promotions/{id}', [AdminMediatorPromotionController::class, 'update']);
    Route::delete('/mediator-promotions/{id}', [AdminMediatorPromotionController::class, 'destroy']);
});
