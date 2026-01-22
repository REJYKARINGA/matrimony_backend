<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #B47FFF 0%, #5CB3FF 50%, #4CD9A6 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
        }

        .header p {
            margin: 0;
            font-size: 15px;
            opacity: 0.95;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }

        .otp-box {
            background: linear-gradient(135deg, #B47FFF 0%, #5CB3FF 100%);
            color: white;
            font-size: 36px;
            font-weight: bold;
            text-align: center;
            padding: 25px;
            margin: 30px 0;
            border-radius: 12px;
            letter-spacing: 10px;
            box-shadow: 0 4px 15px rgba(180, 127, 255, 0.3);
        }

        .info-text {
            color: #666;
            font-size: 15px;
            margin: 20px 0;
            line-height: 1.8;
        }

        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 18px;
            margin: 25px 0;
            border-radius: 6px;
        }

        .security-tips {
            background: #e7f3ff;
            border-left: 4px solid #5CB3FF;
            padding: 18px;
            margin: 25px 0;
            border-radius: 6px;
        }

        .footer {
            background: #f8f9fa;
            padding: 25px;
            text-align: center;
            color: #666;
            font-size: 13px;
            line-height: 1.8;
        }

        .footer-logo {
            font-size: 16px;
            font-weight: 600;
            color: #B47FFF;
            margin-bottom: 10px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #ddd, transparent);
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>💝 Password Reset Request</h1>
            <p>Secure Your Matrimony Account</p>
        </div>
        <div class="content">
            <p class="greeting">Dear Member,</p>

            <p>We received a request to reset the password for your matrimony account. Your account security is
                important to us, and we're here to help you regain access.</p>

            <p style="margin-top: 25px; margin-bottom: 10px; font-weight: 600; color: #333;">Your One-Time Password
                (OTP):</p>

            <div class="otp-box">
                {{ $otp }}
            </div>

            <p class="info-text">
                <strong>⏰ This OTP is valid for 30 minutes only.</strong><br>
                Please enter this code in the app to proceed with resetting your password.
            </p>

            <div class="divider"></div>

            <div class="warning">
                <strong>🔒 Security Notice:</strong><br>
                If you didn't request a password reset, please ignore this email. Your account remains secure, and no
                changes have been made. However, if you're concerned about unauthorized access, please contact our
                support team immediately.
            </div>

            <div class="security-tips">
                <strong>💡 Account Security Tips:</strong><br>
                • Never share your OTP or password with anyone<br>
                • Our team will never ask for your password via email or phone<br>
                • Use a strong, unique password for your matrimony account<br>
                • Keep your profile information up-to-date and accurate
            </div>

            <p class="info-text">
                This OTP can only be used once. If you need a new code, please request another password reset from the
                app.
            </p>

            <p style="margin-top: 30px; color: #666;">
                Thank you for being a valued member of our matrimony community. We're committed to helping you find your
                perfect life partner in a safe and secure environment.
            </p>
        </div>
        <div class="footer">
            <div class="footer-logo">{{ config('app.name') }}</div>
            <p>Connecting Hearts, Building Futures</p>
            <div class="divider"></div>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 12px;">
                This is an automated email. Please do not reply to this message.<br>
                For support, please contact us through the app or visit our help center.
            </p>
        </div>
    </div>
</body>

</html>