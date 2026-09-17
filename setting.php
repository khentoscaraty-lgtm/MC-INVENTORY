<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('settings');
require_once 'includes/header_sidebar.php';
?>

<?php
$user_id = intval($_SESSION['userId'] ?? 0);
$stmt = $connect->prepare("SELECT user_id, username, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

require_once __DIR__ . '/php_action/unit_helper.php';
require_once __DIR__ . '/php_action/sales_core.php';
$sackSizeKg = getSackSizeKg($connect);

// Sales & Receipt settings (see php_action/sales_core.php's
// salesGetVatPercent()/salesGetDiscountRules()/salesGetBusinessInfo() for the
// authoritative defaults/keys — mirrored here so the settings page can show
// current values).
$salesDefaults = [
    'vat_percent' => '12',
    'discount_senior_percent' => '20',
    'discount_pwd_percent' => '10',
    'business_name' => 'Agrivet Inventory Supply',
    'business_address' => '',
    'business_contact' => '',
    'default_reorder_level' => '10',
];
$salesSettings = $salesDefaults;
$salesResult = $connect->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('" . implode("','", array_keys($salesDefaults)) . "')");
if ($salesResult) {
    while ($row = $salesResult->fetch_assoc()) {
        $salesSettings[$row['setting_key']] = $row['setting_value'];
    }
}

// Inventory Core rules (see php_action/inventory_service.php's
// _loadInventoryRules() for the authoritative defaults/keys — mirrored here
// so the settings page can show current values without depending on that
// function's internal/private naming convention).
$invDefaults = [
    'inventory_deduction_strategy' => 'fefo',
    'allow_negative_inventory' => '0',
    'auto_expire_batches' => '1',
    'auto_sync_product_quantity' => '1',
    'low_stock_threshold' => '10',
    'expiry_warning_days' => '30',
    'inventory_precision' => '2',
    'future_reservation_enabled' => '0',
];
$invRules = $invDefaults;
$invResult = $connect->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('" . implode("','", array_keys($invDefaults)) . "')");
if ($invResult) {
    while ($row = $invResult->fetch_assoc()) {
        $invRules[$row['setting_key']] = $row['setting_value'];
    }
}
?>

<style>
    .page-header {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;
    }
    .page-header h2 {
        font-size: 1.15rem; font-weight: 700; color: var(--text); margin: 0;
        display: flex; align-items: center; gap: 8px;
    }
    .page-header h2 i { color: var(--primary); font-size: .95rem; }
    .settings-section {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .settings-section-title {
        font-size: .95rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 1.25rem;
        padding-bottom: .75rem;
        border-bottom: 2px solid var(--border-light);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .settings-section-title i { color: var(--primary); }
    .nav-tabs { border-bottom: 2px solid var(--border); margin-bottom: 1.5rem; }
    .nav-tabs .nav-link {
        color: var(--text-muted); font-weight: 600; font-size: 0.85rem;
        border: none; padding: 0.65rem 1.2rem; border-radius: 8px 8px 0 0;
        transition: all 0.2s;
    }
    .nav-tabs .nav-link:hover { color: var(--text); background: var(--border-lightest); }
    .nav-tabs .nav-link.active {
        color: var(--primary); background: var(--card-bg);
        border-bottom: 2px solid var(--primary); margin-bottom: -2px;
    }
    .tab-pane { animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: none; } }
    .theme-preview {
        width: 80px; height: 60px; border-radius: 10px; border: 2px solid var(--border);
        cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;
    }
    .theme-preview:hover, .theme-preview.active { border-color: var(--primary); transform: scale(1.05); }
    .theme-preview.light { background: #ffffff; color: #333; }
    .theme-preview.dark { background: #1a1a2e; color: #eee; }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent p-0 mb-2">
        <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
        <li class="breadcrumb-item active">Settings</li>
    </ol>
</nav>

<div class="page-header">
    <h2><i class="fas fa-cog"></i> Settings</h2>
</div>

<!-- Settings Tabs -->
<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#accountTab"><i class="fas fa-user me-1"></i> Account</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#generalTab"><i class="fas fa-sliders me-1"></i> General</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#emailTab"><i class="fas fa-envelope me-1"></i> Email &amp; Notifications</a></li>
    <?php if ($currentUser == 1): ?>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#aiTab"><i class="fas fa-bolt me-1" style="color:#ea580c;"></i> Groq AI</a></li>
    <?php endif; ?>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#voiceTab"><i class="fas fa-volume-up me-1"></i> Voice</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#themeTab"><i class="fas fa-palette me-1"></i> Theme</a></li>
</ul>

<div class="tab-content">

    <!-- ═══ ACCOUNT TAB ═══ -->
    <div class="tab-pane fade show active" id="accountTab">
        <div class="row">
            <div class="col-lg-6">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-user"></i> Change Username
                    </div>
                    <form action="php_action/changeUsername.php" method="post" id="changeUsernameForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        <div class="changeUsenrameMessages"></div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" value="<?php echo htmlspecialchars($result['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <input type="hidden" name="user_id" id="user_id" value="<?php echo intval($result['user_id'] ?? 0); ?>">
                        <button type="submit" class="btn btn-success" data-loading-text="Loading..." id="changeUsernameBtn">
                            <i class="fas fa-check me-1"></i> Save Username
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-lock"></i> Change Password
                    </div>
                    <form action="php_action/changePassword.php" method="post" id="changePasswordForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        <div class="changePasswordMessages"></div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter current password">
                        </div>
                        <div class="mb-3">
                            <label for="npassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="npassword" name="npassword" placeholder="Enter new password">
                        </div>
                        <div class="mb-3">
                            <label for="cpassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="cpassword" name="cpassword" placeholder="Confirm new password">
                        </div>
                        <input type="hidden" name="user_id" id="user_id" value="<?php echo intval($result['user_id'] ?? 0); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ GENERAL TAB ═══ -->
    <div class="tab-pane fade" id="generalTab">
        <div class="row">
            <div class="col-lg-6">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-store"></i> System Information
                    </div>
                    <div class="mb-3">
                        <label class="form-label">System Name</label>
                        <input type="text" class="form-control" value="Agrivet Inventory Supply" readonly style="background:var(--border-lightest)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" class="form-control" value="₱ (Philippine Peso)" readonly style="background:var(--border-lightest)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default Low Stock Threshold</label>
                        <input type="number" id="defaultReorderLevel" class="form-control" value="3" min="1" max="100">
                        <small class="text-muted">Products at or below this quantity will be flagged as low stock</small>
                    </div>
                    <button class="btn btn-success" onclick="saveGeneralSettings()"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-info-circle"></i> System Status
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span style="font-size:0.85rem;color:var(--text-muted)">PHP Version</span>
                        <span style="font-size:0.85rem;font-weight:600;color:var(--text)"><?php echo phpversion(); ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span style="font-size:0.85rem;color:var(--text-muted)">MySQL Version</span>
                        <span style="font-size:0.85rem;font-weight:600;color:var(--text)"><?php echo $connect->server_info; ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span style="font-size:0.85rem;color:var(--text-muted)">Database</span>
                        <span style="font-size:0.85rem;font-weight:600;color:var(--text)">sinventoryphp</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span style="font-size:0.85rem;color:var(--text-muted)">Server Time</span>
                        <span style="font-size:0.85rem;font-weight:600;color:var(--text)"><?php echo date('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-weight-hanging"></i> Inventory Settings
                    </div>
                    <div class="mb-3">
                        <label for="sackSizeKg" class="form-label">Sack Size (kg)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="sackSizeKg" value="<?php echo htmlspecialchars($sackSizeKg ?? '50'); ?>">
                        <small class="text-muted">Used to convert whole/half/quarter sack sales into kilos.</small>
                    </div>
                    <button class="btn btn-success" onclick="saveAppSettings()"><i class="fas fa-save me-1"></i> Save</button>
                    <div id="appSettingsMessages" class="mt-2"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-warehouse"></i> Inventory Rules
                    </div>

                    <div class="mb-3">
                        <label for="invDeductionStrategy" class="form-label">Batch Deduction Strategy</label>
                        <?php $invStrategy = strtolower($invRules['inventory_deduction_strategy'] ?? 'fefo'); ?>
                        <select id="invDeductionStrategy" class="form-select">
                            <option value="fefo" <?php echo $invStrategy === 'fefo' ? 'selected' : ''; ?>>FEFO (First-Expiry-First-Out)</option>
                            <option value="fifo" <?php echo $invStrategy === 'fifo' ? 'selected' : ''; ?>>FIFO (First-In-First-Out)</option>
                            <option value="lifo" <?php echo $invStrategy === 'lifo' ? 'selected' : ''; ?>>LIFO (Last-In-First-Out)</option>
                        </select>
                        <small class="text-muted">Which batch gets sold first when multiple batches of the same product exist.</small>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="invAllowNegative" <?php echo ($invRules['allow_negative_inventory'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="invAllowNegative">Allow sales when stock would go negative</label>
                        <div><small class="text-warning"><i class="fas fa-triangle-exclamation me-1"></i>Risky: enabling this can let stock counts go below zero.</small></div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="invAutoExpire" <?php echo ($invRules['auto_expire_batches'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="invAutoExpire">Automatically mark batches as expired past their expiry date</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="invAutoSync" <?php echo ($invRules['auto_sync_product_quantity'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="invAutoSync">Keep product quantity totals automatically synced with batch records</label>
                        <div><small class="text-muted">Advanced/rarely-touched. Should almost always stay on.</small></div>
                    </div>

                    <div class="mb-3">
                        <label for="invLowStockThreshold" class="form-label">Default Low Stock Threshold</label>
                        <input type="number" step="1" min="0" class="form-control" id="invLowStockThreshold" value="<?php echo htmlspecialchars($invRules['low_stock_threshold'] ?? '10', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="text-muted">Fallback used when a specific product doesn't have its own reorder level set.</small>
                    </div>

                    <div class="mb-3">
                        <label for="invExpiryWarningDays" class="form-label">Expiry Warning Window (days)</label>
                        <input type="number" step="1" min="0" class="form-control" id="invExpiryWarningDays" value="<?php echo htmlspecialchars($invRules['expiry_warning_days'] ?? '30', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="text-muted">How many days ahead of expiry a product shows up in expiry alerts.</small>
                    </div>

                    <div class="mb-3">
                        <label for="invPrecision" class="form-label">Decimal Precision for Stock Quantities</label>
                        <input type="number" step="1" min="0" max="6" class="form-control" id="invPrecision" value="<?php echo htmlspecialchars($invRules['inventory_precision'] ?? '2', ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="text-muted"><i class="fas fa-flask me-1"></i>Advanced — most stores should never change this. Must be a whole number from 0 to 6.</small>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="invFutureReservation" <?php echo ($invRules['future_reservation_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="invFutureReservation">Enable stock reservations for pending orders</label>
                        <div><small class="text-muted"><i class="fas fa-info-circle me-1"></i>Forward-looking feature: this is not yet fully wired into a reservation workflow.</small></div>
                    </div>

                    <button class="btn btn-success" onclick="saveInventoryRules()"><i class="fas fa-save me-1"></i> Save</button>
                    <div id="inventoryRulesMessages" class="mt-2"></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-receipt"></i> Sales &amp; Receipt
                    </div>

                    <div class="mb-3">
                        <label for="salesVatPercent" class="form-label">VAT %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" id="salesVatPercent" value="<?php echo htmlspecialchars($salesSettings['vat_percent'], ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="text-muted">Value-added tax applied to POS and wholesale sales.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="salesSeniorDiscount" class="form-label">Senior Citizen Discount %</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="salesSeniorDiscount" value="<?php echo htmlspecialchars($salesSettings['discount_senior_percent'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="salesPwdDiscount" class="form-label">PWD Discount %</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="salesPwdDiscount" value="<?php echo htmlspecialchars($salesSettings['discount_pwd_percent'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="salesReorderLevel" class="form-label">Default Reorder Level</label>
                        <input type="number" step="1" min="0" class="form-control" id="salesReorderLevel" value="<?php echo htmlspecialchars($salesSettings['default_reorder_level'], ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="text-muted">Fallback low-stock reorder threshold for products without their own level set.</small>
                    </div>

                    <button class="btn btn-success" onclick="saveSalesSettings()"><i class="fas fa-save me-1"></i> Save</button>
                    <div id="salesSettingsMessages" class="mt-2"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-shop"></i> Business Info (Receipts)
                    </div>

                    <div class="mb-3">
                        <label for="salesBusinessName" class="form-label">Business Name</label>
                        <input type="text" maxlength="255" class="form-control" id="salesBusinessName" value="<?php echo htmlspecialchars($salesSettings['business_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="salesBusinessAddress" class="form-label">Business Address</label>
                        <input type="text" maxlength="255" class="form-control" id="salesBusinessAddress" value="<?php echo htmlspecialchars($salesSettings['business_address'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="salesBusinessContact" class="form-label">Business Contact</label>
                        <input type="text" maxlength="255" class="form-control" id="salesBusinessContact" value="<?php echo htmlspecialchars($salesSettings['business_contact'], ENT_QUOTES, 'UTF-8'); ?>">
                        <small class="text-muted">Phone number or contact line shown on printed receipts.</small>
                    </div>

                    <button class="btn btn-success" onclick="saveSalesSettings()"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ EMAIL & NOTIFICATIONS TAB ═══ -->
    <div class="tab-pane fade" id="emailTab">
        <div class="row">
            <div class="col-lg-8">
                <!-- SMTP Configuration Card -->
                <div class="settings-section">
                    <div class="settings-section-title d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-envelope-shield"></i> PHPMailer SMTP Configuration</div>
                        <span class="badge" style="background:#e0e7ff; color:#4338ca; font-weight:600; font-size:0.75rem;"><i class="fas fa-shield-halved me-1"></i> OTP Protected</span>
                    </div>

                    <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1.25rem;">
                        Configure your SMTP email server for all automated notifications. Changing sensitive credentials requires OTP verification sent to your verified admin email.
                    </p>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="mailHost" class="form-label" style="font-weight:600;font-size:0.85rem">SMTP Server (Host)</label>
                            <input type="text" id="mailHost" class="form-control" placeholder="smtp.gmail.com" value="smtp.gmail.com">
                        </div>
                        <div class="col-md-3">
                            <label for="mailPort" class="form-label" style="font-weight:600;font-size:0.85rem">Port</label>
                            <input type="number" id="mailPort" class="form-control" placeholder="587" value="587">
                        </div>
                        <div class="col-md-3">
                            <label for="mailEncryption" class="form-label" style="font-weight:600;font-size:0.85rem">Encryption</label>
                            <select id="mailEncryption" class="form-select">
                                <option value="tls" selected>TLS (Port 587)</option>
                                <option value="ssl">SSL (Port 465)</option>
                                <option value="none">None (Port 25)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="mailUsername" class="form-label" style="font-weight:600;font-size:0.85rem">SMTP Username / Gmail Address</label>
                            <input type="email" id="mailUsername" class="form-control" placeholder="your_email@gmail.com">
                        </div>
                        <div class="col-md-6">
                            <label for="mailPassword" class="form-label" style="font-weight:600;font-size:0.85rem">Gmail App Password</label>
                            <div class="input-group">
                                <input type="password" id="mailPassword" class="form-control" placeholder="•••••••••••••••• (Leave empty to keep current)">
                                <button class="btn btn-outline-primary" type="button" onclick="toggleMailPasswordVisibility()" id="mailPassEyeBtn" title="Show password"><i class="fas fa-eye" id="mailPassEyeIcon"></i></button>
                            </div>
                            <div id="mailPasswordStatus" class="mt-1" style="font-size:0.78rem"></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="mailFromEmail" class="form-label" style="font-weight:600;font-size:0.85rem">Sender Email (From)</label>
                            <input type="email" id="mailFromEmail" class="form-control" placeholder="admin@mcagrivet.com">
                        </div>
                        <div class="col-md-6">
                            <label for="mailFromName" class="form-label" style="font-weight:600;font-size:0.85rem">Sender Display Name</label>
                            <input type="text" id="mailFromName" class="form-control" placeholder="Agrivet Inventory System" value="Agrivet Inventory System">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="mailAdminEmail" class="form-label" style="font-weight:600;font-size:0.85rem">Admin Notification Email Address</label>
                        <input type="email" id="mailAdminEmail" class="form-control" placeholder="admin@mcagrivet.com">
                        <small class="text-muted">Target email address where administrative and inventory alerts will be delivered.</small>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" onclick="initiateSaveEmailSettings()" id="saveEmailBtn">
                            <i class="fas fa-shield-check me-1"></i> Save Configuration (Requires OTP)
                        </button>
                        <button class="btn btn-outline-info" onclick="openTestEmailModal()">
                            <i class="fas fa-paper-plane me-1"></i> Send Test Email
                        </button>
                    </div>
                    <div id="emailSettingsMessages" class="mt-3"></div>
                </div>

                <!-- Event Notification Toggles Card -->
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-bell"></i> Real-time Email Alert Subscriptions
                    </div>
                    <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1rem;">
                        Select which store transactions and inventory events should dispatch real-time email notifications.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded-3 bg-light-subtle">
                                <input class="form-check-input" type="checkbox" id="notifInventory" checked>
                                <label class="form-check-label fw-bold" for="notifInventory">
                                    <i class="fas fa-boxes-stacked text-warning me-1"></i> Inventory Alerts
                                </label>
                                <div class="text-muted" style="font-size:0.78rem;">Low stock, critical stock, and out-of-stock notifications.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded-3 bg-light-subtle">
                                <input class="form-check-input" type="checkbox" id="notifPos" checked>
                                <label class="form-check-label fw-bold" for="notifPos">
                                    <i class="fas fa-cash-register text-success me-1"></i> POS Sales &amp; Receipts
                                </label>
                                <div class="text-muted" style="font-size:0.78rem;">Official receipts and customer transaction summaries.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded-3 bg-light-subtle">
                                <input class="form-check-input" type="checkbox" id="notifPo" checked>
                                <label class="form-check-label fw-bold" for="notifPo">
                                    <i class="fas fa-file-invoice text-primary me-1"></i> Purchase Orders
                                </label>
                                <div class="text-muted" style="font-size:0.78rem;">PO creation, supplier receiving, and cancellations.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded-3 bg-light-subtle">
                                <input class="form-check-input" type="checkbox" id="notifStockMovement" checked>
                                <label class="form-check-label fw-bold" for="notifStockMovement">
                                    <i class="fas fa-truck-ramp-box text-info me-1"></i> Stock Movements
                                </label>
                                <div class="text-muted" style="font-size:0.78rem;">Stock-in deliveries, stock-out issues, and manual adjustments.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded-3 bg-light-subtle">
                                <input class="form-check-input" type="checkbox" id="notifExpiry" checked>
                                <label class="form-check-label fw-bold" for="notifExpiry">
                                    <i class="fas fa-clock text-danger me-1"></i> Expiry &amp; Write-Offs
                                </label>
                                <div class="text-muted" style="font-size:0.78rem;">Feed batch expiration warnings and write-off records.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded-3 bg-light-subtle">
                                <input class="form-check-input" type="checkbox" id="notifReturns" checked>
                                <label class="form-check-label fw-bold" for="notifReturns">
                                    <i class="fas fa-arrow-rotate-left text-danger me-1"></i> Returns &amp; Refunds
                                </label>
                                <div class="text-muted" style="font-size:0.78rem;">Sales returns, refund receipts, and restock logs.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Side Info Card -->
            <div class="col-lg-4">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-shield-halved text-primary"></i> Security Architecture
                    </div>
                    <div class="mb-3">
                        <h6 style="font-size:0.85rem;font-weight:700;color:var(--text)"><i class="fas fa-key text-warning me-1"></i> Gmail App Passwords</h6>
                        <p style="font-size:0.80rem;color:var(--text-muted);line-height:1.5">
                            Google accounts with 2-Step Verification require a dedicated 16-character App Password rather than your standard account password.
                        </p>
                    </div>
                    <div class="mb-3">
                        <h6 style="font-size:0.85rem;font-weight:700;color:var(--text)"><i class="fas fa-lock text-success me-1"></i> Zero-Exposure Storage</h6>
                        <p style="font-size:0.80rem;color:var(--text-muted);line-height:1.5">
                            App Passwords are loaded via strict environment files (<code>.env</code>) and are never exposed in browser pages, raw database tables, or log files.
                        </p>
                    </div>
                    <div class="mb-3">
                        <h6 style="font-size:0.85rem;font-weight:700;color:var(--text)"><i class="fas fa-envelope-circle-check text-info me-1"></i> OTP Confirmation</h6>
                        <p style="font-size:0.80rem;color:var(--text-muted);line-height:1.5">
                            Every modification to SMTP or notification parameters sends an immediate 6-digit code to the verified administrator email before activating.
                        </p>
                    </div>
                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size:0.78rem;border-radius:10px">
                        <i class="fas fa-info-circle me-1"></i> <strong>Engine:</strong> PHPMailer 6.9.1 with TLS / SSL SMTP Transport.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($currentUser == 1): ?>
    <!-- ═══ AI SETTINGS TAB (GROQ CONFIGURATION) ═══ -->
    <div class="tab-pane fade" id="aiTab">
        <div class="row">
            <div class="col-lg-8">
                <div class="settings-section">
                    <div class="settings-section-title d-flex align-items-center gap-2">
                        <i class="fas fa-bolt" style="color: #ea580c;"></i> Groq API Configuration
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600;">Groq API Key</label>
                        <div class="input-group">
                            <input type="password" id="aiApiKey" class="form-control" placeholder="gsk_...">
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKeyVisibility()" id="apiKeyEyeBtn" aria-label="Show API key" aria-pressed="false" title="Show API key" style="border-color: #ea580c; color: #ea580c; background: transparent;">
                                <i class="fas fa-eye" id="apiKeyEyeIcon"></i>
                            </button>
                        </div>
                        <div class="mt-1" style="font-size:0.83rem; color:var(--text-muted);">
                            Create a free key at <a href="https://console.groq.com" target="_blank" style="color:#ea580c; text-decoration: underline; font-weight: 500;">Groq Console</a>. Leave blank to keep the saved key.
                        </div>
                        <div id="apiKeyStatus" class="mt-1" style="font-size:0.83rem"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600;">Model</label>
                        <select id="aiModel" class="form-select">
                            <option value="openai/gpt-oss-120b">GPT-OSS 120B — Recommended</option>
                            <option value="llama-3.3-70b-versatile">Llama 3.3 70B Versatile (Powerful & Fast)</option>
                            <option value="llama-3.1-8b-instant">Llama 3.1 8B Instant (Ultra Fast)</option>
                            <option value="mixtral-8x7b-32768">Mixtral 8x7B (32k Context)</option>
                            <option value="gemma2-9b-it">Gemma 2 9B (Google)</option>
                        </select>
                    </div>
                    <div class="alert py-2 px-3 my-3" style="background:#fff7ed; border-left:4px solid #ea580c; border-radius:10px; color:#9a3412; font-size:0.85rem; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-info-circle" style="color:#ea580c; font-size:1.05rem; flex-shrink:0;"></i>
                        <span>Groq free-tier limits apply. MAIA switches to offline mode instead of using a paid provider.</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn" style="background: #ea580c; border-color: #ea580c; color: white; font-weight: 600; padding: 7px 22px; border-radius: 8px;" onclick="saveAISettings()">
                            <i class="fas fa-save me-1"></i> Save
                        </button>
                        <button class="btn btn-outline-secondary" style="font-weight: 600; padding: 7px 18px; border-radius: 8px;" onclick="testAPIConnection()">
                            <i class="fas fa-bolt me-1" style="color:#ea580c;"></i> Test Connection
                        </button>
                    </div>
                    <div id="aiSettingsMessages" class="mt-2"></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-lightbulb"></i> About MAIA
                    </div>
                    <p style="font-size:0.85rem;color:var(--text-muted);line-height:1.6">
                        <strong style="color:var(--text)">MAIA</strong> (Agrivet Intelligent Assistant) is your AI-powered inventory assistant.
                    </p>
                    <p style="font-size:0.82rem;color:var(--text-muted);line-height:1.6">
                        She can help you with:
                    </p>
                    <ul style="font-size:0.82rem;color:var(--text-muted);padding-left:1.2rem;line-height:1.8">
                        <li>Inventory analysis &amp; recommendations</li>
                        <li>Sales trend insights</li>
                        <li>Stock forecasting</li>
                        <li>Agricultural calculations &amp; unit conversion</li>
                    </ul>
                    <div class="alert alert-warning py-2 px-3" style="font-size:0.78rem;border-radius:10px">
                        <i class="fas fa-shield-halved me-1"></i> Your Groq API key is encrypted before storage.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ VOICE SETTINGS TAB ═══ -->
    <div class="tab-pane fade" id="voiceTab">
        <div class="row">
            <div class="col-lg-8">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-volume-up"></i> Voice Notifications
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="voiceEnabled" checked>
                        <label class="form-check-label" for="voiceEnabled">Enable Voice Notifications</label>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Voice Language</label>
                            <select id="voiceLang" class="form-select">
                                <option value="fil-PH">Filipino</option>
                                <option value="en-US">English (US)</option>
                                <option value="en-GB">English (UK)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Speed</label>
                            <select id="voiceSpeed" class="form-select">
                                <option value="slow">Slow</option>
                                <option value="normal" selected>Normal</option>
                                <option value="fast">Fast</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Volume: <span id="volumeValue">80</span>%</label>
                            <input type="range" id="voiceVolume" class="form-range" min="0" max="100" value="80" oninput="document.getElementById('volumeValue').textContent=this.value">
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3" style="font-weight:700;color:var(--text);font-size:0.9rem"><i class="fas fa-bell me-1" style="color:var(--primary)"></i> Alert Types</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" style="font-size:0.85rem">
                            <thead>
                                <tr style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted)">
                                    <th scope="col">Alert</th>
                                    <th scope="col" class="text-center">Voice + Sound</th>
                                    <th scope="col" class="text-center">Sound Only</th>
                                    <th scope="col" class="text-center">Silent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Out of Stock</td><td class="text-center"><input type="radio" name="alert_out_of_stock" value="voice_sound" checked></td><td class="text-center"><input type="radio" name="alert_out_of_stock" value="sound_only"></td><td class="text-center"><input type="radio" name="alert_out_of_stock" value="silent"></td></tr>
                                <tr><td>Critical Stock</td><td class="text-center"><input type="radio" name="alert_critical_stock" value="voice_sound" checked></td><td class="text-center"><input type="radio" name="alert_critical_stock" value="sound_only"></td><td class="text-center"><input type="radio" name="alert_critical_stock" value="silent"></td></tr>
                                <tr><td>Low Stock</td><td class="text-center"><input type="radio" name="alert_low_stock" value="voice_sound"></td><td class="text-center"><input type="radio" name="alert_low_stock" value="sound_only" checked></td><td class="text-center"><input type="radio" name="alert_low_stock" value="silent"></td></tr>
                                <tr><td>New Stock Added</td><td class="text-center"><input type="radio" name="alert_new_stock" value="voice_sound"></td><td class="text-center"><input type="radio" name="alert_new_stock" value="sound_only" checked></td><td class="text-center"><input type="radio" name="alert_new_stock" value="silent"></td></tr>
                                <tr><td>Expiring Soon</td><td class="text-center"><input type="radio" name="alert_expiring" value="voice_sound" checked></td><td class="text-center"><input type="radio" name="alert_expiring" value="sound_only"></td><td class="text-center"><input type="radio" name="alert_expiring" value="silent"></td></tr>
                                <tr><td>Expired</td><td class="text-center"><input type="radio" name="alert_expired" value="voice_sound" checked></td><td class="text-center"><input type="radio" name="alert_expired" value="sound_only"></td><td class="text-center"><input type="radio" name="alert_expired" value="silent"></td></tr>
                                <tr><td>PO Overdue</td><td class="text-center"><input type="radio" name="alert_po_overdue" value="voice_sound"></td><td class="text-center"><input type="radio" name="alert_po_overdue" value="sound_only" checked></td><td class="text-center"><input type="radio" name="alert_po_overdue" value="silent"></td></tr>
                                <tr><td>New Order</td><td class="text-center"><input type="radio" name="alert_new_order" value="voice_sound"></td><td class="text-center"><input type="radio" name="alert_new_order" value="sound_only" checked></td><td class="text-center"><input type="radio" name="alert_new_order" value="silent"></td></tr>
                                <tr><td>Daily Briefing</td><td class="text-center"><input type="radio" name="alert_daily_briefing" value="voice_sound" checked></td><td class="text-center"><input type="radio" name="alert_daily_briefing" value="sound_only"></td><td class="text-center"><input type="radio" name="alert_daily_briefing" value="silent"></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row mb-3 mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Quiet Hours Start</label>
                            <input type="time" id="quietStart" class="form-control" value="22:00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quiet Hours End</label>
                            <input type="time" id="quietEnd" class="form-control" value="06:00">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-success" onclick="saveVoiceSettings()"><i class="fas fa-save me-1"></i> Save</button>
                        <button class="btn btn-info text-white" onclick="testVoice()"><i class="fas fa-volume-up me-1"></i> Test Voice</button>
                    </div>
                    <div id="voiceSettingsMessages" class="mt-2"></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-info-circle"></i> About Voice
                    </div>
                    <p style="font-size:0.85rem;color:var(--text-muted);line-height:1.6">
                        Voice notifications use your browser's built-in speech synthesis to read alerts aloud.
                    </p>
                    <p style="font-size:0.82rem;color:var(--text-muted);line-height:1.6">
                        During <strong style="color:var(--text)">Quiet Hours</strong>, all voice alerts are automatically muted.
                    </p>
                    <div class="alert alert-info py-2 px-3" style="font-size:0.78rem;border-radius:10px">
                        <i class="fas fa-info-circle me-1"></i> Voice works best in Chrome and Edge browsers.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ THEME TAB ═══ -->
    <div class="tab-pane fade" id="themeTab">
        <div class="row">
            <div class="col-lg-6">
                <div class="settings-section">
                    <div class="settings-section-title">
                        <i class="fas fa-palette"></i> Appearance
                    </div>
                    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1.25rem">Choose your preferred theme. This setting is saved locally in your browser.</p>
                    <div class="d-flex gap-4 mb-3">
                        <div class="text-center" onclick="setTheme('light')" style="cursor:pointer">
                            <div class="theme-preview light mb-2" id="themePreviewLight">
                                <i class="fas fa-sun" style="font-size:1.2rem"></i>
                            </div>
                            <span style="font-size:0.82rem;font-weight:600;color:var(--text)">Light</span>
                        </div>
                        <div class="text-center" onclick="setTheme('dark')" style="cursor:pointer">
                            <div class="theme-preview dark mb-2" id="themePreviewDark">
                                <i class="fas fa-moon" style="font-size:1.2rem"></i>
                            </div>
                            <span style="font-size:0.82rem;font-weight:600;color:var(--text)">Dark</span>
                        </div>
                    </div>
                    <p style="font-size:0.78rem;color:var(--text-muted)"><i class="fas fa-info-circle me-1"></i> Current theme: <strong id="currentThemeLabel">Light</strong></p>
                </div>
            </div>
        </div>
    </div>

</div><!-- /tab-content -->

<!-- OTP Verification Modal -->
<div class="modal fade" id="emailOtpModal" tabindex="-1" aria-labelledby="emailOtpModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="emailOtpModalLabel">
                    <i class="fas fa-shield-halved text-primary me-2"></i> Security Verification
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="cancelOtpFlow()"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="text-center my-2">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width:64px; height:64px; font-size:1.8rem; background:#e0e7ff; color:#4338ca;">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Enter Verification Code</h6>
                    <p class="text-muted small mb-3">
                        A 6-digit OTP has been sent to <strong id="otpRecipientMasked" class="text-dark">admin@...</strong>
                    </p>
                </div>

                <div class="mb-3 text-center">
                    <input type="text" id="otpInput" class="form-control form-control-lg text-center fw-bold" placeholder="123456" maxlength="6" autocomplete="one-time-code" style="font-size:1.8rem; letter-spacing:8px; font-family:monospace; border-radius:10px;">
                    <div class="form-text mt-2 text-muted">
                        Code expires in <span id="otpTimer" class="fw-bold text-danger">10:00</span>
                    </div>
                </div>

                <div id="otpModalMessages"></div>
            </div>
            <div class="modal-footer border-top-0 pt-0 px-4 pb-4 d-flex justify-content-between">
                <button type="button" class="btn btn-link text-muted p-0 text-decoration-none" id="resendOtpBtn" onclick="resendEmailOtp()" disabled>
                    <i class="fas fa-rotate-right me-1"></i> Resend code (<span id="resendCountdown">60</span>s)
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" onclick="cancelOtpFlow()">Cancel</button>
                    <button type="button" class="btn btn-primary px-4" id="verifyOtpBtn" onclick="submitEmailOtpVerification()">
                        <i class="fas fa-check-circle me-1"></i> Verify &amp; Activate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="testEmailModalLabel">
                    <i class="fas fa-paper-plane text-info me-2"></i> Send Test Email
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="text-muted small mb-3">
                    Send a test email to verify PHPMailer connection, TLS encryption, and Google App Password authentication.
                </p>
                <div class="mb-3">
                    <label for="testEmailRecipient" class="form-label fw-bold small">Recipient Email Address</label>
                    <input type="email" id="testEmailRecipient" class="form-control" placeholder="admin@mcagrivet.com">
                </div>
                <div id="testEmailMessages"></div>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info text-white px-4" id="sendTestEmailBtn" onclick="executeSendTestEmail()">
                    <i class="fas fa-paper-plane me-1"></i> Send Test Message
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script src="custom/js/setting.js"></script>
<script>
// ═══ AI SETTINGS ═══
function toggleApiKeyVisibility() {
    var inp = document.getElementById('aiApiKey');
    var icon = document.getElementById('apiKeyEyeIcon');
    var btn = document.getElementById('apiKeyEyeBtn');
    if (!inp || !icon) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
        if (btn) { btn.setAttribute('aria-label', 'Hide API key'); btn.setAttribute('aria-pressed', 'true'); btn.title = 'Hide API key'; }
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
        if (btn) { btn.setAttribute('aria-label', 'Show API key'); btn.setAttribute('aria-pressed', 'false'); btn.title = 'Show API key'; }
    }
}

function loadAISettings() {
    $.get('php_action/fetchAISettings.php', function(resp) {
        if (resp.success && resp.settings) {
            var s = resp.settings;
            if (s.model) {
                if (s.model.indexOf('claude-') === 0) {
                    $('#aiModel').val('openai/gpt-oss-120b');
                } else {
                    $('#aiModel').val(s.model);
                }
            }
        }
        if (resp.has_api_key) {
            $('#apiKeyStatus').html('<span style="color:#16a34a; font-weight:500;"><i class="fas fa-check-circle me-1"></i> API key is configured</span>');
            if (resp.api_key) {
                $('#aiApiKey').val(resp.api_key);
            }
        } else {
            $('#apiKeyStatus').html('<span style="color:var(--text-muted);"><i class="fas fa-info-circle me-1"></i> No API key configured</span>');
            $('#aiApiKey').val('');
        }
    }, 'json');
}

function saveAISettings() {
    var data = {
        csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
        apiKey: $('#aiApiKey').val(),
        model: $('#aiModel').val()
    };
    $.post('php_action/saveAISettings.php', data, function(resp) {
        if (resp.success) {
            $('#aiSettingsMessages').html('<div class="alert alert-success py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-check-circle me-1"></i> ' + (resp.messages || 'Groq settings saved successfully!') + '</div>');
            loadAISettings();
        } else {
            $('#aiSettingsMessages').html('<div class="alert alert-danger py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-times-circle me-1"></i> ' + (resp.messages || 'Failed to save settings') + '</div>');
        }
        setTimeout(function() { $('#aiSettingsMessages .alert').fadeOut(); }, 4000);
    }, 'json');
}

function testAPIConnection() {
    $('#aiSettingsMessages').html('<div class="alert alert-info py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-spinner fa-spin me-1"></i> Testing Groq API connection...</div>');
    $.ajax({
        url: 'php_action/ai_chat_online.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
            message: 'Hello, this is a test. Reply with just: Connection successful!'
        }),
        dataType: 'json',
        success: function(resp) {
            if (resp.success && resp.message && resp.message.indexOf('error') === -1 && resp.message.indexOf('requires a Groq API key') === -1) {
                $('#aiSettingsMessages').html('<div class="alert alert-success py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-check-circle me-1"></i> Groq API connection successful!</div>');
            } else {
                $('#aiSettingsMessages').html('<div class="alert alert-warning py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-exclamation-triangle me-1"></i> ' + (resp.message || 'Connection test response received') + '</div>');
            }
            setTimeout(function() { $('#aiSettingsMessages .alert').fadeOut(); }, 6000);
        },
        error: function() {
            $('#aiSettingsMessages').html('<div class="alert alert-danger py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-times-circle me-1"></i> Could not reach the API endpoint.</div>');
        }
    });
}

// ═══ VOICE SETTINGS ═══
function loadVoiceSettings() {
    $.get('php_action/fetchVoiceSettings.php', function(resp) {
        if (resp.success && resp.settings) {
            var s = resp.settings;
            $('#voiceEnabled').prop('checked', s.enabled);
            $('#voiceLang').val(s.voice_lang);
            $('#voiceSpeed').val(s.speed);
            var vol = Math.round(s.volume * 100);
            $('#voiceVolume').val(vol);
            $('#volumeValue').text(vol);
            $('#quietStart').val(s.quiet_start);
            $('#quietEnd').val(s.quiet_end);
            if (s.alerts) {
                $.each(s.alerts, function(key, val) {
                    $('input[name="alert_' + key + '"][value="' + val + '"]').prop('checked', true);
                });
            }
        }
    }, 'json');
}

function saveVoiceSettings() {
    var data = {
        csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
        enabled: $('#voiceEnabled').is(':checked') ? 1 : 0,
        voice_lang: $('#voiceLang').val(),
        voice_gender: 'female',
        speed: $('#voiceSpeed').val(),
        volume: ($('#voiceVolume').val() / 100).toFixed(2),
        quiet_start: $('#quietStart').val(),
        quiet_end: $('#quietEnd').val()
    };
    // Collect alert settings
    var alertTypes = ['out_of_stock','critical_stock','low_stock','new_stock','expiring','expired','po_overdue','new_order','daily_briefing'];
    $.each(alertTypes, function(i, type) {
        data['alert_' + type] = $('input[name="alert_' + type + '"]:checked').val() || 'sound_only';
    });
    $.post('php_action/saveVoiceSettings.php', data, function(resp) {
        if (resp.success) {
            $('#voiceSettingsMessages').html('<div class="alert alert-success py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-check-circle me-1"></i> ' + resp.messages + '</div>');
            // Reload voice settings in MAIAVoice if available
            if (typeof MAIAVoice !== 'undefined' && MAIAVoice.loadSettings) MAIAVoice.loadSettings();
        } else {
            $('#voiceSettingsMessages').html('<div class="alert alert-danger py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-times-circle me-1"></i> ' + resp.messages + '</div>');
        }
        setTimeout(function() { $('#voiceSettingsMessages .alert').fadeOut(); }, 3000);
    }, 'json');
}

function testVoice() {
    if (typeof MAIAVoice !== 'undefined' && MAIAVoice.testVoice) {
        MAIAVoice.testVoice();
    } else {
        // Fallback: use browser speech synthesis
        var msg = new SpeechSynthesisUtterance('Hello! MAIA voice notifications are working correctly.');
        msg.lang = $('#voiceLang').val() || 'en-US';
        msg.volume = $('#voiceVolume').val() / 100;
        var speed = $('#voiceSpeed').val();
        msg.rate = speed === 'slow' ? 0.8 : (speed === 'fast' ? 1.3 : 1.0);
        speechSynthesis.speak(msg);
    }
}

// ═══ GENERAL SETTINGS ═══
function saveGeneralSettings() {
    // Save to localStorage for now
    var reorderLevel = $('#defaultReorderLevel').val();
    localStorage.setItem('defaultReorderLevel', reorderLevel);
    alert('General settings saved.');
}

function saveAppSettings() {
    var data = {
        csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
        sack_size_kg: $('#sackSizeKg').val()
    };
    $.post('php_action/saveAppSettings.php', data, function(resp) {
        if (resp.success) {
            $('#appSettingsMessages').html('<div class="alert alert-success py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-check-circle me-1"></i> ' + resp.messages + '</div>');
        } else {
            $('#appSettingsMessages').html('<div class="alert alert-danger py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-times-circle me-1"></i> ' + resp.messages + '</div>');
        }
        setTimeout(function() { $('#appSettingsMessages .alert').fadeOut(); }, 3000);
    }, 'json');
}

function saveInventoryRules() {
    var data = {
        csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
        inventory_deduction_strategy: $('#invDeductionStrategy').val(),
        allow_negative_inventory: $('#invAllowNegative').is(':checked') ? 1 : 0,
        auto_expire_batches: $('#invAutoExpire').is(':checked') ? 1 : 0,
        auto_sync_product_quantity: $('#invAutoSync').is(':checked') ? 1 : 0,
        low_stock_threshold: $('#invLowStockThreshold').val(),
        expiry_warning_days: $('#invExpiryWarningDays').val(),
        inventory_precision: $('#invPrecision').val(),
        future_reservation_enabled: $('#invFutureReservation').is(':checked') ? 1 : 0
    };
    $.post('php_action/saveAppSettings.php', data, function(resp) {
        if (resp.success) {
            $('#inventoryRulesMessages').html('<div class="alert alert-success py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-check-circle me-1"></i> ' + resp.messages + '</div>');
        } else {
            $('#inventoryRulesMessages').html('<div class="alert alert-danger py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-times-circle me-1"></i> ' + resp.messages + '</div>');
        }
        setTimeout(function() { $('#inventoryRulesMessages .alert').fadeOut(); }, 4000);
    }, 'json');
}

function saveSalesSettings() {
    var data = {
        csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
        vat_percent: $('#salesVatPercent').val(),
        discount_senior_percent: $('#salesSeniorDiscount').val(),
        discount_pwd_percent: $('#salesPwdDiscount').val(),
        default_reorder_level: $('#salesReorderLevel').val(),
        business_name: $('#salesBusinessName').val(),
        business_address: $('#salesBusinessAddress').val(),
        business_contact: $('#salesBusinessContact').val()
    };
    $.post('php_action/saveAppSettings.php', data, function(resp) {
        if (resp.success) {
            $('#salesSettingsMessages').html('<div class="alert alert-success py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-check-circle me-1"></i> ' + resp.messages + '</div>');
        } else {
            $('#salesSettingsMessages').html('<div class="alert alert-danger py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-times-circle me-1"></i> ' + resp.messages + '</div>');
        }
        setTimeout(function() { $('#salesSettingsMessages .alert').fadeOut(); }, 4000);
    }, 'json');
}

// ═══ EMAIL & SMTP NOTIFICATION SETTINGS ═══
var currentOtpId = null;
var otpTimerInterval = null;
var resendTimerInterval = null;

function toggleMailPasswordVisibility() {
    var inp = document.getElementById('mailPassword');
    var icon = document.getElementById('mailPassEyeIcon');
    var btn = document.getElementById('mailPassEyeBtn');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
        if (btn) btn.title = 'Hide password';
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
        if (btn) btn.title = 'Show password';
    }
}

function loadMailSettings() {
    $.get('php_action/fetchMailSettings.php', function(resp) {
        if (resp.success && resp.settings) {
            var s = resp.settings;
            if (s.host) $('#mailHost').val(s.host);
            if (s.port) $('#mailPort').val(s.port);
            if (s.encryption) $('#mailEncryption').val(s.encryption);
            if (s.username) $('#mailUsername').val(s.username);
            if (s.from_email) $('#mailFromEmail').val(s.from_email);
            if (s.from_name) $('#mailFromName').val(s.from_name);
            if (s.admin_email) $('#mailAdminEmail').val(s.admin_email);

            if (s.has_password) {
                $('#mailPasswordStatus').html('<span class="text-success"><i class="fas fa-check-circle me-1"></i>Gmail App Password is securely configured</span>');
                $('#mailPassword').attr('placeholder', '•••••••••••••••• (Configured - leave empty to keep)');
            } else {
                $('#mailPasswordStatus').html('<span class="text-warning"><i class="fas fa-triangle-exclamation me-1"></i>No password configured</span>');
            }

            if (resp.toggles) {
                $('#notifInventory').prop('checked', resp.toggles.inventory);
                $('#notifPos').prop('checked', resp.toggles.pos);
                $('#notifPo').prop('checked', resp.toggles.po);
                $('#notifStockMovement').prop('checked', resp.toggles.stock_movement);
                $('#notifExpiry').prop('checked', resp.toggles.expiry);
                $('#notifReturns').prop('checked', resp.toggles.returns);
            }

            if (resp.verified_admin_email) {
                $('#testEmailRecipient').val(resp.verified_admin_email);
            }
        }
    }, 'json');
}

function initiateSaveEmailSettings() {
    var data = {
        csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
        host: $('#mailHost').val(),
        port: $('#mailPort').val(),
        encryption: $('#mailEncryption').val(),
        username: $('#mailUsername').val(),
        password: $('#mailPassword').val(),
        from_email: $('#mailFromEmail').val(),
        from_name: $('#mailFromName').val(),
        admin_email: $('#mailAdminEmail').val(),
        notif_inventory: $('#notifInventory').is(':checked') ? 1 : 0,
        notif_pos: $('#notifPos').is(':checked') ? 1 : 0,
        notif_po: $('#notifPo').is(':checked') ? 1 : 0,
        notif_stock_movement: $('#notifStockMovement').is(':checked') ? 1 : 0,
        notif_expiry: $('#notifExpiry').is(':checked') ? 1 : 0,
        notif_returns: $('#notifReturns').is(':checked') ? 1 : 0
    };

    var btn = $('#saveEmailBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Sending OTP...');

    $('#emailSettingsMessages').html('');

    $.post('php_action/requestEmailConfigOtp.php', data, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-shield-check me-1"></i> Save Configuration (Requires OTP)');

        if (resp.success) {
            currentOtpId = resp.otp_id;
            $('#otpRecipientMasked').text(resp.recipient_masked || 'your verified email');
            $('#otpInput').val('');
            $('#otpModalMessages').html('');

            // Launch Modal
            var otpModalEl = document.getElementById('emailOtpModal');
            var otpModal = bootstrap.Modal.getOrCreateInstance(otpModalEl);
            otpModal.show();

            setTimeout(function() { $('#otpInput').focus(); }, 500);

            startOtpTimer(600); // 10 minutes
            startResendCooldown(60); // 60 seconds
        } else {
            $('#emailSettingsMessages').html('<div class="alert alert-danger py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-times-circle me-1"></i> ' + (resp.messages || 'Failed to request OTP') + '</div>');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-shield-check me-1"></i> Save Configuration (Requires OTP)');
        $('#emailSettingsMessages').html('<div class="alert alert-danger py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-times-circle me-1"></i> Network error requesting verification code.</div>');
    });
}

function submitEmailOtpVerification() {
    var code = $('#otpInput').val().trim();
    if (!code || code.length !== 6) {
        $('#otpModalMessages').html('<div class="alert alert-warning py-2 small"><i class="fas fa-triangle-exclamation me-1"></i> Please enter the full 6-digit code.</div>');
        return;
    }

    var btn = $('#verifyOtpBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Verifying...');
    $('#otpModalMessages').html('');

    $.post('php_action/verifyEmailConfigOtp.php', {
        csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
        otp_id: currentOtpId,
        otp_code: code
    }, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Verify &amp; Activate');

        if (resp.success) {
            clearInterval(otpTimerInterval);
            clearInterval(resendTimerInterval);

            var otpModalEl = document.getElementById('emailOtpModal');
            var otpModal = bootstrap.Modal.getInstance(otpModalEl);
            if (otpModal) otpModal.hide();

            $('#mailPassword').val('');
            loadMailSettings();

            $('#emailSettingsMessages').html('<div class="alert alert-success py-2" style="font-size:0.82rem;border-radius:10px"><i class="fas fa-check-circle me-1"></i> ' + resp.messages + '</div>');
            setTimeout(function() { $('#emailSettingsMessages .alert').fadeOut(); }, 6000);
        } else {
            $('#otpModalMessages').html('<div class="alert alert-danger py-2 small"><i class="fas fa-times-circle me-1"></i> ' + resp.messages + '</div>');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Verify &amp; Activate');
        $('#otpModalMessages').html('<div class="alert alert-danger py-2 small"><i class="fas fa-times-circle me-1"></i> Error contacting server.</div>');
    });
}

function resendEmailOtp() {
    if (!currentOtpId) return;

    var btn = $('#resendOtpBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Sending...');

    $.post('php_action/resendEmailConfigOtp.php', {
        csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
        otp_id: currentOtpId
    }, function(resp) {
        if (resp.success) {
            $('#otpModalMessages').html('<div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i> ' + resp.messages + '</div>');
            startOtpTimer(600);
            startResendCooldown(60);
        } else {
            $('#otpModalMessages').html('<div class="alert alert-danger py-2 small"><i class="fas fa-times-circle me-1"></i> ' + resp.messages + '</div>');
            btn.prop('disabled', false).html('<i class="fas fa-rotate-right me-1"></i> Resend code');
        }
    }, 'json');
}

function startOtpTimer(seconds) {
    clearInterval(otpTimerInterval);
    var remaining = seconds;

    function update() {
        var m = Math.floor(remaining / 60);
        var s = remaining % 60;
        $('#otpTimer').text((m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s);
        if (remaining <= 0) {
            clearInterval(otpTimerInterval);
            $('#otpModalMessages').html('<div class="alert alert-warning py-2 small"><i class="fas fa-clock me-1"></i> OTP code has expired. Please click resend.</div>');
        }
        remaining--;
    }
    update();
    otpTimerInterval = setInterval(update, 1000);
}

function startResendCooldown(seconds) {
    clearInterval(resendTimerInterval);
    var remaining = seconds;
    var btn = $('#resendOtpBtn');
    btn.prop('disabled', true);

    function update() {
        if (remaining <= 0) {
            clearInterval(resendTimerInterval);
            btn.prop('disabled', false).html('<i class="fas fa-rotate-right me-1"></i> Resend code');
        } else {
            btn.html('<i class="fas fa-rotate-right me-1"></i> Resend code (' + remaining + 's)');
            remaining--;
        }
    }
    update();
    resendTimerInterval = setInterval(update, 1000);
}

function cancelOtpFlow() {
    clearInterval(otpTimerInterval);
    clearInterval(resendTimerInterval);
}

function openTestEmailModal() {
    $('#testEmailMessages').html('');
    var testModalEl = document.getElementById('testEmailModal');
    var testModal = bootstrap.Modal.getOrCreateInstance(testModalEl);
    testModal.show();
}

function executeSendTestEmail() {
    var recipient = $('#testEmailRecipient').val().trim();
    if (!recipient) {
        $('#testEmailMessages').html('<div class="alert alert-warning py-2 small"><i class="fas fa-triangle-exclamation me-1"></i> Please enter a recipient email address.</div>');
        return;
    }

    var btn = $('#sendTestEmailBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Sending...');
    $('#testEmailMessages').html('<div class="alert alert-info py-2 small"><i class="fas fa-spinner fa-spin me-1"></i> Connecting to SMTP server and transmitting test email...</div>');

    var postData = {
        csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>',
        recipient_email: recipient,
        test_host: $('#mailHost').val(),
        test_port: $('#mailPort').val(),
        test_encryption: $('#mailEncryption').val(),
        test_username: $('#mailUsername').val(),
        test_password: $('#mailPassword').val(),
        test_from_email: $('#mailFromEmail').val(),
        test_from_name: $('#mailFromName').val()
    };

    $.post('php_action/sendTestEmail.php', postData, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send Test Message');
        if (resp.success) {
            $('#testEmailMessages').html('<div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i> ' + resp.messages + '</div>');
        } else {
            $('#testEmailMessages').html('<div class="alert alert-danger py-2 small"><i class="fas fa-times-circle me-1"></i> ' + resp.messages + '</div>');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send Test Message');
        $('#testEmailMessages').html('<div class="alert alert-danger py-2 small"><i class="fas fa-times-circle me-1"></i> Unable to reach test email service endpoint.</div>');
    });
}

// ═══ THEME ═══
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    updateThemeUI(theme);
}

function updateThemeUI(theme) {
    $('#themePreviewLight, #themePreviewDark').removeClass('active');
    if (theme === 'dark') {
        $('#themePreviewDark').addClass('active');
        $('#currentThemeLabel').text('Dark');
    } else {
        $('#themePreviewLight').addClass('active');
        $('#currentThemeLabel').text('Light');
    }
}

// ═══ INIT ═══
$(document).ready(function() {
    // Load saved settings
    loadMailSettings();
    loadVoiceSettings();
    <?php if ($currentUser == 1): ?>
    loadAISettings();
    <?php endif; ?>

    // Theme UI
    var currentTheme = document.documentElement.getAttribute('data-theme') || localStorage.getItem('theme') || 'light';
    updateThemeUI(currentTheme);

    // General settings
    var savedReorder = localStorage.getItem('defaultReorderLevel');
    if (savedReorder) $('#defaultReorderLevel').val(savedReorder);
});
</script>
