<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\MatchingController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\EngagementPosterController;
use App\Http\Controllers\Api\SuggestionController;
use App\Http\Controllers\Api\ShortlistController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileViewController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PreferenceController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\ReferenceController;
use App\Events\TestEvent;

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
Route::prefix('auth')->middleware('throttle:6,2')->group(function () {
    Route::get('/test-broadcast', function () {
        broadcast(new TestEvent('Hello from Reverb!'));
        return response()->json(['message' => 'Event broadcasted!']);
    });
    Route::post('register', [AuthController::class, 'register']);

    // Strict limits for sensitive actions: 5 attempts per 5 minutes
    Route::middleware('throttle:5,5')->post('login', [AuthController::class, 'login']);
    Route::middleware('throttle:5,5')->post('forgot-password', [AuthController::class, 'forgotPassword']);

    // Extremely strict limits for OTP: 3 attempts per 5 minutes to stop brute-force
    Route::middleware('throttle:3,5')->post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::middleware('throttle:3,5')->post('reset-password', [AuthController::class, 'resetPassword']);
});

// Public: validate a reference code before registration (no auth needed)
Route::get('references/validate/{code}', [ReferenceController::class, 'validateCode']);

// Image proxy route to bypass CORS for Flutter Web
Route::middleware('throttle:60,2')->get('images/proxy', function (Request $request) {
    $path = $request->query('path');
    if (!$path)
        return response()->json(['error' => 'Path is required'], 400);

    // Clean the path
    $path = str_replace(['http://localhost:8000/storage/', '/storage/'], '', $path);

    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['error' => 'File not found: ' . $path], 404);
    }

    $file = Storage::disk('public')->get($path);
    $type = Storage::mimeType(storage_path('app/public/' . $path));

    return response($file)->header('Content-Type', $type);
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
        Route::get('/visitors', [ProfileViewController::class, 'getVisitors']);
        Route::get('/visited', [ProfileViewController::class, 'getVisitedProfiles']);
        Route::get('/contact-viewed', [ProfileViewController::class, 'getContactViewed']);
        Route::post('/{id}/view', [ProfileViewController::class, 'recordView']);
        Route::get('/{id}', [ProfileController::class, 'show'])->middleware('throttle:10,1');
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
        Route::post('/interest/{interestId}/accept', [MatchingController::class, 'acceptInterest']);
        Route::post('/interest/{interestId}/reject', [MatchingController::class, 'rejectInterest']);
    });

    // Shortlist routes
    Route::prefix('shortlist')->group(function () {
        Route::get('/', [ShortlistController::class, 'index']);
        Route::post('/', [ShortlistController::class, 'store']);
        Route::delete('/{shortlistedUserId}', [ShortlistController::class, 'destroy']);
        Route::get('/check/{shortlistedUserId}', [ShortlistController::class, 'check']);
    });

    // Messaging routes
    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index']);
        Route::get('/unread-count', [MessageController::class, 'getUnreadCount']);
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

    // Search routes
    Route::prefix('search')->group(function () {
        Route::get('/preference-matches', [SearchController::class, 'getPreferenceMatches']);
        Route::post('/log-click', [SearchController::class, 'logDiscoveryClick']);
        Route::get('/', [SearchController::class, 'search']);
    });

    // Location routes
    Route::prefix('location')->group(function () {
        Route::post('/update', [LocationController::class, 'updateLocation']);
        Route::get('/nearby', [LocationController::class, 'getNearbyUsers']);
    });

    // Suggestion routes
    Route::apiResource('suggestions', SuggestionController::class);

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Payment routes
    Route::prefix('payment')->group(function () {
        Route::get('/wallet/balance', [App\Http\Controllers\Api\PaymentController::class, 'getWalletBalance']);
        Route::post('/create-order', [App\Http\Controllers\Api\PaymentController::class, 'createOrder']);
        Route::post('/verify', [App\Http\Controllers\Api\PaymentController::class, 'verifyPayment']);
        Route::post('/unlock-contact-wallet', [App\Http\Controllers\Api\PaymentController::class, 'unlockContactWithWallet']);
        Route::get('/check-unlock/{userId}', [App\Http\Controllers\Api\PaymentController::class, 'checkContactUnlock']);
        Route::get('/transactions', [App\Http\Controllers\Api\PaymentController::class, 'getTransactionHistory']);
        Route::get('/today-unlock-count', [App\Http\Controllers\Api\PaymentController::class, 'getTodayUnlockCount']);
    });

    // Verification routes
    Route::prefix('verification')->group(function () {
        Route::post('/submit', [VerificationController::class, 'submitVerification']);
        Route::get('/status', [VerificationController::class, 'getStatus']);
    });

    // Preference options routes
    Route::prefix('preferences')->group(function () {
        Route::get('/education-options', [PreferenceController::class, 'getEducationOptions']);
        Route::get('/occupation-options', [PreferenceController::class, 'getOccupationOptions']);
        Route::get('/religion-options', [PreferenceController::class, 'getReligionOptions']);
        Route::get('/all-options', [PreferenceController::class, 'getAllOptions']);
    });

    // Reference / Referral routes
    Route::prefix('references')->group(function () {
        // Every logged-in user can see their own reference code
        Route::get('/my-code', [ReferenceController::class, 'myCode']);
        // Mediators: see who registered with their code + purchase counts
        Route::get('/my-referrals', [ReferenceController::class, 'myReferrals']);
        // Regular users: see who referred them
        Route::get('/my-referrer', [ReferenceController::class, 'myReferrer']);
        // Mediator: manually add a referral
        Route::post('/add', [ReferenceController::class, 'addReferral']);
        // Admin: full list of all reference records
        Route::get('/', [ReferenceController::class, 'index']);
        Route::get('/{id}', [ReferenceController::class, 'show']);
    });
});
