<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackDailyUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || $user->role === 'admin')
            return $next($request);

        // Define routes that bypass the usage fee check (e.g., viewing public profiles)
        $excludedRoutes = [
            'api/profiles/*',
            'api/profile-views/*', // Potential extra routes to exclude
        ];

        foreach ($excludedRoutes as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $today = now()->toDateString();
        $now = now();

        // 0. Check if user has an active subscription (No expiry or within date)
        $hasActiveSubscription = \App\Models\UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', $now);
            })
            ->exists();

        if ($hasActiveSubscription) {
            return $next($request);
        }

        // 1. Check if user is "Active" (purchased >= 2 contacts in last 30 days)
        $recentUnlocks = \App\Models\ContactUnlock::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30));
        
        $purchaseCount = $recentUnlocks->count();
        $lastUnlock = \App\Models\ContactUnlock::where('user_id', $user->id)->latest()->first();
        
        $lastPurchaseInfo = $lastUnlock 
            ? "Last contact purchased on " . $lastUnlock->created_at->format('d M Y') 
            : "Registered on " . $user->created_at->format('d M Y') . " (No contacts purchased yet)";

        if ($purchaseCount >= 2) {
            return $next($request);
        }

        // 2. Check for recharge grace period (30 days after last success recharge) OR New user grace period (30 days after registration)
        $lastRecharge = \App\Models\Transaction::where('user_id', $user->id)
            ->where('type', 'wallet_recharge')
            ->where('status', 'success')
            ->latest()
            ->first();

        $isNewUser = $user->created_at->diffInDays(now()) <= 30;

        if ($isNewUser || ($lastRecharge && $lastRecharge->created_at->diffInDays(now()) <= 30)) {
            return $next($request);
        }

        // Passive user, subject to usage fees
        $wallet = \App\Models\Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        if ($user->last_hit_date != $today) {
            // New day - first entry deduction
            if ($wallet->balance < 1) {
                return response()->json([
                    'error' => 'insufficient_balance',
                    'message' => 'Insufficient wallet balance. Recharge now to continue viewing profiles and unlocking contacts.',
                    'last_purchase_at' => $lastUnlock ? $lastUnlock->created_at : null,
                    'required_recharge' => true
                ], 403);
            }

            $wallet->decrement('balance', 1);
            $user->last_hit_date = $today;
            $user->daily_hits_count = 1;
            $user->last_usage_deduction_at = $today;
            $user->save();

            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'type' => 'usage_fee',
                'amount' => 1,
                'status' => 'success',
                'description' => 'Daily usage fee (Passive user)'
            ]);
        } else {
            // Same day entry
            $user->daily_hits_count++;

            // Check if specifically reaching 21 hits
            if ($user->daily_hits_count == 21) {
                if ($wallet->balance < 1) {
                    return response()->json([
                        'error' => 'insufficient_balance',
                        'message' => 'Daily activity limit reached. Recharge your wallet to continue exploring matches.',
                        'last_purchase_at' => $lastUnlock ? $lastUnlock->created_at : null,
                        'required_recharge' => true
                    ], 403);
                }

                $wallet->decrement('balance', 1);

                \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'usage_fee',
                    'amount' => 1,
                    'status' => 'success',
                    'description' => 'High activity daily fee (>20 hits)'
                ]);
            }
            $user->save();
        }

        return $next($request);
    }
}
