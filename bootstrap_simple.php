<?php
/**
 * Simple Application Bootstrap
 * 
 * This file initializes the application with minimal dependencies
 * to work with the existing database structure.
 */

// Define application start time
define('APP_STARTED', true);
define('APP_START_TIME', microtime(true));
define('APP_ROOT', __DIR__);

// Load configuration
require_once __DIR__ . '/config/app_simple.php';

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Load existing database connection
require_once __DIR__ . '/php_action/db_connect.php';

// Simple session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple security functions
if (!class_exists('SimpleSecurity')) {
class SimpleSecurity {
    public static function checkInactivity() {
        if (self::isAuthenticated()) {
            $timeout = defined('SESSION_INACTIVITY_TIMEOUT') ? SESSION_INACTIVITY_TIMEOUT : 900;
            if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > $timeout) {
                $_SESSION = array();
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                @session_destroy();

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'session_expired' => true,
                        'message' => 'Session expired due to 15 minutes of inactivity.',
                        'redirect' => 'login_secure.php?error=session_expired'
                    ]);
                    exit;
                }

                self::redirect('login_secure.php?error=session_expired');
            }
            $_SESSION['last_activity'] = time();
        }
    }

    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        // Input normalization is not output escaping. Encoding here changes
        // usernames/emails before database lookup (for example "&" becomes
        // "&amp;"). Escape only at the HTML sink with htmlspecialchars().
        return trim((string)$data);
    }
    
    public static function isAuthenticated() {
        return !empty($_SESSION['userId']) || !empty($_SESSION['user_id']) || !empty($_SESSION['id']);
    }
    
    public static function getUserId() {
        return $_SESSION['userId'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
    }
    
    public static function getUsername() {
        return $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Admin';
    }
    
    public static function getUserRole() {
        return strtolower((string)($_SESSION['user_role'] ?? $_SESSION['role'] ?? (self::isAdmin() ? 'admin' : 'staff')));
    }

    public static function isAdmin() {
        $role = strtolower((string)($_SESSION['user_role'] ?? $_SESSION['role'] ?? ''));
        if ($role === 'admin') return true;
        if (self::getUserId() == 1) return true;
        return false;
    }

    public static function requireAdmin() {
        if (!self::isAuthenticated()) {
            self::redirect('login_secure.php');
        }
        if (!self::isAdmin()) {
            self::redirect('dashboard_secure.php?error=access_denied');
        }
    }

    /**
     * Whether the current session has been explicitly granted $key, or is an
     * admin (admins implicitly have every permission). $key is one of the
     * grantable permission keys stored in users.custom_permissions —
     * 'settings', 'reports', 'stock_forecast', 'inventory_valuation',
     * 'activity_logs'. User management (user.php) is intentionally NOT
     * grantable through this mechanism — it stays behind requireAdmin() only.
     * As of migrations/013_grant_default_sales_permissions.php, $key can also
     * be one of the sales-floor keys — 'transaction', 'orders',
     * 'customer_orders' — which are granted by default (existing accounts
     * were backfilled, new staff accounts default to having them) but can be
     * restricted per account by an admin. As of
     * migrations/020_grant_default_inventory_permissions.php, the same
     * default-granted-but-restrictable treatment applies to the
     * inventory-floor keys — 'products', 'stock_movement', 'purchase_order'.
     */
    public static function hasPermission($key) {
        if (self::isAdmin()) return true;
        $granted = $_SESSION['custom_permissions'] ?? null;
        if ($granted === null) {
            $defaultStaffPermissions = ['transaction', 'returns', 'orders', 'customer_orders', 'products', 'stock_movement', 'purchase_order'];
            return in_array($key, $defaultStaffPermissions, true);
        }
        return is_array($granted) && in_array($key, $granted, true);
    }

    public static function requirePermission($key) {
        if (!self::isAuthenticated()) {
            self::redirect('login_secure.php');
        }
        if (!self::hasPermission($key)) {
            self::redirect('dashboard_secure.php?error=access_denied');
        }
    }

    public static function redirect($url) {
        header('Location: ' . $url);
        exit();
    }
    
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function verifyPassword($password, $hash) {
        if (password_verify($password, $hash)) {
            return true;
        }
        // Fallback for legacy MD5 passwords — will auto-upgrade on login
        if (strlen($hash) === 32 && (md5($password) === $hash || md5($password) === strtolower($hash))) {
            return true;
        }
        return false;
    }

    /**
     * Check if a password hash needs upgrading (MD5 → bcrypt)
     */
    public static function needsRehash($hash) {
        if (strlen($hash) === 32) return true;
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Cryptographically random hex token (e.g. for password reset links).
     * NOT for passwords — password_hash()'s random salt makes it unsuitable
     * for anything that needs an exact-match DB lookup later.
     */
    public static function generateToken($length = 64) {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }

    /**
     * Deterministic digest for storing/looking up a high-entropy random
     * token (e.g. password reset tokens). Unlike password_hash(), this
     * produces the same output for the same input, which a "WHERE token = ?"
     * lookup requires. Safe here specifically because tokens (unlike
     * passwords) are already high-entropy random values, not something an
     * attacker could feasibly brute-force or rainbow-table even without a
     * per-hash salt or cost factor.
     */
    public static function hashToken($token) {
        return hash('sha256', $token);
    }

    /**
     * Mirrors the client-side requirement checklist already shown on
     * reset_password.php (length, upper, lower, number, special char).
     */
    public static function checkPasswordStrength($password) {
        $checks = [
            'length' => strlen($password) >= 8,
            'upper' => (bool) preg_match('/[A-Z]/', $password),
            'lower' => (bool) preg_match('/[a-z]/', $password),
            'number' => (bool) preg_match('/[0-9]/', $password),
            'special' => (bool) preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]/', $password),
        ];
        return [
            'isStrong' => !in_array(false, $checks, true),
            'checks' => $checks
        ];
    }

    /**
     * Simple flock()-based per-key rate limiter (same mechanics as the
     * IP-based login throttle in SimpleAuth::login(), factored out here so
     * other endpoints — e.g. the password reset request form — can reuse it
     * without duplicating the read-modify-write/locking logic).
     */
    public static function checkRateLimit($bucketKey, $maxAttempts, $windowSeconds) {
        $dir = __DIR__ . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/ratelimit_' . md5($bucketKey) . '.json';
        $count = 0;

        $fh = @fopen($file, 'c+');
        if ($fh !== false) {
            flock($fh, LOCK_EX);
            $raw = stream_get_contents($fh);
            $data = $raw ? (@json_decode($raw, true) ?: null) : null;
            if (!$data || time() - $data['start'] > $windowSeconds) {
                $data = ['count' => 0, 'start' => time()];
            }
            $data['count']++;
            $count = $data['count'];
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode($data));
            fflush($fh);
            flock($fh, LOCK_UN);
            fclose($fh);
        }

        return $count <= $maxAttempts;
    }
}
}

