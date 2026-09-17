<?php 
require_once __DIR__ . '/bootstrap_simple.php';

// Redirect if already logged in
if (SimpleSecurity::isAuthenticated()) {
    SimpleSecurity::redirect('dashboard_secure.php');
}

$csrf_token = SimpleSecurity::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Inventory Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 480px;
        }

        .forgot-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            width: 100%;
            animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(24px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .forgot-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2.25rem 2rem 1.75rem;
            text-align: center;
            position: relative;
        }

        .forgot-header .logo-circle {
            width: 68px;
            height: 68px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .forgot-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.6rem;
            letter-spacing: -0.5px;
        }

        .forgot-header p {
            opacity: 0.9;
            margin: 6px 0 0;
            font-size: 0.88rem;
        }

        /* Step Progression Indicators */
        .step-progress {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 1.25rem;
        }

        .step-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.75);
            transition: all 0.3s ease;
        }

        .step-pill.active {
            background: #ffffff;
            color: #1e3c72;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .step-pill.completed {
            background: rgba(34, 197, 94, 0.25);
            color: #86efac;
        }

        .forgot-body {
            padding: 2.25rem 2rem;
        }

        .step-view {
            display: none;
            animation: fadeIn 0.3s ease forwards;
        }

        .step-view.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 0.9rem 1.15rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .alert-danger-custom {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        .alert-success-custom {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            color: #166534;
        }

        .alert-info-custom {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            color: #1e40af;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label i {
            color: #2563eb;
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            transition: all 0.25s ease;
            font-size: 0.95rem;
            background: #f8fafc;
            color: #1e293b;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        /* 6-Digit OTP Box */
        .otp-input-field {
            font-size: 2.2rem;
            letter-spacing: 12px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            text-align: center;
            font-weight: 700;
            color: #1e3c72;
            border-radius: 14px;
            background: #f1f5f9;
            border: 2px solid #cbd5e1;
            padding: 12px;
        }

        .otp-input-field:focus {
            background: #ffffff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        }

        .otp-timer-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 0.82rem;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            border-radius: 12px;
            padding: 13px;
            font-weight: 700;
            transition: all 0.25s ease;
            color: white;
            width: 100%;
            font-size: 0.98rem;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 16px rgba(30, 60, 114, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-primary-custom:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(30, 60, 114, 0.35);
            color: white;
        }

        .btn-primary-custom:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .btn-secondary-custom {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            color: #475569;
            width: 100%;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-secondary-custom:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        /* Password strength indicator */
        .password-requirements {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 1.25rem;
            font-size: 0.8rem;
        }

        .password-requirements h6 {
            color: #1e3c72;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 0.82rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            margin-bottom: 4px;
            transition: color 0.2s ease;
        }

        .requirement.met {
            color: #16a34a;
            font-weight: 600;
        }

        .requirement i {
            width: 14px;
            text-align: center;
            font-size: 0.75rem;
        }

        .pass-input-group {
            position: relative;
        }

        .pass-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        .pass-toggle-btn:hover {
            color: #2563eb;
        }

        .success-animation {
            text-align: center;
            padding: 1.5rem 0;
        }

        .success-icon-wrap {
            width: 80px;
            height: 80px;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1.25rem;
            animation: bounceIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes bounceIn {
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(1); }
        }

        .footer-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid #e2e8f0;
        }

        .footer-links a {
            color: #2563eb;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: #1e3c72;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="forgot-container">
            
            <!-- Header -->
            <div class="forgot-header">
                <div class="logo-circle" id="headerIcon">
                    <i class="fas fa-lock"></i>
                </div>
                <h2 id="headerTitle">Reset Password</h2>
                <p id="headerSubtitle">Enter your username or email to recover your account</p>

                <!-- Steps Progress Indicator -->
                <div class="step-progress">
                    <div class="step-pill active" id="pillStep1">
                        <i class="fas fa-user"></i> 1. Identify
                    </div>
                    <div class="step-pill" id="pillStep2">
                        <i class="fas fa-shield-alt"></i> 2. Verify OTP
                    </div>
                    <div class="step-pill" id="pillStep3">
                        <i class="fas fa-key"></i> 3. New Password
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="forgot-body">
                
                <!-- Shared Alert Container -->
                <div id="alertBox"></div>

                <!-- ════ STEP 1: IDENTIFY USER ════ -->
                <div class="step-view active" id="step1View">
                    <form id="formRequestOtp" onsubmit="handleRequestOtp(event)">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="mb-3">
                            <label for="identifier" class="form-label">
                                <i class="fas fa-user"></i> Username or Email Address
                            </label>
                            <input type="text" class="form-control" id="identifier" name="identifier" 
                                   placeholder="Enter your registered username or email" required 
                                   autocomplete="username" autofocus>
                            <div class="text-muted mt-2" style="font-size:0.80rem;">
                                <i class="fas fa-info-circle me-1"></i> A 6-digit OTP verification code will be sent to your registered email.
                            </div>
                        </div>

                        <button type="submit" class="btn-primary-custom" id="btnRequestOtp">
                            <i class="fas fa-paper-plane me-1"></i> Send Verification Code
                        </button>
                    </form>

                    <div class="footer-links">
                        <a href="login_secure.php">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
                </div>

                <!-- ════ STEP 2: VERIFY 6-DIGIT OTP ════ -->
                <div class="step-view" id="step2View">
                    <div class="text-center mb-3">
                        <p class="text-muted" style="font-size:0.88rem; margin-bottom:4px;">
                            Verification code dispatched to:
                        </p>
                        <strong id="maskedEmailDisplay" class="text-dark" style="font-size:0.98rem; letter-spacing:0.5px;">email@...</strong>
                    </div>

                    <form id="formVerifyOtp" onsubmit="handleVerifyOtp(event)">
                        <div class="mb-3">
                            <label for="otpCodeInput" class="form-label justify-content-center">
                                <i class="fas fa-shield-halved"></i> Enter 6-Digit OTP Code
                            </label>
                            <input type="text" class="form-control otp-input-field" id="otpCodeInput" name="otp_code" 
                                   placeholder="123456" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" 
                                   autocomplete="one-time-code" required>
                            
                            <div class="otp-timer-box">
                                <span class="text-muted">
                                    <i class="fas fa-clock text-danger me-1"></i> Expires in <strong id="otpCountdown" class="text-danger">10:00</strong>
                                </span>
                                <button type="button" class="btn btn-link p-0 text-decoration-none fw-semibold" id="btnResendOtp" onclick="handleResendOtp()" disabled style="font-size:0.82rem;">
                                    <i class="fas fa-rotate-right me-1"></i> Resend Code (<span id="resendSeconds">60</span>s)
                                </button>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-2 mt-4">
                            <button type="submit" class="btn-primary-custom" id="btnVerifyOtp">
                                <i class="fas fa-check-circle me-1"></i> Verify Code &amp; Continue
                            </button>
                            <button type="button" class="btn-secondary-custom" onclick="goToStep(1)">
                                <i class="fas fa-arrow-left me-1"></i> Change Username/Email
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ════ STEP 3: CREATE NEW PASSWORD ════ -->
                <div class="step-view" id="step3View">
                    <form id="formResetPassword" onsubmit="handleResetPassword(event)">
                        
                        <!-- Password Strength Checklist -->
                        <div class="password-requirements">
                            <h6><i class="fas fa-shield-alt me-1"></i> Password Security Requirements:</h6>
                            <div class="requirement" id="reqLength"><i class="fas fa-circle"></i> At least 8 characters</div>
                            <div class="requirement" id="reqUpper"><i class="fas fa-circle"></i> One uppercase letter (A-Z)</div>
                            <div class="requirement" id="reqLower"><i class="fas fa-circle"></i> One lowercase letter (a-z)</div>
                            <div class="requirement" id="reqNumber"><i class="fas fa-circle"></i> One number (0-9)</div>
                            <div class="requirement" id="reqSpecial"><i class="fas fa-circle"></i> One special character (!@#$%)</div>
                        </div>

                        <div class="mb-3">
                            <label for="newPassword" class="form-label">
                                <i class="fas fa-lock"></i> New Password
                            </label>
                            <div class="pass-input-group">
                                <input type="password" class="form-control" id="newPassword" name="password" 
                                       placeholder="Enter your new password" required autocomplete="new-password">
                                <button type="button" class="pass-toggle-btn" onclick="togglePassVisibility('newPassword', this)" title="Show/hide password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirmPassword" class="form-label">
                                <i class="fas fa-lock"></i> Confirm New Password
                            </label>
                            <div class="pass-input-group">
                                <input type="password" class="form-control" id="confirmPassword" name="password_confirm" 
                                       placeholder="Re-enter your new password" required autocomplete="new-password">
                                <button type="button" class="pass-toggle-btn" onclick="togglePassVisibility('confirmPassword', this)" title="Show/hide password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary-custom" id="btnSubmitNewPassword">
                            <i class="fas fa-key me-1"></i> Update &amp; Save New Password
                        </button>
                    </form>
                </div>

                <!-- ════ STEP 4: SUCCESS CONFIRMATION ════ -->
                <div class="step-view" id="step4View">
                    <div class="success-animation">
                        <div class="success-icon-wrap">
                            <i class="fas fa-check"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Password Reset Successfully</h4>
                        <p class="text-muted small mb-4">
                            Your password has been securely updated in the database. You will be redirected to the login page momentarily...
                        </p>
                        <a href="login_secure.php?reset=success" class="btn-primary-custom text-decoration-none">
                            <i class="fas fa-sign-in-alt me-1"></i> Proceed to Login Now
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const CSRF_TOKEN = '<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>';
        let currentResetSessionToken = null;
        let otpCountdownInterval = null;
        let resendCountdownInterval = null;

        function showAlert(message, type = 'danger') {
            const icon = type === 'success' ? 'fa-check-circle' : (type === 'info' ? 'fa-info-circle' : 'fa-exclamation-circle');
            const alertHtml = `
                <div class="alert-custom alert-${type}-custom">
                    <i class="fas ${icon} mt-1"></i>
                    <div style="flex:1;">${message}</div>
                </div>
            `;
            document.getElementById('alertBox').innerHTML = alertHtml;
        }

        function clearAlert() {
            document.getElementById('alertBox').innerHTML = '';
        }

        function goToStep(step) {
            clearAlert();
            document.querySelectorAll('.step-view').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.step-pill').forEach(el => el.classList.remove('active', 'completed'));

            const headerIcon = document.getElementById('headerIcon');
            const headerTitle = document.getElementById('headerTitle');
            const headerSubtitle = document.getElementById('headerSubtitle');

            if (step === 1) {
                document.getElementById('step1View').classList.add('active');
                document.getElementById('pillStep1').classList.add('active');
                headerIcon.innerHTML = '<i class="fas fa-lock"></i>';
                headerTitle.textContent = 'Reset Password';
                headerSubtitle.textContent = 'Enter your username or email to recover your account';
                document.getElementById('identifier').focus();
            } else if (step === 2) {
                document.getElementById('step2View').classList.add('active');
                document.getElementById('pillStep1').classList.add('completed');
                document.getElementById('pillStep2').classList.add('active');
                headerIcon.innerHTML = '<i class="fas fa-envelope-open-text"></i>';
                headerTitle.textContent = 'Enter Verification Code';
                headerSubtitle.textContent = 'Check your email for the 6-digit OTP code';
                document.getElementById('otpCodeInput').value = '';
                setTimeout(() => document.getElementById('otpCodeInput').focus(), 300);
            } else if (step === 3) {
                document.getElementById('step3View').classList.add('active');
                document.getElementById('pillStep1').classList.add('completed');
                document.getElementById('pillStep2').classList.add('completed');
                document.getElementById('pillStep3').classList.add('active');
                headerIcon.innerHTML = '<i class="fas fa-key"></i>';
                headerTitle.textContent = 'Create New Password';
                headerSubtitle.textContent = 'Choose a strong, secure password for your account';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                setTimeout(() => document.getElementById('newPassword').focus(), 300);
            } else if (step === 4) {
                document.getElementById('step4View').classList.add('active');
                document.getElementById('pillStep1').classList.add('completed');
                document.getElementById('pillStep2').classList.add('completed');
                document.getElementById('pillStep3').classList.add('completed');
                headerIcon.innerHTML = '<i class="fas fa-circle-check"></i>';
                headerTitle.textContent = 'Reset Complete';
                headerSubtitle.textContent = 'Your account has been secured';
            }
        }

        // ════ STEP 1: REQUEST OTP ════
        async function handleRequestOtp(e) {
            e.preventDefault();
            clearAlert();

            const identifier = document.getElementById('identifier').value.trim();
            if (!identifier) {
                showAlert('Please enter your username or email address.');
                return;
            }

            const btn = document.getElementById('btnRequestOtp');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Transmitting Verification Code...';

            try {
                const formData = new URLSearchParams();
                formData.append('action', 'request_otp');
                formData.append('identifier', identifier);
                formData.append('csrf_token', CSRF_TOKEN);

                const res = await fetch('php_action/forgotPasswordOtp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });

                const data = await res.json();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Verification Code';

                if (data.success) {
                    currentResetSessionToken = data.reset_session_token;
                    document.getElementById('maskedEmailDisplay').textContent = data.recipient_masked || 'your email';

                    startOtpCountdown(data.expires_in || 600);
                    startResendCooldown(60);
                    goToStep(2);
                    showAlert(data.messages, 'success');
                } else {
                    showAlert(data.messages || 'Unable to process password reset request.');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Verification Code';
                showAlert('A network error occurred while communicating with the server. Please try again.');
            }
        }

        // ════ STEP 2: VERIFY OTP ════
        async function handleVerifyOtp(e) {
            e.preventDefault();
            clearAlert();

            const otpCode = document.getElementById('otpCodeInput').value.trim();
            if (!otpCode || otpCode.length !== 6) {
                showAlert('Please enter the complete 6-digit verification code.');
                return;
            }

            const btn = document.getElementById('btnVerifyOtp');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Validating Code...';

            try {
                const formData = new URLSearchParams();
                formData.append('action', 'verify_otp');
                formData.append('reset_session_token', currentResetSessionToken);
                formData.append('otp_code', otpCode);
                formData.append('csrf_token', CSRF_TOKEN);

                const res = await fetch('php_action/forgotPasswordOtp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });

                const data = await res.json();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Verify Code &amp; Continue';

                if (data.success) {
                    clearInterval(otpCountdownInterval);
                    clearInterval(resendCountdownInterval);
                    goToStep(3);
                    showAlert(data.messages, 'success');
                } else {
                    showAlert(data.messages || 'Verification code failed validation.');
                    document.getElementById('otpCodeInput').select();
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Verify Code &amp; Continue';
                showAlert('A network error occurred. Please try again.');
            }
        }

        // ════ STEP 2b: RESEND OTP ════
        async function handleResendOtp() {
            if (!currentResetSessionToken) return;

            const btn = document.getElementById('btnResendOtp');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';

            try {
                const formData = new URLSearchParams();
                formData.append('action', 'resend_otp');
                formData.append('reset_session_token', currentResetSessionToken);
                formData.append('csrf_token', CSRF_TOKEN);

                const res = await fetch('php_action/forgotPasswordOtp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });

                const data = await res.json();
                if (data.success) {
                    startOtpCountdown(600);
                    startResendCooldown(60);
                    showAlert(data.messages, 'success');
                } else {
                    showAlert(data.messages || 'Failed to resend verification code.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-rotate-right me-1"></i> Resend Code';
                }
            } catch (err) {
                showAlert('Network error resending verification code.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-rotate-right me-1"></i> Resend Code';
            }
        }

        // ════ STEP 3: RESET PASSWORD ════
        async function handleResetPassword(e) {
            e.preventDefault();
            clearAlert();

            const password = document.getElementById('newPassword').value;
            const passwordConfirm = document.getElementById('confirmPassword').value;

            if (!password) {
                showAlert('Please enter a new password.');
                return;
            }

            if (password.length < 8) {
                showAlert('Password must be at least 8 characters long.');
                return;
            }

            if (password !== passwordConfirm) {
                showAlert('New password and confirm password do not match.');
                return;
            }

            // Check strength
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);

            if (!hasUpper || !hasLower || !hasNumber || !hasSpecial) {
                showAlert('Please satisfy all password security requirements shown above.');
                return;
            }

            const btn = document.getElementById('btnSubmitNewPassword');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Securing Account &amp; Updating...';

            try {
                const formData = new URLSearchParams();
                formData.append('action', 'reset_password');
                formData.append('reset_session_token', currentResetSessionToken);
                formData.append('password', password);
                formData.append('password_confirm', passwordConfirm);
                formData.append('csrf_token', CSRF_TOKEN);

                const res = await fetch('php_action/forgotPasswordOtp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });

                const data = await res.json();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-key me-1"></i> Update &amp; Save New Password';

                if (data.success) {
                    goToStep(4);
                    setTimeout(() => {
                        window.location.href = data.redirect || 'login_secure.php?reset=success';
                    }, 3000);
                } else {
                    showAlert(data.messages || 'Failed to update password.');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-key me-1"></i> Update &amp; Save New Password';
                showAlert('A network error occurred while updating your password. Please try again.');
            }
        }

        // ════ TIMERS & COUNTDOWNS ════
        function startOtpCountdown(seconds) {
            clearInterval(otpCountdownInterval);
            let remaining = seconds;
            const el = document.getElementById('otpCountdown');

            function update() {
                const m = Math.floor(remaining / 60);
                const s = remaining % 60;
                if (el) el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;

                if (remaining <= 0) {
                    clearInterval(otpCountdownInterval);
                    showAlert('Your verification code has expired. Please click Resend Code.', 'danger');
                }
                remaining--;
            }
            update();
            otpCountdownInterval = setInterval(update, 1000);
        }

        function startResendCooldown(seconds) {
            clearInterval(resendCountdownInterval);
            let remaining = seconds;
            const btn = document.getElementById('btnResendOtp');
            const secSpan = document.getElementById('resendSeconds');

            btn.disabled = true;

            function update() {
                if (remaining <= 0) {
                    clearInterval(resendCountdownInterval);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-rotate-right me-1"></i> Resend Code';
                } else {
                    btn.innerHTML = `<i class="fas fa-rotate-right me-1"></i> Resend Code (${remaining}s)`;
                    remaining--;
                }
            }
            update();
            resendCountdownInterval = setInterval(update, 1000);
        }

        // ════ PASSWORD TOGGLE & STRENGTH ════
        function togglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const newPass = document.getElementById('newPassword');
            if (newPass) {
                newPass.addEventListener('input', function() {
                    const val = this.value;
                    updateRequirement('reqLength', val.length >= 8);
                    updateRequirement('reqUpper', /[A-Z]/.test(val));
                    updateRequirement('reqLower', /[a-z]/.test(val));
                    updateRequirement('reqNumber', /\d/.test(val));
                    updateRequirement('reqSpecial', /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(val));
                });
            }

            // OTP Input formatting: only digits
            const otpInput = document.getElementById('otpCodeInput');
            if (otpInput) {
                otpInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value.length === 6) {
                        // Auto-submit if 6 digits entered
                        document.getElementById('btnVerifyOtp').focus();
                    }
                });
            }
        });

        function updateRequirement(id, isMet) {
            const el = document.getElementById(id);
            if (!el) return;
            const icon = el.querySelector('i');
            if (isMet) {
                el.classList.add('met');
                icon.className = 'fas fa-check-circle';
            } else {
                el.classList.remove('met');
                icon.className = 'fas fa-circle';
            }
        }
    </script>
</body>
</html>
