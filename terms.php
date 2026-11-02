<?php require __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service | Big Wallet Lounge</title>
    <style>
        :root{
            --bg:#0b1220;
            --surface:#111c2e;
            --surface-2:#16243a;
            --surface-3:#1e2d47;
            --text:#e2e8f0;
            --text-dim:#94a3b8;
            --text-mute:#64748b;
            --border:#243349;
            --amber:#f59e0b;
        }
        *{box-sizing:border-box}
        body { margin:0; font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; background: var(--surface); border: 1px solid var(--border); padding: 40px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,.5); }
        h1 { color: var(--text); margin-top: 0; }
        h2 { color: var(--text); margin-top: 30px; font-size: 1.15rem; }
        p { color: var(--text-dim); margin: 10px 0; }
        ul { color: var(--text-dim); margin: 10px 0; padding-left: 20px; }
        li { margin: 6px 0; }
        a { color: var(--amber); text-decoration: none; font-weight: bold; transition: color .15s; }
        a:hover { color: #d97706; }
        .footer-nav { margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--border); text-align: center; font-size: 0.85rem; }
        @media(max-width: 640px) {
            body { padding: 20px 12px; }
            .container { padding: 24px; }
            h1 { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.html">&larr; Back to Home</a>
        <h1>Terms of Service</h1>
        <p>Last updated: November 2026</p>

        <h2>1. Acceptance of Terms</h2>
        <p>Welcome to the Big Wallet Lounge Management System. By accessing or using this portal, you agree to comply with and be bound by these Terms of Service. If you do not agree, please do not use this system.</p>

        <h2>2. Authorized Use</h2>
        <p>This portal is a private business management system intended strictly for authorized employees of Big Wallet Lounge. Access is restricted to staff holding valid credentials issued by the system administrator.</p>
        <ul>
            <li>You must not share your login credentials with anyone.</li>
            <li>You must not attempt to gain unauthorized access to other accounts or data.</li>
            <li>You must use the system solely for legitimate business operations (sales, inventory, bookings, etc.).</li>
        </ul>

        <h2>3. Account Suspension</h2>
        <p>We reserve the right to suspend or terminate any account at any time if we suspect misuse, unauthorized access, or violation of these terms. All activity within this system is logged and monitored for security purposes.</p>

        <h2>4. Data Accuracy and Liability</h2>
        <p>While we strive to keep information accurate and up to date, Big Wallet Lounge makes no warranties regarding the completeness or reliability of the system. Authorized staff are responsible for ensuring the accuracy of data they enter.</p>

        <h2>5. Contact Us</h2>
        <p>If you have questions about these Terms of Service, please contact us at <a href="mailto:info@bigwalletlounge.com">info@bigwalletlounge.com</a>, WhatsApp <a href="https://wa.me/211925830541" target="_blank">+211 925 830 541</a>, or call <a href="tel:0787072542">0787 072 542</a>.</p>

        <div class="footer-nav">
            <a href="index.html">Home</a> | 
            <a href="privacy.php">Privacy Policy</a> | 
            <a href="manual.php">User Manual</a>
        </div>
    </div>
</body>
</html>