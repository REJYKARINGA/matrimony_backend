<?php

use Illuminate\Support\Facades\Route;
use App\Events\TestEvent;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    broadcast(new TestEvent('Homepage visited! Real-time working!'));
    return view('welcome');
});

// Web payment page for wallet recharge (opened from app via browser)
Route::get('/recharge', function (\Illuminate\Http\Request $request) {
    $orderId = $request->query('order_id');
    $amount = $request->query('amount');
    $type = $request->query('type', 'wallet_recharge');
    $token = $request->query('token');
    $transactionId = $request->query('transaction_id');
    $unlockedUserId = $request->query('unlocked_user_id');
    $razorpayKey = env('RAZORPAY_KEY');
    $apiBase = url('/api');

    if (!$orderId || !$amount || !$token || !$transactionId) {
        return response()->json(['error' => 'Missing required parameters'], 400);
    }

    return view('payment.recharge', compact(
        'orderId', 'amount', 'type', 'token', 'transactionId',
        'unlockedUserId', 'razorpayKey', 'apiBase'
    ));
});

// Public profile view by matrimony ID
Route::get('/profile/{matrimonyId}', function ($matrimonyId) {
    $user = \App\Models\User::where('matrimony_id', $matrimonyId)->with('userProfile')->first();
    if (!$user || !$user->userProfile) {
        return response()->json(['error' => 'Profile not found'], 404);
    }
    $profile = $user->userProfile;
    return response()->json([
        'id' => $user->matrimony_id,
        'name' => trim("{$profile->first_name} {$profile->last_name}"),
        'age' => $profile->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->age : null,
        'gender' => $profile->gender,
        'city' => $profile->city,
        'state' => $profile->state,
        'religion' => $profile->religionModel?->name,
        'caste' => $profile->casteModel?->name,
        'education' => $profile->educationModel?->name,
        'occupation' => $profile->occupationModel?->name,
    ]);
});

// Test email route - Remove this in production
Route::middleware('throttle:2,2')->get('/test-email', function () {
    try {
        $testOtp = '123456';

        \Mail::send('emails.password-reset', ['otp' => $testOtp], function ($message) {
            $message->to('rejy1@yopmail.com')
                ->subject('Test Password Reset OTP - ' . config('app.name'));
        });

        return response()->json([
            'success' => true,
            'message' => 'Test email sent successfully to rejy1@yopmail.com',
            'otp' => $testOtp
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});