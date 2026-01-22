<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\Payment;

class SubscriptionController extends Controller
{
    /**
     * Get all available subscription plans
     */
    public function index()
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return response()->json([
            'plans' => $plans
        ]);
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe($planId, Request $request)
    {
        $user = $request->user();
        $plan = SubscriptionPlan::find($planId);

        if (!$plan) {
            return response()->json([
                'error' => 'Plan not found'
            ], 404);
        }

        // Check if user already has an active subscription
        $currentSubscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($currentSubscription) {
            return response()->json([
                'error' => 'You already have an active subscription'
            ], 400);
        }

        // Calculate end date
        $startDate = now();
        $endDate = $startDate->copy()->addDays($plan->duration_days);

        $userSubscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active'
        ]);

        // In a real implementation, you would process payment here
        // For now, we'll just create a payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $userSubscription->id,
            'amount' => $plan->price,
            'payment_method' => 'card', // This would come from payment gateway
            'transaction_id' => 'TXN_' . uniqid(),
            'status' => 'completed'
        ]);

        $userSubscription->update([
            'payment_id' => $payment->transaction_id,
            'amount_paid' => $plan->price
        ]);

        return response()->json([
            'message' => 'Subscription successful',
            'subscription' => $userSubscription->load(['plan'])
        ]);
    }

    /**
     * Get user's current subscription
     */
    public function mySubscription(Request $request)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_id', $user->id)
            ->with('plan')
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'No subscription found'
            ]);
        }

        return response()->json([
            'subscription' => $subscription
        ]);
    }
}
