<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\MatchingController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\EngagementPosterController;
use App\Http\Controllers\Api\SuggestionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/user', [AuthController::class, 'getUser']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);
    Route::put('auth/update-info', [AuthController::class, 'updateInfo']);
    Route::delete('auth/delete-account', [AuthController::class, 'deleteAccount']);

    Route::apiResource('users', UserController::class);

    // Additional user routes
    Route::post('users/{userId}/block', [UserController::class, 'blockUser']);
    Route::delete('users/{userId}/block', [UserController::class, 'unblockUser']);
    Route::get('users/blocked', [UserController::class, 'getBlockedUsers']);

    // Profile routes
    Route::prefix('profiles')->group(function () {
        Route::get('/my', [ProfileController::class, 'myProfile']);
        Route::put('/my', [ProfileController::class, 'updateMyProfile']);
        Route::put('/family', [ProfileController::class, 'updateFamilyDetails']);
        Route::put('/preferences', [ProfileController::class, 'updatePreferences']);
        Route::get('/photos', [ProfileController::class, 'getProfilePhotos']);
        Route::post('/photos', [ProfileController::class, 'uploadProfilePhoto']);
        Route::put('/photos/{photoId}/primary', [ProfileController::class, 'setPrimaryPhoto']);
        Route::delete('/photos/{photoId}', [ProfileController::class, 'deleteProfilePhoto']);
        Route::get('/{id}', [ProfileController::class, 'show']);
        Route::get('/', [ProfileController::class, 'index']);
    });

    // Matching routes
    Route::prefix('matching')->group(function () {
        Route::get('/suggestions', [MatchingController::class, 'getSuggestions']);
        Route::post('/match/{userId}', [MatchingController::class, 'createMatch']);
        Route::get('/matches', [MatchingController::class, 'getMatches']);
        Route::post('/interest/{userId}', [MatchingController::class, 'sendInterest']);
        Route::get('/interests/sent', [MatchingController::class, 'getSentInterests']);
        Route::get('/interests/received', [MatchingController::class, 'getReceivedInterests']);
    });

    // Messaging routes
    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index']);
        Route::post('/', [MessageController::class, 'store']);
        Route::get('/{userId}', [MessageController::class, 'getMessagesWithUser']);
        Route::put('/{id}/read', [MessageController::class, 'markAsRead']);
    });

    // Subscription routes
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/subscribe/{planId}', [SubscriptionController::class, 'subscribe']);
        Route::get('/my', [SubscriptionController::class, 'mySubscription']);
    });

    // Engagement Poster routes
    Route::apiResource('engagement-posters', EngagementPosterController::class);

    // Suggestion routes
    Route::apiResource('suggestions', SuggestionController::class);
});