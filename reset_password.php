<?php 
require_once __DIR__ . '/bootstrap_simple.php';

// Redirect if already logged in
if (SimpleSecurity::isAuthenticated()) {
    SimpleSecurity::redirect('dashboard_secure.php');
}

$errors = [];
$success = false;
$token_valid = false;
$user_id = null;
$token = $_GET['token'] ?? '';

$db = new SimpleDatabase();

// Validate token if provided
if (!empty($token)) {
    // Must match the deterministic hash forgot_password.php now stores
    // (SimpleSecurity::hashToken, SHA-256) — the old hashPassword() (bcrypt,
    // random salt per call) could never match a previously-stored hash of
    // the same raw token, which made every reset link permanently invalid.
    $tokenHash = SimpleSecurity::hashToken($token);

    // Check if token exists and hasn't expired
    $resetRecord = $db->fetch(
        "SELECT * FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()",
        [$tokenHash]
    );
    
    if ($resetRecord) {
        $token_valid = true;
        $user_id = $resetRecord['user_id'];
    } else {
        $errors[] = "This password reset link is invalid or has expired. Please request a new one.";
    }
} else {
    $errors[] = "No reset token provided. Please use the link from your email.";
}

// Handle password reset form
if ($_POST && $token_valid) {
    // Validate CSRF token
    if (!SimpleSecurity::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $token_hidden = $_POST['token'] ?? '';
        
        // Validate passwords
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif ($password !== $password_confirm) {
            $errors[] = "Passwords do not match.";
        } else {
            // Check password strength
            $strength = SimpleSecurity::checkPasswordStrength($password);
            if (!$strength['isStrong']) {
                $errors[] = "Password is too weak. Use a mix of uppercase, lowercase, numbers, and special characters.";
            }
        }

        if (empty($errors)) {
            // Update password
            $hashedPassword = SimpleSecurity::hashPassword($password);

            $result = $db->execute(
                "UPDATE users SET password = ?, failed_login_attempts = 0, locked_until = NULL WHERE user_id = ?",
                [$hashedPassword, $user_id]
            );

            if ($result['affected_rows'] > 0) {
                // Delete used token so it can't be replayed
                $db->execute("DELETE FROM password_reset_tokens WHERE token = ?", [$tokenHash]);

                $success = true;
                $token_valid = false;
            } else {
                $errors[] = "An error occurred while updating your password. Please try again.";
            }
        }
    }
}