// Simple database wrapper
if (!class_exists('SimpleDatabase')) {
class SimpleDatabase {
    private $connection;
    
    public function __construct() {
        global $connect;
        $this->connection = $connect;
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            error_log('DB prepare failed: ' . $this->connection->error);
            return null;
        }
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            error_log('DB execute failed: ' . $stmt->error);
            return null;
        }
        return $result->fetch_assoc();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            error_log('DB prepare failed: ' . $this->connection->error);
            return ['affected_rows' => 0, 'insert_id' => 0];
        }
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return [
            'affected_rows' => $stmt->affected_rows,
            'insert_id' => $stmt->insert_id
        ];
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
}

// Simple authentication
if (!class_exists('SimpleAuth')) {
class SimpleAuth {
    private $db;

    // Per-account brute-force lockout: N wrong passwords in a row locks that
    // specific account for LOCKOUT_SECONDS, independent of the IP-based
    // throttle below (which protects against volume from one network, not a
    // distributed attack against one specific account).
    const MAX_FAILED_ATTEMPTS = 3;
    const LOCKOUT_SECONDS = 60;

    public function __construct() {
        $this->db = new SimpleDatabase();
    }

    private static function rememberCookieOptions($expires) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['REQUEST_SCHEME'] ?? '') === 'https')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        return [
            'expires' => $expires,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict',
        ];
    }

    private static function clearRememberCookie() {
        setcookie('user_session', '', self::rememberCookieOptions(time() - 3600));
        unset($_COOKIE['user_session']);
    }

    /**
     * Restore a signed "remember me" session. The token includes a fingerprint
     * of the current password hash, so changing/resetting the password
     * immediately invalidates every previously issued cookie.
     */
    public function restoreRememberedSession() {
        if (SimpleSecurity::isAuthenticated() || empty($_COOKIE['user_session'])) {
            return false;
        }

        $decoded = base64_decode($_COOKIE['user_session'], true);
        $parts = $decoded === false ? [] : explode('|', $decoded);
        if (count($parts) !== 5 || $parts[0] !== 'v2' || !ctype_digit($parts[1]) || !ctype_digit($parts[2])) {
            self::clearRememberCookie();
            return false;
        }

        [$version, $userId, $expiry, $passwordFingerprint, $providedSignature] = $parts;
        $expiry = (int) $expiry;
        if ($expiry < time() || $expiry > time() + (86400 * 31)) {
            self::clearRememberCookie();
            return false;
        }

        $signedData = implode('|', [$version, $userId, (string) $expiry, $passwordFingerprint]);
        $expectedSignature = hash_hmac('sha256', $signedData, ENCRYPTION_KEY);
        if (!hash_equals($expectedSignature, $providedSignature)) {
            self::clearRememberCookie();
            return false;
        }

        $user = $this->db->fetch(
            "SELECT user_id, username, password, role, custom_permissions FROM users WHERE user_id = ?",
            [(int) $userId]
        );
        $actualFingerprint = $user ? substr(hash('sha256', $user['password']), 0, 24) : '';
        if (!$user || !hash_equals($actualFingerprint, $passwordFingerprint)) {
            self::clearRememberCookie();
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['userId'] = (int) $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        if ($user['custom_permissions'] === null || $user['custom_permissions'] === '') {
            $_SESSION['custom_permissions'] = ($user['role'] === 'staff')
                ? ['transaction', 'returns', 'orders', 'customer_orders', 'products', 'stock_movement', 'purchase_order']
                : null;
        } else {
            $_SESSION['custom_permissions'] = json_decode($user['custom_permissions'], true);
            if (!is_array($_SESSION['custom_permissions'])) {
                $_SESSION['custom_permissions'] = [];
            }
        }

        return true;
    }

    public function login($username, $password, $remember = false) {
        // Rate limit: 10 attempts per 5 minutes per IP. Uses flock() so
        // concurrent requests from the same IP (e.g. a scripted brute-force
        // sending parallel requests) can't race past the read-modify-write
        // and undercount — without a lock, two simultaneous requests could
        // both read count=9, both increment to 10, and both pass the >10
        // check even though 11 attempts just happened.
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rateLimitDir = __DIR__ . '/logs';
        if (!is_dir($rateLimitDir)) {
            @mkdir($rateLimitDir, 0755, true);
        }
        $rateLimitFile = $rateLimitDir . '/ratelimit_' . md5($ip) . '.json';
        $ipAttemptCount = 0;

        $fh = @fopen($rateLimitFile, 'c+');
        if ($fh !== false) {
            flock($fh, LOCK_EX);
            $raw = stream_get_contents($fh);
            $attempts = $raw ? (@json_decode($raw, true) ?: null) : null;
            if (!$attempts || time() - $attempts['start'] > 300) {
                $attempts = ['count' => 0, 'start' => time()];
            }
            $attempts['count']++;
            $ipAttemptCount = $attempts['count'];
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode($attempts));
            fflush($fh);
            flock($fh, LOCK_UN);
            fclose($fh);
        }

        if ($ipAttemptCount > 10) {
            return [
                'success' => false,
                'message' => 'Too many login attempts from your network. Please wait 5 minutes.'
            ];
        }

        $username = SimpleSecurity::sanitize($username);

        // Lookup by username or email
        $user = $this->db->fetch(
            "SELECT user_id, username, email, password, role, failed_login_attempts, locked_until, custom_permissions FROM users WHERE (username = ? OR email = ?) LIMIT 1",
            [$username, $username]
        );

        // Check if user exists
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Account not found. Please check your username.'
            ];
        }

        // Per-account lockout still active? Return the remaining wait time
        // without touching the password hash at all — this both avoids the
        // bcrypt CPU cost during a lockout window and keeps the response
        // identical regardless of whether the submitted password happens to
        // be correct, so a locked-out legitimate user and an attacker get
        // the same "locked" response either way.
        if (!empty($user['locked_until'])) {
            $secondsRemaining = strtotime($user['locked_until']) - time();
            if ($secondsRemaining > 0) {
                return [
                    'success' => false,
                    'message' => 'Too many failed attempts. Please wait ' . $secondsRemaining . ' seconds.',
                    'account_locked_seconds' => $secondsRemaining
                ];
            }
        }

        // User exists, check password
        if (!SimpleSecurity::verifyPassword($password, $user['password'])) {
            $newAttempts = intval($user['failed_login_attempts']) + 1;

            if ($newAttempts >= self::MAX_FAILED_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_SECONDS);
                $this->db->execute(
                    "UPDATE users SET failed_login_attempts = 0, locked_until = ? WHERE user_id = ?",
                    [$lockedUntil, $user['user_id']]
                );
                return [
                    'success' => false,
                    'message' => 'Too many failed attempts. Please wait ' . self::LOCKOUT_SECONDS . ' seconds.',
                    'account_locked_seconds' => self::LOCKOUT_SECONDS
                ];
            }

            $this->db->execute(
                "UPDATE users SET failed_login_attempts = ? WHERE user_id = ?",
                [$newAttempts, $user['user_id']]
            );

            return [
                'success' => false,
                'message' => 'Wrong password. Please try again.',
                'attempts_remaining' => self::MAX_FAILED_ATTEMPTS - $newAttempts
            ];
        }

        // Login successful
        // Reset rate limit
        @unlink($rateLimitFile);
        // Reset per-account lockout state
        $this->db->execute(
            "UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE user_id = ?",
            [$user['user_id']]
        );
        // Auto-upgrade MD5 passwords to bcrypt
        $effectivePasswordHash = $user['password'];
        if (SimpleSecurity::needsRehash($user['password'])) {
            $newHash = SimpleSecurity::hashPassword($password);
            $this->db->execute(
                "UPDATE users SET password = ? WHERE user_id = ?",
                [$newHash, $user['user_id']]
            );
            $effectivePasswordHash = $newHash;
        }

        $_SESSION['userId'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];

        // Regenerate session ID to prevent fixation
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        // Store the real role from the users table
        $_SESSION['user_role'] = $user['role'];

        if ($user['custom_permissions'] === null || $user['custom_permissions'] === '') {
            $_SESSION['custom_permissions'] = ($user['role'] === 'staff')
                ? ['transaction', 'returns', 'orders', 'customer_orders', 'products', 'stock_movement', 'purchase_order']
                : null;
        } else {
            $_SESSION['custom_permissions'] = json_decode($user['custom_permissions'], true);
            if (!is_array($_SESSION['custom_permissions'])) {
                $_SESSION['custom_permissions'] = [];
            }
        }

        // Handle remember me — use HMAC-signed token. The signing secret is
        // the app's own ENCRYPTION_KEY (config/app_simple.php, gitignored,
        // per-install random value) rather than a literal string that used
        // to be hardcoded directly in source and would end up in git history.
        if ($remember) {
            $expiry = time() + (86400 * 30); // 30 days
            $passwordFingerprint = substr(hash('sha256', $effectivePasswordHash), 0, 24);
            $tokenData = 'v2|' . $user['user_id'] . '|' . $expiry . '|' . $passwordFingerprint;
            $signature = hash_hmac('sha256', $tokenData, ENCRYPTION_KEY);
            $token = base64_encode($tokenData . '|' . $signature);
            setcookie('user_session', $token, self::rememberCookieOptions($expiry));
        }

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ];
    }
    
    public function logout() {
        // Clear remember-me cookie
        if (isset($_COOKIE['user_session'])) {
            self::clearRememberCookie();
        }
        // Clear all session data
        $_SESSION = [];
        session_destroy();
    }
}
}

// Consume a valid remember-me cookie before entry pages check authentication.
if (!SimpleSecurity::isAuthenticated() && !empty($_COOKIE['user_session'])) {
    (new SimpleAuth())->restoreRememberedSession();
}

// Enforce 15-minute inactivity session expiration
SimpleSecurity::checkInactivity();

// Check auto-backup schedule if authenticated
if (SimpleSecurity::isAuthenticated()) {
    require_once __DIR__ . '/php_action/backup_service.php';
    BackupService::checkAutoBackup();
}

// Simple error handling
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}

// Initialize log directory
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Set error log
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . '/php_errors.log');

?>
