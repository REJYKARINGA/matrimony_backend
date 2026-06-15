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
            position: relative;
            overflow: hidden;
        }
        .offer-ribbon {
            position: absolute;
            top: 16px;
            right: -32px;
            background: linear-gradient(135deg, #F59E0B, #D97706);
            color: #fff;
            padding: 6px 40px;
            font-size: 12px;
            font-weight: 700;
            transform: rotate(45deg);
            box-shadow: 0 2px 8px rgba(245,158,11,0.3);
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
            margin-bottom: 4px;
        }
        .amount-display.has-discount {
            color: #00C897;
        }
        .original-amount {
            font-size: 20px;
            color: #9CA3AF;
            text-decoration: line-through;
            margin-bottom: 4px;
        }
        .amount-label {
            color: #9CA3AF;
            font-size: 13px;
            margin-bottom: 24px;
        }
        .offer-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #FFFBEB, #FEF3C7);
            border: 1px solid #FDE68A;
            border-radius: 100px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #92400E;
            margin-bottom: 24px;
        }
        .offer-badge svg { width: 16px; height: 16px; flex-shrink: 0; }
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
        @if($festivalName && $originalPrice > 0)
            <div class="offer-ribbon">OFFER</div>
        @endif
        <div class="logo">Vivah Matrimony</div>
        <div class="subtitle">Secure wallet recharge</div>

        @if($festivalName && $originalPrice > 0)
            <div class="original-amount">₹{{ number_format($originalPrice, 0) }}</div>
            <div class="amount-display has-discount">₹{{ number_format($amount / 100, 0) }}</div>
        @else
            <div class="amount-display">₹{{ number_format($amount / 100, 0) }}</div>
        @endif
        <div class="amount-label">{{ $type === 'contact_unlock' ? 'Contact Unlock' : 'Wallet Recharge' }}</div>

        @if($festivalName && $originalPrice > 0)
            <div class="offer-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="#92400E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
                </svg>
                {{ $festivalName }} Offer — Save ₹{{ number_format($originalPrice - ($amount / 100), 0) }}
            </div>
        @endif

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
                    setStatus('Payment successful!', 'success');
                    // Redirect to success page — user can choose to open app from there
                    setTimeout(function() {
                        window.location.href = API_BASE.replace('/api', '') + '/payment/success?type=' + TYPE;
                    }, 1200);
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
