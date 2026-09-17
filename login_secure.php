<?php
require_once __DIR__ . '/bootstrap_simple.php';

if (!function_exists('check_system_online')) {
    function check_system_online() {
        $connected = @fsockopen("www.google.com", 443, $errno, $errstr, 1.5);
        if ($connected) {
            fclose($connected);
            return true;
        }
        return false;
    }
}
$is_system_online = check_system_online();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// Redirect if already logged in
if (SimpleSecurity::isAuthenticated()) {
    SimpleSecurity::redirect('dashboard_secure.php');
}

$auth = new SimpleAuth();
$errors = [];
$username_value = '';
$remember_checked = false;
$account_locked = false;
$locked_seconds = 0;
$attempts_remaining = null;

if ($_POST) {
    $client_offline = isset($_POST['system_offline']) && $_POST['system_offline'] === '1';

    if (!empty($_POST['website_url'] ?? '')) {
        sleep(2);
        $errors[] = "security_error";
    }
    elseif (isset($_POST['_ts']) && (time() - (int)$_POST['_ts']) < 1) {
        sleep(2);
        $errors[] = "security_error";
    }
    elseif (!SimpleSecurity::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "security_error";
    } else {
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        $recaptcha_valid = false;

        if (!$is_system_online || $client_offline) {
            $recaptcha_valid = true;
        } elseif (empty($recaptcha_response)) {
            $errors[] = "recaptcha_error";
        } else {
            $verify_data = http_build_query([
                'secret' => RECAPTCHA_SECRET_KEY,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $verify_data,
                    'timeout' => 5
                ]
            ]);
            $verify_result = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
            if ($verify_result !== false) {
                $response_data = json_decode($verify_result, true);
                $recaptcha_valid = !empty($response_data['success']);
            }
            if (!$recaptcha_valid) {
                $errors[] = "recaptcha_error";
            }
        }

        if (empty($errors)) {
            $username = SimpleSecurity::sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']) ? true : false;

            if (strlen($username) < 1 || strlen($username) > 100) {
                $errors[] = "Invalid username or email.";
            } elseif (strlen($password) < 1 || strlen($password) > 128) {
                $errors[] = "Invalid password.";
            } else {
                $username_value = $username;
                $remember_checked = $remember;
                $result = $auth->login($username, $password, $remember);

                if ($result['success']) {
                    session_regenerate_id(true);
                    require_once __DIR__ . '/php_action/ActivityLogger.php';
                    ActivityLogger::login('User logged in: ' . $username, ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
                    SimpleSecurity::redirect('dashboard_secure.php');
                } else {
                    $errors[] = $result['message'];
                    // Use the structured fields SimpleAuth::login() now returns
                    // instead of matching against message text — both the
                    // per-account 60s lockout and the IP-wide 5-minute throttle
                    // used to share the "Too many login attempts" phrase, so a
                    // strpos() check couldn't reliably tell them apart.
                    if (isset($result['account_locked_seconds'])) {
                        $account_locked = true;
                        $locked_seconds = intval($result['account_locked_seconds']);
                    } elseif (strpos($result['message'], 'Too many login attempts from your network') !== false) {
                        // IP-wide throttle has no exact expiry tracked server-side
                        // (it's a rolling 5-minute window, not a fixed unlock time),
                        // so the countdown shown is a conservative upper bound.
                        $account_locked = true;
                        $locked_seconds = 300;
                    } elseif (isset($result['attempts_remaining'])) {
                        $attempts_remaining = intval($result['attempts_remaining']);
                    }
                }
            }
        }
    }
}

