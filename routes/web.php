<?php

use Illuminate\Support\Facades\Route;

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
    return view('welcome');
});

// Test email route - Remove this in production
Route::get('/test-email', function () {
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