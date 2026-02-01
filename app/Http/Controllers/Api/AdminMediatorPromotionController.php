<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MediatorPromotion;
use Illuminate\Support\Facades\Validator;

class AdminMediatorPromotionController extends Controller
{
    /**
     * Get all promotions
     */
    public function index()
    {
        $promotions = MediatorPromotion::with(['user', 'setting'])->orderBy('created_at', 'desc')->get();
        return response()->json([
            'promotions' => $promotions
        ]);
    }

    /**
     * Update promotion (status, counts, payout)
     */
    public function update(Request $request, $id)
    {
        $promotion = MediatorPromotion::with('setting')->find($id);

        if (!$promotion) {
            return response()->json(['error' => 'Promotion not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'views_count' => 'sometimes|integer|min:0',
            'likes_count' => 'sometimes|integer|min:0',
            'comments_count' => 'sometimes|integer|min:0',
            'status' => 'sometimes|string|in:pending,verified,paid,rejected',
            'calculated_payout' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        // Update basic fields - strip commas in case they come from formatted inputs
        if ($request->has('views_count')) {
            $val = str_replace(',', '', (string) $request->views_count);
            $promotion->views_count = intval($val);
        }
        if ($request->has('likes_count')) {
            $val = str_replace(',', '', (string) $request->likes_count);
            $promotion->likes_count = intval($val);
        }
        if ($request->has('comments_count')) {
            $val = str_replace(',', '', (string) $request->comments_count);
            $promotion->comments_count = intval($val);
        }
        if ($request->has('status'))
            $promotion->status = $request->status;

        // Auto-calculate payout if counts changed and setting exists
        // Only if admin didn't explicitly provide a payout amount this request? 
        // Or always auto-calc unless overridden?
        // Let's auto-calc if counts provided.
        if (($request->has('views_count') || $request->has('likes_count') || $request->has('comments_count')) && $promotion->setting) {
            $setting = $promotion->setting;

            if ($setting->views_required > 0) {
                $viewsMultiplier = floor($promotion->views_count / $setting->views_required);

                $finalMultiplier = $viewsMultiplier;

                if ($setting->is_likes_enabled && $setting->likes_required > 0) {
                    $likesMultiplier = floor($promotion->likes_count / $setting->likes_required);
                    $finalMultiplier = min($finalMultiplier, $likesMultiplier);
                }

                if ($setting->is_comments_enabled && $setting->comments_required > 0) {
                    $commentsMultiplier = floor($promotion->comments_count / $setting->comments_required);
                    $finalMultiplier = min($finalMultiplier, $commentsMultiplier);
                }

                $totalEarned = $finalMultiplier * $setting->payout_amount;
                // Pending payout is total earned minus what has already been paid
                $promotion->calculated_payout = max(0, $totalEarned - $promotion->total_paid_amount);
            }
        }

        // Manual override of payout
        if ($request->has('calculated_payout')) {
            $promotion->calculated_payout = $request->calculated_payout;
        }

        if ($request->has('status') && $request->status === 'paid') {
            // When marking as paid, add the pending calculated_payout to total_paid_amount
            $promotion->total_paid_amount += $promotion->calculated_payout;
            $promotion->calculated_payout = 0;
            $promotion->paid_at = now();
        }

        $promotion->save();

        return response()->json([
            'message' => 'Promotion updated successfully',
            'promotion' => $promotion
        ]);
    }

    /**
     * Delete promotion
     */
    public function destroy($id)
    {
        $promotion = MediatorPromotion::find($id);

        if (!$promotion) {
            return response()->json(['error' => 'Promotion not found'], 404);
        }

        $promotion->delete();

        return response()->json([
            'message' => 'Promotion deleted successfully'
        ]);
    }

    /**
     * Process Payout via Razorpay
     */
    public function processPayout($id)
    {
        $promotion = MediatorPromotion::with('user')->find($id);

        if (!$promotion) {
            return response()->json(['error' => 'Promotion not found'], 404);
        }

        if ($promotion->calculated_payout <= 0) {
            return response()->json(['error' => 'No pending payout amount'], 400);
        }

        $user = $promotion->user;
        if (!$user) {
            return response()->json(['error' => 'Mediator user not found'], 404);
        }

        // Check if Razorpay Payouts is enabled
        if (!config('services.razorpay.key_id')) {
            return response()->json(['error' => 'Razorpay Payouts is not configured'], 500);
        }

        try {
            $payoutService = new \App\Services\RazorpayPayoutService();

            $bankAccount = $user->primaryBankAccount;

            if (!$bankAccount) {
                return response()->json(['error' => 'Mediator has not provided bank details'], 400);
            }

            // Ensure Fund Account exists
            if (!$bankAccount->razorpay_fund_account_id) {
                // Create Contact & Fund Account on the fly
                $contact = $payoutService->createContact(
                    $user->name ?: 'Mediator',
                    $user->email,
                    $user->phone ?: '9999999999',
                    'user_' . $user->id
                );

                $fundAccount = $payoutService->createFundAccount(
                    $contact->id,
                    $bankAccount->account_name,
                    $bankAccount->account_number,
                    $bankAccount->ifsc_code
                );

                $bankAccount->razorpay_fund_account_id = $fundAccount->id;
                $bankAccount->save();
            }

            // Create Payout
            $payout = $payoutService->createPayout(
                $bankAccount->razorpay_fund_account_id,
                $promotion->calculated_payout,
                'INR',
                'IMPS',
                'payout',
                'Payout for Promotion #' . $promotion->id
            );

            // If we are here, payout request accepted
            // Update promotion status locally
            // Ideally we should wait for webhook, but for now mark as paid
            $promotion->total_paid_amount += $promotion->calculated_payout;
            $promotion->calculated_payout = 0;
            $promotion->status = 'paid';
            $promotion->paid_at = now();
            $promotion->save();

            return response()->json([
                'message' => 'Payout initiated successfully',
                'payout_id' => $payout->id,
                'promotion' => $promotion
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Payout Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payout failed: ' . $e->getMessage()], 500);
        }
    }
}
