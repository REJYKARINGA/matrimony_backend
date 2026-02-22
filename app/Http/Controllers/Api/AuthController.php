<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\PasswordResetToken;
use App\Models\Reference;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,staff,user,mediator',
            // Optional: a 6-letter mediator reference code
            'reference_code' => 'nullable|string|size:6|exists:users,reference_code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
            'status' => 'active',
            'email_verified' => false,
        ]);

        // If a reference code was provided, link this user to the mediator
        if ($request->filled('reference_code')) {
            $referrer = User::where('reference_code', $request->reference_code)->first();
            if ($referrer) {
                Reference::create([
                    'referenced_by_id' => $referrer->id,
                    'referenced_user_id' => $user->id,
                    'reference_code' => $request->reference_code,
                    'reference_type' => $referrer->role, // e.g. 'mediator'
                    'purchased_count' => 0,
                ]);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
            'has_profile' => false
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Invalid credentials'
            ], 401);
        }

        // Update last login
        $user->update([
            'last_login' => now()
        ]);

        $user->load(['userProfile', 'familyDetails', 'preferences', 'profilePhotos', 'verification', 'primaryBankAccount', 'interests', 'personalities']);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'has_profile' => $user->userProfile()->exists()
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // Check if user is authenticated before trying to log out
        if (!$request->user()) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        // Revoke the token that was used to authenticate the current request
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function getUser(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        // Check if user is soft deleted
        if ($user->trashed()) {
            return response()->json([
                'error' => 'Account has been deleted'
            ], 403);
        }

        // Update last login whenever the app is opened/user info is fetched
        $user->update(['last_login' => now()]);

        $user->load(['userProfile', 'familyDetails', 'preferences', 'profilePhotos', 'verification', 'primaryBankAccount', 'interests', 'personalities']);

        return response()->json([
            'user' => $user,
            'has_profile' => $user->userProfile()->exists()
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        // Update the password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Update user information (email and phone)
     */
    public function updateInfo(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        // Validate the input
        $validator = \Validator::make($request->all(), [
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:255|unique:users,phone,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // Update user information
        $user->update($request->only(['email', 'phone']));

        return response()->json([
            'message' => 'User information updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete user account (soft delete)
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        // Soft delete the user
        $user->delete();

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * Send password reset OTP to user's email
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Generate a 6-digit numeric OTP
        $otp = rand(100000, 999999);

        // Create or update password reset token
        $resetToken = PasswordResetToken::updateOrCreate(
            ['email' => $request->email],
            [
                'token' => $otp,
                'expires_at' => now()->addMinutes(30), // OTP expires in 30 minutes
                'used_at' => null
            ]
        );

        // Send OTP to user's email
        try {
            Mail::send('emails.password-reset', ['otp' => $otp], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Password Reset OTP - ' . config('app.name'));
            });

            // For development/testing purposes, we'll also return the OTP in the response
            // In production, you should remove the 'otp' field from the response
            return response()->json([
                'message' => 'OTP sent to your email',
                'otp' => env('APP_ENV') === 'local' ? $otp : null // Only show OTP in local environment
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Failed to send password reset email: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to send OTP email. Please try again later.',
                'message' => env('APP_DEBUG') ? $e->getMessage() : 'Email service error',
                'otp' => env('APP_ENV') === 'local' ? $otp : null // Still return OTP in local for testing
            ], 500);
        }
    }

    /**
     * Verify the OTP sent to user's email
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|min:6|max:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // Find the password reset token
        $resetToken = PasswordResetToken::where('email', $request->email)
            ->where('token', $request->otp)
            ->valid()
            ->first();

        if (!$resetToken) {
            return response()->json([
                'error' => 'Invalid or expired OTP'
            ], 400);
        }

        // Mark the token as verified (but not used yet)
        $resetToken->update(['verified_at' => now()]);

        return response()->json([
            'message' => 'OTP verified successfully'
        ]);
    }

    /**
     * Reset user's password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // Find the verified password reset token
        $resetToken = PasswordResetToken::where('email', $request->email)
            ->whereNotNull('verified_at')
            ->valid()
            ->first();

        if (!$resetToken) {
            return response()->json([
                'error' => 'Invalid or expired reset token. Please request a new OTP.'
            ], 400);
        }

        // Find the user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        // Update the user's password
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Mark the reset token as used
        $resetToken->update(['used_at' => now()]);

        return response()->json([
            'message' => 'Password reset successfully'
        ]);
    }
}
