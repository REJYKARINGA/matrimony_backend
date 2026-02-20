<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MediatorPromotion;
use App\Models\PromotionSetting;
use App\Models\Transaction;
use App\Models\Reference;
use App\Services\SocialMediaStatsService;
use Illuminate\Support\Facades\Validator;
use App\Services\RazorpayPayoutService;
use Illuminate\Support\Facades\Log;

class MediatorPromotionController extends Controller
{
    protected $statsService;

    public function __construct(SocialMediaStatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    /**
     * Get my submitted promotions
     */
    public function index(Request $request)
    {
        $promotions = MediatorPromotion::where('user_id', $request->user()->id)
            ->with('setting')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'promotions' => $promotions
        ]);
    }

    /**
     * Submit a new promotion
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|max:255',
            'link' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        // Get default setting
        $setting = PromotionSetting::where('is_default', true)->first();

        // Fetch stats from platform
        $stats = null;
        if ($request->platform === 'youtube') {
            $stats = $this->statsService->fetchYouTubeStats($request->link);
        } elseif ($request->platform === 'instagram') {
            $stats = $this->statsService->fetchInstagramStats($request->link);
        }

        $promotion = MediatorPromotion::create([
            'user_id' => $request->user()->id,
            'promotion_setting_id' => $setting ? $setting->id : null,
            'platform' => $request->platform,
            'link' => $request->link,
            'username' => $request->username,
            'status' => 'pending',
            'views_count' => $stats['views'] ?? 0,
            'likes_count' => $stats['likes'] ?? 0,
            'comments_count' => $stats['comments'] ?? 0,
            'total_paid_amount' => 0,
            'calculated_payout' => 0,
        ]);

        // Calculate payout if stats were fetched
        if ($stats && $setting) {
            $this->calculatePayout($promotion, $setting);
        }

        return response()->json([
            'message' => 'Promotion submitted successfully',
            'promotion' => $promotion->load('setting'),
            'stats_fetched' => $stats !== null
        ], 201);
    }

    private function calculatePayout($promotion, $setting)
    {
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

            // Calculate pending payout (Total earned - what has already been paid)
            $promotion->calculated_payout = max(0, $totalEarned - $promotion->total_paid_amount);
            $promotion->save();
        }
    }

    /**
     * Get all bank accounts for the merchant
     */
    public function getBankAccounts(Request $request)
    {
        return response()->json([
            'bank_accounts' => $request->user()->bankAccounts()->orderBy('is_primary', 'desc')->get()
        ]);
    }

    /**
     * Add a new bank account
     */
    public function addBankAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_name' => 'required|string',
            'account_number' => 'required|string',
            'ifsc_code' => 'required|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'is_primary' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $user = $request->user();
        $razorpayFundAccountId = null;

        // Register with RazorpayX if service enabled
        if (config('services.razorpay.key_id')) {
            try {
                $payoutService = new RazorpayPayoutService();
                $contact = $payoutService->createContact(
                    $user->name ?: 'Mediator',
                    $user->email,
                    $user->phone ?: '9999999999',
                    'user_' . $user->id
                );
                $fundAccount = $payoutService->createFundAccount(
                    $contact->id,
                    $request->account_name,
                    $request->account_number,
                    $request->ifsc_code
                );
                $razorpayFundAccountId = $fundAccount->id;
            } catch (\Exception $e) {
                Log::error('Failed to register Razorpay Fund Account: ' . $e->getMessage());
            }
        }

        // If this is the first account, make it primary
        $isFirst = $user->bankAccounts()->count() === 0;
        $shouldBePrimary = $request->is_primary || $isFirst;

        if ($shouldBePrimary) {
            $user->bankAccounts()->update(['is_primary' => false]);
        }

        $bankAccount = $user->bankAccounts()->create([
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'razorpay_fund_account_id' => $razorpayFundAccountId,
            'is_primary' => $shouldBePrimary
        ]);

        return response()->json([
            'message' => 'Bank account added successfully',
            'bank_account' => $bankAccount
        ]);
    }

    /**
     * Set a bank account as primary
     */
    public function setPrimaryBankAccount(Request $request, $id)
    {
        $user = $request->user();
        $account = $user->bankAccounts()->findOrFail($id);

        $user->bankAccounts()->update(['is_primary' => false]);
        $account->update(['is_primary' => true]);

        return response()->json([
            'message' => 'Primary bank account updated',
            'bank_account' => $account
        ]);
    }

    /**
     * Delete a bank account
     */
    public function deleteBankAccount(Request $request, $id)
    {
        $user = $request->user();
        $account = $user->bankAccounts()->findOrFail($id);

        if ($account->is_primary) {
            return response()->json(['error' => 'Cannot delete primary bank account'], 422);
        }

        $account->delete();

        return response()->json([
            'message' => 'Bank account deleted successfully'
        ]);
    }

    /**
     * Request payout for all eligible promotions
     */
    public function requestPayout(Request $request)
    {
        $user = $request->user();

        // 1. Check for primary bank account
        $primaryAccount = $user->bankAccounts()->where('is_primary', true)->first();
        if (!$primaryAccount) {
            return response()->json(['error' => 'No primary bank account found. Please add one in Bank Accounts tab.'], 422);
        }

        // 2. Recalculate all payouts to ensure DB is in sync with raw stats
        $allPromotions = MediatorPromotion::where('user_id', $user->id)
            ->with('setting')
            ->get();

        foreach ($allPromotions as $promo) {
            if ($promo->setting) {
                $this->calculatePayout($promo, $promo->setting);
            }
        }

        // 3. Get total withdrawable amount from promotions
        $promotions = MediatorPromotion::where('user_id', $user->id)
            ->where('calculated_payout', '>', 0)
            ->get();

        $promoPayable = $promotions->sum('calculated_payout');

        // 4. Get total referral earnings (₹20 per unlock)
        $rewardPerPurchase = 20;
        $referrals = Reference::where('referenced_by_id', $user->id)->get();
        $referralPayable = 0;
        $payableReferrals = [];

        foreach ($referrals as $ref) {
            $earned = $ref->purchased_count * $rewardPerPurchase;
            $pending = max(0, $earned - ($ref->total_paid_amount ?? 0));
            if ($pending > 0) {
                $referralPayable += $pending;
                $payableReferrals[] = [
                    'model' => $ref,
                    'amount' => $pending
                ];
            }
        }

        $totalPayable = $promoPayable + $referralPayable;

        if ($totalPayable <= 0) {
            return response()->json(['error' => 'No pending payouts available.'], 422);
        }

        $payoutId = null;
        $status = 'pending';

        // 4. Initiate RazorpayX Payout if config exist
        if (config('services.razorpay.key_id')) {
            try {
                $payoutService = new RazorpayPayoutService();

                // Ensure Fund Account exists
                if (!$primaryAccount->razorpay_fund_account_id) {
                    $contact = $payoutService->createContact(
                        $user->name ?: 'Mediator',
                        $user->email,
                        $user->phone ?: '9999999999',
                        'user_' . $user->id
                    );
                    $fundAccount = $payoutService->createFundAccount(
                        $contact->id,
                        $primaryAccount->account_name,
                        $primaryAccount->account_number,
                        $primaryAccount->ifsc_code
                    );
                    $primaryAccount->razorpay_fund_account_id = $fundAccount->id;
                    $primaryAccount->save();
                }

                $payout = $payoutService->createPayout(
                    $primaryAccount->razorpay_fund_account_id,
                    $totalPayable,
                    'INR',
                    'IMPS',
                    'payout',
                    'Payout for User ' . $user->id
                );

                $payoutId = $payout->id;
                $status = 'success';

            } catch (\Exception $e) {
                Log::error('Razorpay Payout Failed: ' . $e->getMessage());
                return response()->json(['error' => 'Razorpay payout failed: ' . $e->getMessage()], 500);
            }
        }

        // Record in Transactions
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'payout',
            'amount' => $totalPayable,
            'status' => $status,
            'description' => 'Payout to ' . $primaryAccount->account_number . ($payoutId ? " (ID: $payoutId)" : " (Manual)"),
            'razorpay_payment_id' => $payoutId
        ]);

        // 6. Update promotions
        foreach ($promotions as $promo) {
            $promo->total_paid_amount += $promo->calculated_payout;
            $promo->calculated_payout = 0;
            if ($status === 'success') {
                $promo->status = 'paid';
                $promo->paid_at = now();
            }
            $promo->save();
        }

        // 7. Update references
        foreach ($payableReferrals as $item) {
            $ref = $item['model'];
            $ref->total_paid_amount += $item['amount'];
            $ref->save();
        }

        return response()->json([
            'message' => $status === 'success' ? 'Payout initiated successfully' : 'Payout request submitted for manual processing',
            'amount' => $totalPayable,
            'status' => $status
        ]);
    }
}