$session_expired = isset($_GET['error']) && $_GET['error'] === 'session_expired';
$reset_success = isset($_GET['reset']) && $_GET['reset'] === 'success';
$csrf_token = SimpleSecurity::generateCSRFToken();
$form_timestamp = time();
$display_errors = array_filter($errors, function($e) { return $e !== 'recaptcha_error'; });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>
    (function() {
        var theme = localStorage.getItem('mcagrivet_theme');
        if (!theme) theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
    })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agrivet Inventory Supply - Login</title>
    <link rel="icon" type="image/png" href="logo.png?v=<?php echo time(); ?>">
    <link rel="apple-touch-icon" href="logo.png?v=<?php echo time(); ?>">
    <?php if ($is_system_online): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer onerror="onRecaptchaScriptError()"></script>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,500;1,9..144,600&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assests/css/app.css">
    <style>
        /* Bridge to the shared design-system tokens in assests/css/app.css so this
           page's dark mode uses the same palette as the rest of the app instead of
           a second, incompatible variable vocabulary. */
        :root {
            --login-bg: var(--main-bg);
            --login-card-bg: var(--card-bg);
            --login-text: var(--text);
            --login-text-muted: var(--text-muted);
            --login-border: var(--border);
            --login-input-bg: var(--input-bg);

            /* Warm neutral palette for this page's light mode — replaces generic
               cool Tailwind grays with tones that read as "grain/harvest" rather
               than "default UI kit," while dark mode keeps bridging to the shared
               app tokens above untouched. */
            --ink: #2A2118;
            --ink-soft: #6B5D4F;
            --ink-faint: #A79A88;
            --hairline: #EBE2D4;
            --surface: #FAF6EF;
            --surface-deep: #F3ECDF;
            --font-display: 'Fraunces', Georgia, serif;
            --font-body: 'Work Sans', -apple-system, sans-serif;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; width: 100%; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.001ms !important; animation-iteration-count: 1 !important; }
        }

        body {
            font-family: var(--font-body);
            background: var(--surface);
            overflow-x: hidden;
        }

        @keyframes riseIn {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ══════════════════════════════════════════════
           SPLIT LAYOUT
        ══════════════════════════════════════════════ */
        .login-page {
            display: flex;
            min-height: 100vh;
        }

        /* ── LEFT BRAND PANEL ──────────────────────── */
        .brand-side {
            flex: 1 1 55%;
            background:
                radial-gradient(ellipse 900px 600px at 15% -10%, rgba(255,214,140,0.35), transparent 60%),
                linear-gradient(160deg, #B87F0A 0%, #E8A317 35%, #D99516 68%, #C78C0A 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Grain texture — a hand-tuned SVG turbulence noise, blended softly over
           the gradient so the panel reads as tactile paper/grain rather than a
           flat, generic color swatch. */
        .brand-grain {
            position: absolute;
            inset: 0;
            opacity: 0.5;
            mix-blend-mode: overlay;
            pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.4'/%3E%3C/svg%3E");
        }

        /* Decorative rows — quiet nod to cultivated fields, sitting low so they
           never compete with the logo/copy above them. */
        .brand-rows {
            position: absolute;
            left: 0; right: 0; bottom: -10%;
            height: 46%;
            background: repeating-linear-gradient(
                -6deg,
                transparent 0px, transparent 34px,
                rgba(255,255,255,0.055) 34px, rgba(255,255,255,0.055) 36px
            );
            pointer-events: none;
        }

        /* Soft glow circles */
        .brand-side::before {
            content: '';
            position: absolute;
            width: 480px; height: 480px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            top: -200px; right: -140px;
            filter: blur(2px);
        }
        .brand-side::after {
            content: '';
            position: absolute;
            width: 320px; height: 320px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.12);
            bottom: -120px; left: -70px;
        }

        .brand-inner {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 520px;
            width: 100%;
        }

        .brand-logo {
            width: 96%;
            max-width: 480px;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(60,35,0,0.3);
            margin-bottom: 2.25rem;
            transition: transform 0.4s ease;
            animation: riseIn 0.7s cubic-bezier(0.22,1,0.36,1) both;
            object-fit: contain;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .brand-logo:hover { transform: scale(1.02) translateY(-4px); }

        .brand-tagline {
            font-family: var(--font-display);
            font-style: italic;
            font-optical-sizing: auto;
            color: #fff;
            font-size: 1.4rem;
            font-weight: 500;
            line-height: 1.5;
            text-shadow: 0 1px 8px rgba(60,35,0,0.2);
            margin-bottom: 2.75rem;
            animation: riseIn 0.7s cubic-bezier(0.22,1,0.36,1) both;
            animation-delay: 0.1s;
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.14);
            backdrop-filter: blur(6px);
            border-radius: 12px;
            padding: 12px 14px;
            transition: background 0.3s, transform 0.3s;
            animation: riseIn 0.7s cubic-bezier(0.22,1,0.36,1) both;
        }
        .feature-item:nth-child(1) { animation-delay: 0.18s; }
        .feature-item:nth-child(2) { animation-delay: 0.24s; }
        .feature-item:nth-child(3) { animation-delay: 0.30s; }
        .feature-item:nth-child(4) { animation-delay: 0.36s; }
        .feature-item:hover { background: rgba(255,255,255,0.19); transform: translateY(-2px); }

        .feature-icon {
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .feature-text {
            color: white;
            font-size: 0.82rem;
            font-weight: 600;
            line-height: 1.3;
        }

        /* ── RIGHT FORM PANEL ──────────────────────── */
        .form-side {
            flex: 0 0 460px;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .form-scroll {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 2.5rem 1.5rem;
            max-width: 460px;
            width: 100%;
            margin: 0 auto;
        }

        /* Mobile logo (hidden on desktop) */
        .mobile-header {
            display: none;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .mobile-header img {
            width: 100%;
            max-width: 300px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .form-title {
            font-family: var(--font-display);
            font-optical-sizing: auto;
            font-size: 2.1rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 0.35rem;
            letter-spacing: -0.01em;
            animation: riseIn 0.6s cubic-bezier(0.22,1,0.36,1) both;
        }

        .form-subtitle {
            color: var(--ink-soft);
            font-size: 0.92rem;
            margin-bottom: 1.85rem;
            line-height: 1.5;
            animation: riseIn 0.6s cubic-bezier(0.22,1,0.36,1) both;
            animation-delay: 0.06s;
        }

        /* ── ALERTS ────────────────────────────────── */
        .alert-container { margin-bottom: 1rem; }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 0.85rem 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            animation: alertSlideIn 0.4s ease;
            position: relative;
            overflow: hidden;
            pointer-events: none;
            user-select: none;
        }

        /* Progress bar for auto-collapse countdown */
        .alert::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            height: 3px;
            width: 100%;
            background: currentColor;
            opacity: 0.3;
            animation: alertTimer 5s linear forwards;
        }

        /* Collapse animation */
        .alert.collapsing-out {
            animation: alertCollapseOut 0.5s ease forwards;
        }

        @keyframes alertSlideIn {
            from { opacity: 0; transform: translateY(-10px); max-height: 0; padding-top: 0; padding-bottom: 0; margin-bottom: 0; }
            to { opacity: 1; transform: translateY(0); max-height: 80px; }
        }

        @keyframes alertCollapseOut {
            0% { opacity: 1; max-height: 80px; transform: translateY(0); }
            50% { opacity: 0; transform: translateY(-6px); }
            100% { opacity: 0; max-height: 0; padding: 0; margin: 0; overflow: hidden; }
        }

        @keyframes alertTimer {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* Wrong password - red */
        .alert-wrong-password {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: #991b1b;
            border-left: 4px solid #ef4444;
            box-shadow: 0 2px 8px rgba(239,68,68,0.1);
        }
        .alert-wrong-password i { color: #ef4444; }

        /* Account not found - amber/warning */
        .alert-not-found {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            color: #92400e;
            border-left: 4px solid #f59e0b;
            box-shadow: 0 2px 8px rgba(245,158,11,0.1);
        }
        .alert-not-found i { color: #f59e0b; }

        /* Generic error - red */
        .alert-danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: #991b1b;
            border-left: 4px solid #ef4444;
            box-shadow: 0 2px 8px rgba(239,68,68,0.1);
        }
        .alert-danger i { color: #ef4444; }

        /* Account locked - dark red */
        .alert-locked {
            background: linear-gradient(135deg, #fef2f2, #fecaca);
            color: #7f1d1d;
            border-left: 4px solid #dc2626;
            box-shadow: 0 2px 8px rgba(220,38,38,0.12);
        }
        .alert-locked i { color: #dc2626; }

        .alert i { flex-shrink: 0; font-size: 1rem; }
        .alert-text { flex: 1; line-height: 1.4; }

        .info-notice {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-left: 4px solid #3b82f6;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #1d4ed8;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            pointer-events: none;
            position: relative;
            overflow: hidden;
            animation: alertSlideIn 0.4s ease;
        }

        .info-notice::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            height: 3px; width: 100%;
            background: #3b82f6;
            opacity: 0.3;
            animation: alertTimer 5s linear forwards;
        }

        .info-notice.collapsing-out {
            animation: alertCollapseOut 0.5s ease forwards;
        }

        /* ── FORM ──────────────────────────────────── */
        .form-group {
            margin-bottom: 1.1rem;
            animation: riseIn 0.6s cubic-bezier(0.22,1,0.36,1) both;
        }
        .form-group:nth-of-type(1) { animation-delay: 0.12s; }
        .form-group:nth-of-type(2) { animation-delay: 0.17s; }

        .form-label {
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 0.35rem;
            font-size: 0.78rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            display: block;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .field-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--ink-faint);
            font-size: 0.85rem;
            pointer-events: none;
            transition: color 0.25s;
            z-index: 2;
        }

        .form-control {
            border-radius: 10px;
            border: 1.5px solid var(--hairline);
            padding: 11px 14px 11px 40px;
            font-size: 0.92rem;
            background: var(--surface-deep);
            width: 100%;
            transition: all 0.25s;
            color: var(--ink);
            font-weight: 500;
        }

        .form-control:focus {
            background: #fff;
            border-color: #E8A317;
            box-shadow: 0 0 0 3px rgba(232,163,23,0.12);
            outline: none;
        }
        .form-control:focus ~ .field-icon { color: #E8A317; }
        .form-control::placeholder { color: var(--ink-faint); font-weight: 400; }

        .form-control.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.08);
        }

        /* Password toggle */
        .pass-toggle {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--ink-faint);
            cursor: pointer;
            font-size: 0.92rem;
            padding: 3px 5px;
            border-radius: 6px;
            transition: all 0.2s;
            z-index: 2;
        }
        .pass-toggle:hover { color: #E8A317; background: rgba(232,163,23,0.08); }
        #password { padding-right: 42px; }

        /* ── REMEMBER + FORGOT ROW ─────────────────── */
        .options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            animation: riseIn 0.6s cubic-bezier(0.22,1,0.36,1) both;
            animation-delay: 0.22s;
        }

        .form-check { margin: 0; }

        .form-check-input {
            width: 16px; height: 16px;
            margin-top: 0.2rem;
            cursor: pointer;
            border: 2px solid var(--hairline);
            border-radius: 4px;
            transition: all 0.2s;
        }
        .form-check-input:checked { background-color: #E8A317; border-color: #E8A317; }
        .form-check-input:focus { border-color: #E8A317; box-shadow: 0 0 0 3px rgba(232,163,23,0.1); }

        .form-check-label {
            cursor: pointer;
            user-select: none;
            color: var(--ink-soft);
            font-size: 0.82rem;
            font-weight: 500;
        }

        .forgot-link {
            color: #C78C0A;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 600;
            transition: color 0.2s;
            white-space: nowrap;
        }
        .forgot-link:hover { color: #9c6f08; text-decoration: underline; }

        /* ── reCAPTCHA ─────────────────────────────── */
        .captcha-box {
            background: var(--surface-deep);
            border: 1.5px solid var(--hairline);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: border-color 0.3s;
            animation: riseIn 0.6s cubic-bezier(0.22,1,0.36,1) both;
            animation-delay: 0.27s;
        }
        .captcha-box.has-error { border-color: #fca5a5; background: #fef8f8; }
        .captcha-box.verified { border-color: #86efac; background: #f0fdf4; }

        .captcha-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--ink-faint);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .captcha-label i { font-size: 0.7rem; }

        .g-recaptcha {
            transform-origin: center;
        }

        .captcha-error {
            color: #dc2626;
            font-size: 0.78rem;
            font-weight: 600;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .captcha-error i { font-size: 0.7rem; }

        /* ── LOGIN BUTTON ──────────────────────────── */
        .btn-login {
            background: linear-gradient(135deg, #E8A317 0%, #C78C0A 100%);
            border: none;
            border-radius: 12px;
            padding: 13px;
            font-weight: 700;
            color: white;
            width: 100%;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 16px rgba(199,140,10,0.3);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            animation: riseIn 0.6s cubic-bezier(0.22,1,0.36,1) both;
            animation-delay: 0.32s;
        }
        .btn-login::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 50%;
            background: rgba(255,255,255,0.1);
            pointer-events: none;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(199,140,10,0.4);
            color: white;
        }
        .btn-login:active:not(:disabled) { transform: translateY(0); }
        .btn-login:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        /* ── FOOTER ────────────────────────────────── */
        .form-footer {
            padding: 1.1rem 2.5rem 1.35rem;
            border-top: 1px solid var(--hairline);
            text-align: center;
            flex-shrink: 0;
        }

        .footer-note {
            color: var(--ink-faint);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.02em;
        }

        /* ── LOCKED STATE ──────────────────────────── */
        .locked-state {
            text-align: center;
            padding: 2rem 0.5rem 1rem;
        }
        .locked-state .icon-circle {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: #fef2f2;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
        .locked-state .icon-circle i { font-size: 1.75rem; color: #ef4444; }
        .locked-state h3 { font-family: var(--font-display); color: #991b1b; font-size: 1.25rem; font-weight: 600; margin-bottom: 0.4rem; }
        .locked-state p { color: var(--ink-soft); font-size: 0.88rem; line-height: 1.5; }

        .lock-progress {
            width: 100%;
            max-width: 220px;
            height: 6px;
            border-radius: 6px;
            background: var(--hairline);
            margin: 1rem auto 0;
            overflow: hidden;
        }
        .lock-progress-bar {
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, #ef4444, #f59e0b);
            transition: width 1s linear;
        }

        /* Honeypot */
        .hp-field {
            position: absolute; left: -9999px; top: -9999px;
            opacity: 0; height: 0; width: 0; z-index: -1; pointer-events: none;
        }

        /* ══════════════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════════════ */

        /* Tablets / smaller desktops */
        @media (max-width: 1024px) {
            .form-side { flex: 0 0 420px; }
            .brand-logo { max-width: 360px; }
            .features-grid { gap: 10px; }
            .feature-item { padding: 10px 12px; }
        }

        /* Mobile - stack vertically */
        @media (max-width: 767.98px) {
            .login-page { flex-direction: column; }

            .brand-side {
                flex: none;
                padding: 2rem 1.5rem;
            }

            .brand-inner { max-width: 400px; }
            .brand-logo { max-width: 320px; width: 85%; margin-bottom: 1.25rem; }
            .brand-tagline { font-size: 0.95rem; margin-bottom: 1.5rem; }
            .features-grid { grid-template-columns: 1fr 1fr; gap: 8px; }

            .form-side {
                flex: none;
                width: 100%;
            }

            .form-scroll {
                padding: 2rem 1.75rem 1.25rem;
            }

            .mobile-header { display: none; }

            .form-footer { padding: 1rem 1.75rem 1.25rem; }
        }

        /* Small phones */
        @media (max-width: 479.98px) {
            .brand-side { padding: 1.5rem 1rem; }
            .brand-logo { max-width: 280px; margin-bottom: 1rem; }
            .brand-tagline { font-size: 0.88rem; margin-bottom: 1rem; }
            .features-grid { grid-template-columns: 1fr; gap: 6px; }
            .feature-item { padding: 10px; }

            .form-scroll { padding: 1.75rem 1.25rem 1rem; }
            .form-title { font-size: 1.4rem; }

            .form-control { font-size: 16px; }

            .g-recaptcha {
                transform: scale(0.92);
                transform-origin: center;
            }

            .form-footer { padding: 0.75rem 1.25rem 1rem; }
        }

        @media (max-width: 360px) {
            .g-recaptcha {
                transform: scale(0.82);
                transform-origin: center;
            }

            .captcha-box { padding: 12px 8px; }
        }

        /* Short screens on desktop */
        @media (min-width: 769px) and (max-height: 700px) {
            .form-scroll { padding: 1.5rem 2rem 1rem; }
            .form-title { font-size: 1.4rem; }
            .form-group { margin-bottom: 0.9rem; }
            .captcha-box { padding: 12px; margin-bottom: 1rem; }
            .options-row { margin-bottom: 1rem; }
        }
        /* ══════════════════════════════════════════════
           DARK MODE
        ══════════════════════════════════════════════ */
        :root[data-theme="dark"] {
            --login-bg: #121212;
            --login-card-bg: #1E1E1E;
            --login-text: #E0E0E0;
            --login-text-muted: #9E9E9E;
            --login-border: #2C2C2C;
            --login-input-bg: #2A2A2A;
        }

        [data-theme="dark"] body {
            background: var(--login-bg);
        }

        [data-theme="dark"] .form-side {
            background: var(--login-card-bg);
        }

        [data-theme="dark"] .form-title {
            color: var(--login-text);
        }

        [data-theme="dark"] .form-subtitle {
            color: var(--login-text-muted);
        }

        [data-theme="dark"] .form-label {
            color: var(--login-text-muted);
        }

        [data-theme="dark"] .form-control {
            background: var(--login-input-bg);
            border-color: var(--login-border);
            color: var(--login-text);
        }

        [data-theme="dark"] .form-control:focus {
            background: #333;
            border-color: #E8A317;
            box-shadow: 0 0 0 3px rgba(232,163,23,0.15);
        }

        [data-theme="dark"] .form-control::placeholder {
            color: #666;
        }

        [data-theme="dark"] .input-wrap .field-icon {
            color: #666;
        }

        [data-theme="dark"] .form-control:focus ~ .field-icon {
            color: #E8A317;
        }

        [data-theme="dark"] .pass-toggle {
            color: #666;
        }

        [data-theme="dark"] .pass-toggle:hover {
            color: #E8A317;
            background: rgba(232,163,23,0.1);
        }

        [data-theme="dark"] .form-check-label {
            color: var(--login-text-muted);
        }

        [data-theme="dark"] .form-check-input {
            background-color: var(--login-input-bg);
            border-color: var(--login-border);
        }

        [data-theme="dark"] .form-check-input:checked {
            background-color: #E8A317;
            border-color: #E8A317;
        }

        [data-theme="dark"] .forgot-link {
            color: #E8A317;
        }

        [data-theme="dark"] .forgot-link:hover {
            color: #ffb300;
        }

        [data-theme="dark"] .captcha-box {
            background: var(--login-input-bg);
            border-color: var(--login-border);
        }

        [data-theme="dark"] .captcha-label {
            color: #777;
        }

        [data-theme="dark"] .form-footer {
            border-top-color: var(--login-border);
        }

        [data-theme="dark"] .footer-note {
            color: #666;
        }

        [data-theme="dark"] .locked-state h3 {
            color: #ef4444;
        }

        [data-theme="dark"] .locked-state p {
            color: var(--login-text-muted);
        }

        [data-theme="dark"] .lock-progress {
            background: var(--login-border);
        }

        [data-theme="dark"] .locked-state .icon-circle {
            background: #2a1a1a;
        }

        [data-theme="dark"] .info-notice {
            background: linear-gradient(135deg, #1a2332, #1a2744);
            color: #60a5fa;
            border-left-color: #3b82f6;
        }

        [data-theme="dark"] .alert-wrong-password {
            background: linear-gradient(135deg, #2a1a1a, #2d1b1b);
            color: #fca5a5;
            border-left-color: #ef4444;
        }

        [data-theme="dark"] .alert-not-found {
            background: linear-gradient(135deg, #2a2510, #2d2712);
            color: #fcd34d;
            border-left-color: #f59e0b;
        }

        [data-theme="dark"] .alert-danger {
            background: linear-gradient(135deg, #2a1a1a, #2d1b1b);
            color: #fca5a5;
            border-left-color: #ef4444;
        }

        [data-theme="dark"] .alert-locked {
            background: linear-gradient(135deg, #2a1515, #2d1818);
            color: #fca5a5;
            border-left-color: #dc2626;
        }

        [data-theme="dark"] .mobile-header img {
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="login-page">
        <!-- ════ LEFT: BRAND ════ -->
        <div class="brand-side">
            <div class="brand-grain" aria-hidden="true"></div>
            <div class="brand-rows" aria-hidden="true"></div>
            <div class="brand-inner">
                <img src="logo.png?v=<?php echo time(); ?>" alt="Agrivet Inventory Supply" class="brand-logo">

                <p class="brand-tagline">
                    Your trusted partner for premium<br>
                    animal feeds &amp; agricultural supplies
                </p>

                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-boxes-stacked"></i></div>
                        <span class="feature-text">Real-time<br>Inventory</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                        <span class="feature-text">Sales &amp;<br>Analytics</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                        <span class="feature-text">Secure &amp;<br>Reliable</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-users-gear"></i></div>
                        <span class="feature-text">Multi-user<br>Access</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════ RIGHT: FORM ════ -->
        <div class="form-side">
            <div class="form-scroll">

                <!-- Mobile logo -->
                <div class="mobile-header">
                    <img src="logo.png?v=<?php echo time(); ?>" alt="Agrivet Inventory Supply">
                </div>

                <h1 class="form-title">Welcome Back</h1>
                <p class="form-subtitle">Sign in to your inventory management account</p>

                <?php if ($reset_success): ?>
                    <div class="alert alert-success d-flex align-items-center mb-3" style="background:#f0fff4; border-left:4px solid #22c55e; color:#15803d; border-radius:10px; padding:12px 16px;">
                        <i class="fas fa-check-circle me-2" style="font-size:1.2rem;"></i>
                        <div>
                            <strong>Password Reset Successfully!</strong>
                            <div style="font-size:0.85rem;">You can now log in with your new password.</div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($session_expired): ?>
                    <div class="info-notice">
                        <i class="fas fa-clock"></i>
                        <span>Your session has expired due to 15 minutes of inactivity. Please log in again.</span>
                    </div>
                <?php endif; ?>

                <?php if ($account_locked): ?>
                    <div class="locked-state">
                        <div class="icon-circle"><i class="fas fa-lock"></i></div>
                        <h3>Account Temporarily Locked</h3>
                        <p>Too many failed attempts.<br>Please wait
                            <strong><span id="lockCountdown"><?php echo $locked_seconds; ?></span> second<?php echo $locked_seconds === 1 ? '' : 's'; ?></strong>
                            and try again.
                        </p>
                        <div class="lock-progress"><div class="lock-progress-bar" id="lockProgressBar"></div></div>
                        <div class="mt-3">
                            <a href="forgot_password.php" class="forgot-link">
                                <i class="fas fa-key me-1"></i>Reset your password instead?
                            </a>
                        </div>
                    </div>
                    <script>
                        (function() {
                            var seconds = <?php echo (int)$locked_seconds; ?>;
                            var total = seconds;
                            var el = document.getElementById('lockCountdown');
                            var bar = document.getElementById('lockProgressBar');
                            var tick = setInterval(function() {
                                seconds--;
                                if (el) el.textContent = Math.max(seconds, 0);
                                if (bar) bar.style.width = (Math.max(seconds, 0) / total * 100) + '%';
                                if (seconds <= 0) {
                                    clearInterval(tick);
                                    window.location.reload();
                                }
                            }, 1000);
                        })();
                    </script>
                <?php else: ?>

                    <?php if (!empty($display_errors)): ?>
                        <div class="alert-container" id="alertContainer">
                            <?php foreach ($display_errors as $error):
                                // Determine alert type, icon, and message
                                if (strpos($error, 'Wrong password') !== false) {
                                    $alert_class = 'alert-wrong-password';
                                    $alert_icon = 'fas fa-key';
                                    $alert_msg = 'Wrong password. Please try again.';
                                    if ($attempts_remaining !== null && $attempts_remaining > 0) {
                                        $alert_msg .= ' (' . $attempts_remaining . ' attempt' . ($attempts_remaining === 1 ? '' : 's') . ' left before a temporary lock.)';
                                    }
                                } elseif (strpos($error, 'Account not found') !== false) {
                                    $alert_class = 'alert-not-found';
                                    $alert_icon = 'fas fa-user-xmark';
                                    $alert_msg = 'Account not found. Please check your username.';
                                } elseif (strpos($error, 'Too many login attempts') !== false) {
                                    $alert_class = 'alert-locked';
                                    $alert_icon = 'fas fa-ban';
                                    $alert_msg = 'Too many failed attempts. Please wait 5 minutes.';
                                } elseif (strpos($error, 'Account is disabled') !== false) {
                                    $alert_class = 'alert-locked';
                                    $alert_icon = 'fas fa-user-lock';
                                    $alert_msg = 'Account is disabled. Contact your administrator.';
                                } elseif ($error === 'security_error') {
                                    $alert_class = 'alert-danger';
                                    $alert_icon = 'fas fa-shield-exclamation';
                                    $alert_msg = 'Invalid request. Please try again.';
                                } else {
                                    $alert_class = 'alert-danger';
                                    $alert_icon = 'fas fa-exclamation-circle';
                                    $alert_msg = htmlspecialchars($error);
                                }
                            ?>
                                <div class="alert <?php echo $alert_class; ?>" role="alert">
                                    <i class="<?php echo $alert_icon; ?>"></i>
                                    <div class="alert-text"><?php echo $alert_msg; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="loginForm" novalidate autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="_ts" value="<?php echo $form_timestamp; ?>">
                        <input type="hidden" name="system_offline" id="system_offline" value="<?php echo $is_system_online ? '0' : '1'; ?>">

                        <div class="hp-field" aria-hidden="true">
                            <input type="text" name="website_url" id="website_url" tabindex="-1" autocomplete="off">
                        </div>

                        <!-- Username / Email -->
                        <div class="form-group">
                            <label for="username" class="form-label">Username or Email</label>
                            <div class="input-wrap">
                                <input type="text" class="form-control" id="username" name="username"
                                       value="<?php echo htmlspecialchars($username_value); ?>"
                                       placeholder="Enter your username or email" required autocomplete="username"
                                       maxlength="100">
                                <i class="fas fa-user field-icon"></i>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-wrap">
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Enter your password" required autocomplete="new-password"
                                       maxlength="128">
                                <i class="fas fa-lock field-icon"></i>
                                <button type="button" class="pass-toggle" id="togglePassword"
                                        aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember + Forgot -->
                        <div class="options-row">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember"
                                       <?php if ($remember_checked) echo 'checked'; ?>>
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                        </div>

                        <!-- reCAPTCHA Container -->
                        <div class="captcha-box <?php echo in_array('recaptcha_error', $errors) ? 'has-error' : ''; ?>" id="captchaBox" style="<?php echo $is_system_online ? '' : 'display: none;'; ?>">
                            <div class="captcha-label">
                                <i class="fas fa-shield-halved"></i> Security Verification
                            </div>
                            <div class="g-recaptcha"
                                 data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"
                                 data-callback="onRecaptchaSuccess"
                                 data-expired-callback="onRecaptchaExpired"></div>
                            <?php if (in_array('recaptcha_error', $errors)): ?>
                                <div class="captcha-error" id="captchaErr">
                                    <i class="fas fa-exclamation-triangle"></i> Please verify that you are not a robot.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Login -->
                        <button type="submit" class="btn btn-login" id="loginBtn" <?php echo $is_system_online ? 'disabled' : ''; ?>>
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </form>

                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="form-footer">
                <p class="footer-note">&copy; <?php echo date('Y'); ?> Agrivet Inventory Supply</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var systemIsOnline = <?php echo $is_system_online ? 'true' : 'false'; ?>;

        function onRecaptchaScriptError() {
            systemIsOnline = false;
            applyOnlineOfflineState();
        }

        function applyOnlineOfflineState() {
            var online = (typeof navigator !== 'undefined' ? navigator.onLine : true) && systemIsOnline;
            var captchaBox = document.getElementById('captchaBox');
            var loginBtn = document.getElementById('loginBtn');
            var offlineInput = document.getElementById('system_offline');

            if (!online) {
                if (captchaBox) {
                    captchaBox.style.display = 'none';
                    captchaBox.setAttribute('aria-hidden', 'true');
                }
                if (loginBtn) {
                    loginBtn.disabled = false;
                }
                if (offlineInput) {
                    offlineInput.value = '1';
                }
            } else {
                if (captchaBox) {
                    captchaBox.style.display = '';
                    captchaBox.removeAttribute('aria-hidden');
                }
                if (offlineInput) {
                    offlineInput.value = '0';
                }
                var rcResp = document.querySelector('[name="g-recaptcha-response"]');
                if (loginBtn && (!rcResp || !rcResp.value)) {
                    loginBtn.disabled = true;
                }
            }
        }

        function onRecaptchaSuccess() {
            var btn = document.getElementById('loginBtn');
            var box = document.getElementById('captchaBox');
            var err = document.getElementById('captchaErr');
            if (btn) btn.disabled = false;
            if (box) { box.classList.remove('has-error'); box.classList.add('verified'); }
            if (err) err.style.display = 'none';
        }

        function onRecaptchaExpired() {
            var btn = document.getElementById('loginBtn');
            var box = document.getElementById('captchaBox');
            if (btn) btn.disabled = true;
            if (box) box.classList.remove('verified');
        }

        document.addEventListener('DOMContentLoaded', function() {
            var username = document.getElementById('username');
            var password = document.getElementById('password');

            // Disable automatic password saving / autofill
            if (password) {
                password.value = '';
                password.setAttribute('autocomplete', 'new-password');
            }
            if (username) {
                username.setAttribute('autocomplete', 'off');
            }

            applyOnlineOfflineState();

            window.addEventListener('online', function() {
                systemIsOnline = true;
                applyOnlineOfflineState();
            });
            window.addEventListener('offline', function() {
                systemIsOnline = false;
                applyOnlineOfflineState();
            });

            if (username && !username.value) username.focus();
            else if (password && !password.value) password.focus();

            // Clear password on pageshow (e.g. back button navigation)
            window.addEventListener('pageshow', function(e) {
                if (password) password.value = '';
            });

            // Auto-collapse alerts after 5 seconds
            var alerts = document.querySelectorAll('.alert, .info-notice');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.classList.add('collapsing-out');
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                        var container = document.getElementById('alertContainer');
                        if (container && container.children.length === 0) {
                            container.parentNode.removeChild(container);
                        }
                    }, 500);
                }, 5000);
            });

            // Password toggle
            var tog = document.getElementById('togglePassword');
            if (tog && password) {
                tog.addEventListener('click', function(e) {
                    e.preventDefault();
                    var t = password.type === 'password' ? 'text' : 'password';
                    password.type = t;
                    var ic = tog.querySelector('i');
                    ic.classList.toggle('fa-eye');
                    ic.classList.toggle('fa-eye-slash');
                });
            }

            // Form submit
            var form = document.getElementById('loginForm');
            var btn = document.getElementById('loginBtn');

            if (form && btn) {
                form.addEventListener('submit', function(e) {
                    var u = username ? username.value.trim() : '';
                    var p = password ? password.value : '';

                    if (u.length < 1) { e.preventDefault(); username.focus(); username.classList.add('is-invalid'); return; }
                    if (p.length < 1) { e.preventDefault(); password.focus(); password.classList.add('is-invalid'); return; }

                    var isOnline = (typeof navigator !== 'undefined' ? navigator.onLine : true) && systemIsOnline;
                    var captchaBox = document.getElementById('captchaBox');
                    var captchaVisible = captchaBox && captchaBox.style.display !== 'none';

                    if (isOnline && captchaVisible) {
                        var rc = document.querySelector('[name="g-recaptcha-response"]');
                        if (!rc || !rc.value) {
                            e.preventDefault();
                            captchaBox.classList.add('has-error');
                            if (!document.getElementById('captchaErr')) {
                                var d = document.createElement('div');
                                d.className = 'captcha-error'; d.id = 'captchaErr';
                                d.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please verify that you are not a robot.';
                                captchaBox.appendChild(d);
                            }
                            return;
                        }
                    }

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing in...';
                });

                [username, password].forEach(function(f) {
                    if (f) f.addEventListener('input', function() { this.classList.remove('is-invalid'); });
                });
            }

            var hp = document.getElementById('website_url');
            if (hp) hp.addEventListener('focus', function() { this.blur(); });
        });
    </script>
</body>
</html>
