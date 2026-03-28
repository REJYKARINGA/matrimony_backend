<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\ContactUnlock;
use App\Models\Transaction;
use App\Models\Reference;
use App\Models\User;
use App\Models\WalletTransferOtp;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    private $razorpayKey;
    private $razorpaySecret;

    public function __construct()
    {
        $this->razorpayKey = env('RAZORPAY_KEY');
        $this->razorpaySecret = env('RAZORPAY_SECRET');
    }

    public function getWalletBalance(Request $request)
    {
        $user = $request->user();
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        return response()->json([
            'balance' => $wallet->balance
        ]);
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:' . ($request->type === 'wallet_recharge' ? 199 : 1),
            'type' => 'required|in:wallet_recharge,contact_unlock',
            'unlocked_user_id' => 'required_if:type,contact_unlock|exists:users,id'
        ]);

        $user = $request->user();
        $amount = $request->amount * 100; // Convert to paise

        try {
            $api = new Api($this->razorpayKey, $this->razorpaySecret);

            $orderData = [
                'receipt' => 'order_' . time(),
                'amount' => $amount,
                'currency' => 'INR'
            ];

            $razorpayOrder = $api->order->create($orderData);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'razorpay_order_id' => $razorpayOrder['id'],
                'type' => $request->type,
                'amount' => $request->amount,
                'status' => 'pending',
                'description' => $request->type === 'wallet_recharge'
                    ? 'Wallet recharge'
                    : 'Contact unlock for user #' . $request->unlocked_user_id
            ]);

            return response()->json([
                'order_id' => $razorpayOrder['id'],
                'amount' => $request->amount,
                'key' => $this->razorpayKey,
                'transaction_id' => $transaction->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create order',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
            'transaction_id' => 'required|exists:transactions,id',
            'unlocked_user_id' => 'nullable|exists:users,id'
        ]);

        $user = $request->user();
        $transaction = Transaction::find($request->transaction_id);

        try {
            $api = new Api($this->razorpayKey, $this->razorpaySecret);

            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            $api->utility->verifyPaymentSignature($attributes);

            $transaction->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
                'status' => 'success'
            ]);

            if ($transaction->type === 'wallet_recharge') {
                $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
                $wallet->increment('balance', (float) $transaction->amount);
            } else {
                ContactUnlock::create([
                    'user_id' => $user->id,
                    'unlocked_user_id' => $request->unlocked_user_id,
                    'amount_paid' => $transaction->amount,
                    'payment_method' => 'direct'
                ]);

                // Increment purchased_count for the mediator who brought in the profile being unlocked
                $reference = Reference::where('referenced_user_id', $request->unlocked_user_id)->first();
                if ($reference) {
                    $reference->increment('purchased_count');
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'type' => $transaction->type
            ]);

        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed']);

            return response()->json([
                'error' => 'Payment verification failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function unlockContactWithWallet(Request $request)
    {
        $request->validate([
            'unlocked_user_id' => 'required|exists:users,id'
        ]);

        $user = $request->user();

        // Check daily unlock limit (configurable, default 20 per day)
        $dailyLimit = config('services.daily_unlock_limit', 20);
        $todayUnlocks = ContactUnlock::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayUnlocks >= $dailyLimit) {
            return response()->json([
                'error' => 'daily_limit_exceeded',
                'message' => "You have reached your daily limit of $dailyLimit contact unlocks. Please try again tomorrow.",
                'daily_limit' => $dailyLimit,
                'today_unlocks' => $todayUnlocks
            ], 403);
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet || $wallet->balance < 49) {
            return response()->json([
                'error' => 'Insufficient wallet balance'
            ], 400);
        }

        $alreadyUnlocked = ContactUnlock::where('user_id', $user->id)
            ->where('unlocked_user_id', $request->unlocked_user_id)
            ->exists();

        if ($alreadyUnlocked) {
            return response()->json([
                'error' => 'Contact already unlocked'
            ], 400);
        }

        $wallet->decrement('balance', 49);

        ContactUnlock::create([
            'user_id' => $user->id,
            'unlocked_user_id' => $request->unlocked_user_id,
            'amount_paid' => 49,
            'payment_method' => 'wallet'
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'type' => 'contact_unlock',
            'amount' => 49,
            'status' => 'success',
            'description' => 'Contact unlock for user #' . $request->unlocked_user_id . ' (Wallet)'
        ]);

        // Increment purchased_count for the mediator who brought in the profile being unlocked
        $reference = Reference::where('referenced_user_id', $request->unlocked_user_id)->first();
        if ($reference) {
            $reference->increment('purchased_count');
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact unlocked successfully',
            'remaining_balance' => $wallet->balance,
            'today_unlocks' => $todayUnlocks + 1,
            'daily_limit' => $dailyLimit
        ]);
    }

    public function checkContactUnlock(Request $request, $userId)
    {
        $user = $request->user();

        $unlocked = ContactUnlock::where('user_id', $user->id)
            ->where('unlocked_user_id', $userId)
            ->exists();

        return response()->json([
            'unlocked' => $unlocked
        ]);
    }

    public function getTransactionHistory(Request $request)
    {
        $user = $request->user();

        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // For contact_unlock transactions, attach the unlocked user's name & matrimony_id
        $transactions->getCollection()->transform(function ($tx) {
            if ($tx->type === 'contact_unlock') {
                // Extract the unlocked_user_id from the description or from contact_unlocks
                $contactUnlock = \App\Models\ContactUnlock::where('user_id', $tx->user_id)
                    ->whereDate('created_at', $tx->created_at->toDateString())
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($contactUnlock) {
                    $unlockedUser = \App\Models\User::with('userProfile:id,user_id,first_name,last_name')
                        ->select('id', 'matrimony_id')
                        ->find($contactUnlock->unlocked_user_id);

                    if ($unlockedUser) {
                        $tx->unlocked_user = [
                            'id' => $unlockedUser->id,
                            'matrimony_id' => $unlockedUser->matrimony_id,
                            'first_name' => $unlockedUser->userProfile->first_name ?? null,
                            'last_name' => $unlockedUser->userProfile->last_name ?? null,
                        ];
                    }
                }
            }
            return $tx;
        });

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    public function getTodayUnlockCount(Request $request)
    {
        $user = $request->user();

        $count = ContactUnlock::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return response()->json([
            'count' => $count,
            'daily_limit' => config('services.daily_unlock_limit', 20),
            'remaining' => config('services.daily_unlock_limit', 20) - $count
        ]);
    }

    public function searchUser(Request $request)
    {
        $request->validate(['query' => 'required|string|min:3']);
        $query = $request->input('query');

        $users = User::withoutGlobalScope('active')
            ->where(function($q) use ($query) {
                $q->where('matrimony_id', 'LIKE', "%$query%")
                  ->orWhere('phone', 'LIKE', "%$query%");
            })
            ->with('userProfile:user_id,first_name,last_name')
            ->select('id', 'matrimony_id', 'phone', 'role')
            ->limit(5)
            ->get()
            ->map(function($u) {
                return [
                    'id' => $u->id,
                    'matrimony_id' => $u->matrimony_id,
                    'name' => $u->userProfile ? $u->userProfile->first_name . ' ' . $u->userProfile->last_name : 'No Name',
                    'role' => $u->role
                ];
            });

        return response()->json(['users' => $users]);
    }

    public function requestTransferOtp(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:10'
        ]);

        $sender = $request->user();
        $recipient = User::findOrFail($request->recipient_id);
        
        if ($sender->id === $recipient->id) {
            return response()->json(['error' => 'You cannot transfer to yourself'], 400);
        }

        $totalAmount = $request->amount * 1.10; // 10% fee
        $wallet = Wallet::where('user_id', $sender->id)->first();
        if (!$wallet || $wallet->balance < $totalAmount) {
            return response()->json(['error' => 'Insufficient balance (Required: ₹' . number_format($totalAmount, 2) . ')'], 400);
        }

        $otp = rand(111111, 999999);
        
        WalletTransferOtp::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'amount' => $request->amount,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10)
        ]);

        return response()->json([
            'message' => 'OTP sent to ' . ($recipient->userProfile->first_name ?? 'Recipient'),
            'otp' => env('APP_ENV') === 'local' ? $otp : null
        ]);
    }

    public function transferWallet(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:10',
            'otp' => 'required|string|size:6'
        ]);

        $sender = $request->user();
        $recipient = User::findOrFail($request->recipient_id);

        $transferOtp = WalletTransferOtp::where([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'amount' => $request->amount,
            'otp' => $request->otp
        ])->valid()->latest()->first();

        if (!$transferOtp) {
            return response()->json(['error' => 'Invalid or expired OTP. Please ask the recipient for the latest code.'], 400);
        }

        $amount = (float) $request->amount;
        $fee = $amount * 0.10;
        $totalDeduction = $amount + $fee;

        $senderWallet = Wallet::where('user_id', $sender->id)->first();
        if (!$senderWallet || $senderWallet->balance < $totalDeduction) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        \DB::transaction(function() use ($sender, $recipient, $senderWallet, $amount, $fee, $totalDeduction, $transferOtp) {
            // Deduct from sender
            $senderWallet->decrement('balance', $totalDeduction);
            
            // Add to recipient
            $recipientWallet = Wallet::firstOrCreate(['user_id' => $recipient->id], ['balance' => 0]);
            $recipientWallet->increment('balance', $amount);

            // Mark OTP as used
            $transferOtp->update(['verified_at' => now()]);

            // Create Transactions
            Transaction::create([
                'user_id' => $sender->id,
                'type' => 'wallet_transfer',
                'amount' => $totalDeduction,
                'status' => 'success',
                'description' => "Sent ₹" . number_format($amount, 0) . " to {$recipient->matrimony_id} (Fee: ₹" . number_format($fee, 0) . ")"
            ]);

            Transaction::create([
                'user_id' => $recipient->id,
                'type' => 'wallet_transfer',
                'amount' => $amount,
                'status' => 'success',
                'description' => "Received ₹" . number_format($amount, 0) . " from {$sender->matrimony_id}"
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Transfer complete!',
            'new_balance' => $senderWallet->fresh()->balance
        ]);
    }
}