// Generate CSRF token for form
$csrf_token = SimpleSecurity::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Inventory Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .container-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 100vh;
        }

        .reset-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .reset-header h2 {
            margin: 0;
            font-weight: 300;
            font-size: 1.8rem;
        }

        .reset-header .logo {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .reset-body {
            padding: 2.5rem 2rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background-color: #fff5f5;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
        }

        .alert-success {
            background-color: #f0fff4;
            border-left: 4px solid #27ae60;
            color: #1e7e34;
        }

        .alert i {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert-text {
            flex: 1;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .form-label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 0.7rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: #667eea;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 1rem;
            background: #f9f9f9;
        }

        .form-control:focus {
            background: white;
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
            outline: none;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 38px;
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .password-toggle:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #764ba2;
        }

        .password-requirements {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }

        .password-requirements h6 {
            color: #667eea;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #999;
            margin-bottom: 5px;
        }

        .requirement.met {
            color: #27ae60;
        }

        .requirement i {
            width: 16px;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            width: 100%;
            font-size: 1rem;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            margin-bottom: 1rem;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #f0f0f0;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #666;
            width: 100%;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
            color: #333;
        }

        .success-icon {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .success-icon i {
            font-size: 3rem;
            color: #27ae60;
        }

        @media (max-width: 576px) {
            .reset-container {
                border-radius: 15px;
            }

            .reset-header {
                padding: 2rem 1.5rem;
            }

            .reset-header h2 {
                font-size: 1.5rem;
            }

            .reset-header .logo {
                font-size: 2rem;
                margin-bottom: 0.75rem;
            }

            .reset-body {
                padding: 1.75rem 1.5rem;
            }

            .form-control {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="reset-container">
            <!-- Header -->
            <div class="reset-header">
                <div class="logo">
                    <i class="fas fa-key"></i>
                </div>
                <h2>Create New Password</h2>
                <p>Set a strong password for your account</p>
            </div>

            <!-- Body -->
            <div class="reset-body">
                <?php if ($success): ?>
                    <!-- Success Message -->
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div class="alert-text">
                            <strong>Password Reset Successful!</strong>
                            <br>Your password has been securely updated. You can now log in with your new password.
                        </div>
                    </div>

                    <a href="login_secure.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                    </a>
                <?php elseif ($token_valid): ?>
                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert-container">
                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <div class="alert-text"><?php echo htmlspecialchars($error); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Password Requirements -->
                    <div class="password-requirements">
                        <h6><i class="fas fa-shield-alt me-2"></i>Password Requirements</h6>
                        <div class="requirement" id="req-length">
                            <i class="fas fa-circle"></i>
                            <span>At least 8 characters</span>
                        </div>
                        <div class="requirement" id="req-upper">
                            <i class="fas fa-circle"></i>
                            <span>One uppercase letter (A-Z)</span>
                        </div>
                        <div class="requirement" id="req-lower">
                            <i class="fas fa-circle"></i>
                            <span>One lowercase letter (a-z)</span>
                        </div>
                        <div class="requirement" id="req-number">
                            <i class="fas fa-circle"></i>
                            <span>One number (0-9)</span>
                        </div>
                        <div class="requirement" id="req-special">
                            <i class="fas fa-circle"></i>
                            <span>One special character (!@#$%)</span>
                        </div>
                    </div>

                    <!-- Reset Form -->
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?token=' . urlencode($token); ?>" id="resetForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i>New Password
                            </label>
                            <div style="position: relative;">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your new password" required 
                                       autocomplete="new-password" autofocus>
                                <button type="button" class="password-toggle" id="togglePassword" 
                                        title="Show/hide password" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm" class="form-label">
                                <i class="fas fa-lock"></i>Confirm Password
                            </label>
                            <div style="position: relative;">
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                       placeholder="Confirm your new password" required 
                                       autocomplete="new-password">
                                <button type="button" class="password-toggle" id="togglePasswordConfirm" 
                                        title="Show/hide password" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-check me-2"></i>Reset Password
                        </button>
                    </form>
                <?php else: ?>
                    <!-- Invalid Token Message -->
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i>
                        <div class="alert-text">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <a href="forgot_password.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Request New Reset Link
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const setupPasswordToggle = (toggleSelector, targetSelector) => {
                const toggleBtn = document.querySelector(toggleSelector);
                const field = document.querySelector(targetSelector);
                
                if (toggleBtn && field) {
                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
                        field.setAttribute('type', type);
                        
                        const icon = toggleBtn.querySelector('i');
                        if (type === 'text') {
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        } else {
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    });
                }
            };

            setupPasswordToggle('#togglePassword', '#password');
            setupPasswordToggle('#togglePasswordConfirm', '#password_confirm');

            // Password strength checker
            const passwordField = document.getElementById('password');
            if (passwordField) {
                passwordField.addEventListener('input', function() {
                    const password = this.value;
                    
                    // Check requirements
                    const hasLength = password.length >= 8;
                    const hasUpper = /[A-Z]/.test(password);
                    const hasLower = /[a-z]/.test(password);
                    const hasNumber = /\d/.test(password);
                    const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);

                    // Update UI
                    updateRequirement('req-length', hasLength);
                    updateRequirement('req-upper', hasUpper);
                    updateRequirement('req-lower', hasLower);
                    updateRequirement('req-number', hasNumber);
                    updateRequirement('req-special', hasSpecial);
                });
            }

            const updateRequirement = (id, met) => {
                const element = document.getElementById(id);
                if (element) {
                    if (met) {
                        element.classList.add('met');
                        element.querySelector('i').classList.remove('fa-circle');
                        element.querySelector('i').classList.add('fa-check-circle');
                    } else {
                        element.classList.remove('met');
                        element.querySelector('i').classList.add('fa-circle');
                        element.querySelector('i').classList.remove('fa-check-circle');
                    }
                }
            };

            // Form submission
            const form = document.getElementById('resetForm');
            const submitBtn = document.getElementById('submitBtn');

            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    submitBtn.disabled = true;
                    const originalHTML = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
                    
                    setTimeout(() => {
                        if (!form.reportValidity()) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHTML;
                        }
                    }, 100);
                });
            }
        });
    </script>
</body>
</html>
