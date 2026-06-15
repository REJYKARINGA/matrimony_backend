<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Successful - Vivah Matrimony</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            padding: 40px 32px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            text-align: center;
            position: relative;
        }
        .check-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: popIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .check-icon svg { width: 36px; height: 36px; stroke: #059669; }
        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .title {
            font-size: 26px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #6B7280;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .divider {
            height: 1px;
            background: #F3F4F6;
            margin: 24px 0;
        }
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn-primary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #00C897 0%, #00A87D 100%);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 16px rgba(0,200,151,0.3);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(0,200,151,0.4); }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary svg { width: 20px; height: 20px; flex-shrink: 0; }
        .btn-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            background: #F9FAFB;
            color: #374151;
            border: 1px solid #E5E7EB;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-secondary:hover { background: #F3F4F6; }
        .btn-secondary svg { width: 16px; height: 16px; flex-shrink: 0; }
        .secure-badge {
            margin-top: 24px;
            color: #9CA3AF;
            font-size: 12px;
        }

        /* Modal overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            z-index: 100;
            animation: fadeIn 0.2s ease;
        }
        .modal-overlay.active { display: flex; }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-box {
            background: #fff;
            border-radius: 24px;
            padding: 32px 28px 28px;
            max-width: 360px;
            width: 90%;
            text-align: center;
            animation: slideUp 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }
        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #E0F2FE, #BAE6FD);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .modal-icon svg { width: 28px; height: 28px; stroke: #0284C7; }
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }
        .modal-desc {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .modal-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .modal-btn-open {
            padding: 14px;
            background: linear-gradient(135deg, #00C897 0%, #00A87D 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .modal-btn-open:hover { opacity: 0.9; }
        .modal-btn-close {
            padding: 12px;
            background: #F3F4F6;
            color: #374151;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }
        .modal-btn-close:hover { background: #E5E7EB; }
    </style>
</head>
<body>
    <div class="card">
        <div class="check-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="title">Payment Successful!</div>
        <div class="subtitle">
            @if($type === 'contact_unlock')
                Contact details have been unlocked. You can now message them directly.
            @else
                Your wallet has been recharged successfully.
            @endif
        </div>

        <div class="divider"></div>

        <div class="btn-group">
            <button class="btn-primary" onclick="showOpenModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                Open App
            </button>
            <button class="btn-secondary" onclick="window.close()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                Close Tab
            </button>
        </div>

        <div class="secure-badge">Vivah Matrimony</div>
    </div>

    <!-- Custom Modal -->
    <div class="modal-overlay" id="openModal">
        <div class="modal-box">
            <div class="modal-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <div class="modal-title">Open in Vivah Matrimony?</div>
            <div class="modal-desc">
                You'll be taken to the app where your updated balance will be reflected.
            </div>
            <div class="modal-actions">
                <button class="modal-btn-open" onclick="openApp()">
                    Open App
                </button>
                <button class="modal-btn-close" onclick="hideOpenModal()">
                    Stay Here
                </button>
            </div>
        </div>
    </div>

    <script>
        function showOpenModal() {
            document.getElementById('openModal').classList.add('active');
        }

        function hideOpenModal() {
            document.getElementById('openModal').classList.remove('active');
        }

        function openApp() {
            window.location.href = 'yourapp://wallet/success';
            // Close modal after attempt
            setTimeout(function() {
                hideOpenModal();
            }, 500);
        }
    </script>
</body>
</html>
