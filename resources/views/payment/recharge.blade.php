<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wallet Recharge - Vivah Matrimony</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #00C897 0%, #00A87D 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 24px;
            padding: 40px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            text-align: center;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #00C897;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #6B7280;
            font-size: 14px;
            margin-bottom: 28px;
        }
        .amount-display {
            font-size: 48px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }
        .amount-label {
            color: #9CA3AF;
            font-size: 13px;
            margin-bottom: 32px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #F3F4F6;
            font-size: 14px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6B7280; }
        .info-value { color: #111827; font-weight: 500; }
        .btn-pay {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #00C897 0%, #00A87D 100%);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 24px;
            transition: opacity 0.2s;
        }
        .btn-pay:hover { opacity: 0.9; }
        .btn-pay:disabled { opacity: 0.5; cursor: not-allowed; }
        .status {
            margin-top: 20px;
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            display: none;
        }
        .status.success { display: block; background: #D1FAE5; color: #065F46; }
        .status.error { display: block; background: #FEE2E2; color: #991B1B; }
        .status.loading { display: block; background: #EFF6FF; color: #1E40AF; }
        .secure-badge {
            margin-top: 20px;
            color: #9CA3AF;
            font-size: 12px;
        }
        .secure-badge svg { vertical-align: middle; margin-right: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">Vivah Matrimony</div>
        <div class="subtitle">Secure wallet recharge</div>

        <div class="amount-display">₹{{ number_format($amount / 100, 0) }}</div>
        <div class="amount-label">{{ $type === 'contact_unlock' ? 'Contact Unlock' : 'Wallet Recharge' }}</div>

        <div class="info-row">
            <span class="info-label">Order ID</span>
            <span class="info-value">{{ $orderId }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Type</span>
            <span class="info-value">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
        </div>

        <button id="payBtn" class="btn-pay" onclick="openRazorpay()">Pay ₹{{ number_format($amount / 100, 0) }}</button>

        <div id="status" class="status"></div>

        <div class="secure-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Secured by Razorpay
        </div>
    </div>

    <script>
        const RAZORPAY_KEY = '{{ $razorpayKey }}';
        const ORDER_ID = '{{ $orderId }}';
        const AMOUNT = {{ $amount }};
        const TRANSACTION_ID = {{ $transactionId }};
        const TOKEN = '{{ $token }}';
        const API_BASE = '{{ $apiBase }}';
        const TYPE = '{{ $type }}';
        const UNLOCKED_USER_ID = '{{ $unlockedUserId ?? '' }}';

        function setStatus(msg, type) {
            const el = document.getElementById('status');
            el.textContent = msg;
            el.className = 'status ' + type;
        }

        function openRazorpay() {
            document.getElementById('payBtn').disabled = true;
            setStatus('Opening payment gateway...', 'loading');

            var description = TYPE === 'contact_unlock' ? 'Contact Unlock' : 'Wallet Recharge';
            var options = {
                key: RAZORPAY_KEY,
                amount: AMOUNT,
                currency: 'INR',
                name: 'Vivah Matrimony',
                description: description,
                order_id: ORDER_ID,
                theme: { color: '#00C897' },
                handler: function(response) {
                    verifyPayment(response);
                },
                modal: {
                    ondismiss: function() {
                        document.getElementById('payBtn').disabled = false;
                        setStatus('Payment cancelled', 'error');
                    }
                }
            };
            new Razorpay(options).open();
        }

        function verifyPayment(response) {
            setStatus('Verifying payment...', 'loading');

            fetch(API_BASE + '/payment/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + TOKEN
                },
                body: JSON.stringify({
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature: response.razorpay_signature,
                    transaction_id: TRANSACTION_ID,
                    unlocked_user_id: UNLOCKED_USER_ID || undefined
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setStatus('Payment successful! Redirecting...', 'success');
                    setTimeout(function() {
                        window.location.href = 'yourapp://wallet/success';
                    }, 1500);
                } else {
                    setStatus(data.message || 'Payment verification failed', 'error');
                    document.getElementById('payBtn').disabled = false;
                }
            })
            .catch(err => {
                setStatus('Verification error. Please contact support.', 'error');
                document.getElementById('payBtn').disabled = false;
                console.error(err);
            });
        }

        // Auto-open Razorpay on load
        window.addEventListener('load', function() {
            setTimeout(openRazorpay, 500);
        });
    </script>
</body>
</html>
