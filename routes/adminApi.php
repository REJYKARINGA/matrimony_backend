<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminPromotionSettingController;
use App\Http\Controllers\Api\AdminMediatorPromotionController;
use App\Http\Controllers\Api\Admin\InterestHobbyController;
use App\Http\Controllers\Api\Admin\PersonalityController;
use App\Http\Controllers\Api\Admin\ReligionController;

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

    // Interests & Hobbies Management
    Route::get('/interests', [InterestHobbyController::class, 'getInterests']);
    Route::get('/interests/types', [InterestHobbyController::class, 'getInterestTypes']);
    Route::post('/interests', [InterestHobbyController::class, 'createInterest']);
    Route::put('/interests/{id}', [InterestHobbyController::class, 'updateInterest']);
    Route::delete('/interests/{id}', [InterestHobbyController::class, 'deleteInterest']);
    Route::post('/interests/bulk-update-trending', [InterestHobbyController::class, 'bulkUpdateTrending']);

    // Personality Management
    Route::get('/personalities', [PersonalityController::class, 'getPersonalities']);
    Route::get('/personalities/types', [PersonalityController::class, 'getPersonalityTypes']);
    Route::post('/personalities', [PersonalityController::class, 'createPersonality']);
    Route::put('/personalities/{id}', [PersonalityController::class, 'updatePersonality']);
    Route::delete('/personalities/{id}', [PersonalityController::class, 'deletePersonality']);
    Route::post('/personalities/bulk-update-trending', [PersonalityController::class, 'bulkUpdateTrending']);

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
    Route::post('/mediator-promotions/{id}/payout', [AdminMediatorPromotionController::class, 'processPayout']);
    Route::delete('/mediator-promotions/{id}', [AdminMediatorPromotionController::class, 'destroy']);

    // Religion Management
    Route::get('/religions', [ReligionController::class, 'getReligions']);
    Route::post('/religions', [ReligionController::class, 'createReligion']);
    Route::put('/religions/{id}', [ReligionController::class, 'updateReligion']);
    Route::delete('/religions/{id}', [ReligionController::class, 'deleteReligion']);

    // Caste Management
    Route::get('/castes', [ReligionController::class, 'getCastes']);
    Route::post('/castes', [ReligionController::class, 'createCaste']);
    Route::put('/castes/{id}', [ReligionController::class, 'updateCaste']);
    Route::delete('/castes/{id}', [ReligionController::class, 'deleteCaste']);

    // SubCaste Management
    Route::get('/sub-castes', [ReligionController::class, 'getSubCastes']);
    Route::post('/sub-castes', [ReligionController::class, 'createSubCaste']);
    Route::put('/sub-castes/{id}', [ReligionController::class, 'updateSubCaste']);
    Route::delete('/sub-castes/{id}', [ReligionController::class, 'deleteSubCaste']);
});
