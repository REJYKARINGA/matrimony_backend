<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\ContactUnlock;
use App\Models\Transaction;
use App\Models\Reference;
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
            'amount' => 'required|numeric|min:1',
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
            'remaining_balance' => $wallet->balance
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
            'count' => $count
        ]);
    }
}
