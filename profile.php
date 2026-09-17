<?php
require_once 'bootstrap_simple.php';

// Check authentication
if (!SimpleSecurity::isAuthenticated()) {
    SimpleSecurity::redirect('login_secure.php');
}

// Ensure CSRF token is set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set user variables from session
$currentUser = SimpleSecurity::getUserId();
$currentUsername = SimpleSecurity::getUsername();

global $connect;

$userInfo = null;

// Fetch user info from database
if (!empty($currentUser)) {
    $stmt = $connect->prepare("SELECT * FROM users WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $currentUser);
        $stmt->execute();
        $userInfo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$userInfo && !empty($currentUsername)) {
    $stmt = $connect->prepare("SELECT * FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $currentUsername);
        $stmt->execute();
        $userInfo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// Fallback default if user not found in DB
if (!$userInfo) {
    $userInfo = [
        'user_id' => $currentUser ?: 1,
        'username' => $currentUsername ?: 'admin',
        'email' => $_SESSION['email'] ?? 'admin@agrivet.com',
        'role' => SimpleSecurity::isAdmin() ? 'admin' : 'staff',
        'created_at' => null,
        'last_login' => null
    ];
}

// Set resolved variables
$currentUsername = $userInfo['username'] ?? ($currentUsername ?: 'admin');
$currentUser = $userInfo['user_id'] ?? ($currentUser ?: 1);
$isAdminUser = ($currentUser == 1 || strtolower($userInfo['role'] ?? '') === 'admin' || SimpleSecurity::isAdmin());

// Calculate User Statistics
$totalOrders = 0;
$totalSales = 0.0;
$avgOrderValue = 0.0;

if (!empty($currentUser)) {
    // 1. Orders count & revenue
    $stmtOrd = $connect->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(grand_total), 0) AS rev FROM orders WHERE user_id = ? AND order_status = 1");
    if ($stmtOrd) {
        $stmtOrd->bind_param("i", $currentUser);
        $stmtOrd->execute();
        $ordRes = $stmtOrd->get_result()->fetch_assoc();
        $stmtOrd->close();
    }

    // 2. POS Transactions count & revenue
    $stmtTx = $connect->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(total_payable), 0) AS rev FROM transactions WHERE user_id = ? AND transaction_status = 1");
    if ($stmtTx) {
        $stmtTx->bind_param("i", $currentUser);
        $stmtTx->execute();
        $txRes = $stmtTx->get_result()->fetch_assoc();
        $stmtTx->close();
    }

    // 3. Customer Returns / Refunds
    $stmtRet = $connect->prepare("SELECT COALESCE(SUM(refund_amount), 0) AS rev FROM sales_returns WHERE user_id = ? AND status = 'completed'");
    if ($stmtRet) {
        $stmtRet->bind_param("i", $currentUser);
        $stmtRet->execute();
        $retRes = $stmtRet->get_result()->fetch_assoc();
        $stmtRet->close();
    }

    $ordersCount = intval($ordRes['c'] ?? 0);
    $txCount = intval($txRes['c'] ?? 0);
    $totalOrders = $ordersCount + $txCount;

    $grossSales = floatval($ordRes['rev'] ?? 0) + floatval($txRes['rev'] ?? 0);
    $returnsVal = floatval($retRes['rev'] ?? 0);
    $totalSales = max(0, $grossSales - $returnsVal);

    $avgOrderValue = $totalOrders > 0 ? ($totalSales / $totalOrders) : 0.0;
}

// Fetch user's recent activity logs
$recentActivities = [];
$stmt = $connect->prepare("SELECT module, action, description, created_at FROM activity_logs WHERE username = ? ORDER BY created_at DESC LIMIT 6");
if ($stmt) {
    $stmt->bind_param("s", $currentUsername);
    $stmt->execute();
    $activityResult = $stmt->get_result();
    if ($activityResult) {
        $recentActivities = $activityResult->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

require_once 'includes/header_sidebar.php';
?>

<style>
.profile-container {
    max-width: 1240px;
    margin: 0 auto;
}

/* Hero Card */
.profile-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 20px;
    padding: 2.2rem 2.2rem 1.8rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.25);
    position: relative;
    overflow: hidden;
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

[data-theme="light"] .profile-hero {
    background: linear-gradient(135deg, #1b365d 0%, #0f2442 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.profile-hero-pattern {
    position: absolute;
    top: -40px;
    right: -40px;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(232, 163, 23, 0.15) 0%, rgba(232, 163, 23, 0) 70%);
    pointer-events: none;
}

.profile-hero-avatar {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gold) 0%, #c4820e 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.4rem;
    font-weight: 800;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    border: 4px solid rgba(255,255,255,0.2);
    margin-right: 1.5rem;
    flex-shrink: 0;
}

.profile-badge-role {
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.35rem 0.85rem;
    border-radius: 50rem;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}

/* Stat Cards */
.profile-stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.25rem 1.4rem;
    height: 100%;
    transition: all 0.25s ease;
    box-shadow: 0 4px 14px rgba(0,0,0,0.03);
    display: flex;
    align-items: center;
    gap: 1.1rem;
}

.profile-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    border-color: var(--primary-light);
}

.profile-stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.stat-icon-orders { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
.stat-icon-sales { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.stat-icon-avg { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
.stat-icon-status { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }

.profile-stat-val {
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1.2;
    color: var(--text);
    margin-bottom: 2px;
}

.profile-stat-lbl {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Cards & Content */
.profile-card {
    background: var(--card-bg);
    border-radius: 16px;
    border: 1px solid var(--border);
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.profile-card-header {
    padding: 1.15rem 1.4rem;
    border-bottom: 1px solid var(--border-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--card-bg);
}

.profile-card-header h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-field-row {
    padding: 0.95rem 1.4rem;
    border-bottom: 1px solid var(--border-lightest);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.profile-field-row:last-child {
    border-bottom: none;
}

.profile-field-name {
    font-size: 0.86rem;
    color: var(--text-muted);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-field-val {
    font-size: 0.92rem;
    color: var(--text);
    font-weight: 700;
}

/* Timeline */
.timeline-wrapper {
    position: relative;
    padding-left: 24px;
    margin-top: 0.5rem;
}

.timeline-wrapper::before {
    content: '';
    position: absolute;
    top: 6px;
    bottom: 6px;
    left: 8px;
    width: 2px;
    background: var(--border);
}

.timeline-node {
    position: relative;
    margin-bottom: 1.1rem;
}

.timeline-node:last-child {
    margin-bottom: 0;
}

.timeline-dot {
    position: absolute;
    left: -24px;
    top: 4px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--primary);
    border: 3px solid var(--card-bg);
    box-shadow: 0 0 0 2px var(--primary-light);
}

/* Password strength badges */
.pw-check-item {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text-muted);
    transition: color 0.2s ease;
}

.pw-check-item.valid {
    color: #10b981;
}

.pw-check-item.valid i {
    color: #10b981 !important;
}

@media (max-width: 768px) {
    .profile-hero {
        padding: 1.5rem 1.25rem;
        text-align: center;
    }
    .profile-hero-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .profile-hero-avatar {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    .profile-field-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style>

<div class="profile-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0 mb-3">
            <li class="breadcrumb-item"><a href="dashboard_secure.php" class="text-decoration-none fw-semibold">Home</a></li>
            <li class="breadcrumb-item active fw-semibold">User Profile</li>
        </ol>
    </nav>

    <div id="profileGlobalMessages">
        <?php
        $flashSuccess = $_SESSION['profile_success'] ?? (!empty($_GET['success']) ? 'Profile updated successfully!' : null);
        $flashError = $_SESSION['profile_error'] ?? null;
        unset($_SESSION['profile_success'], $_SESSION['profile_error']);
        ?>
        <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle fs-5"></i>
            <div><strong>Success!</strong> <?php echo htmlspecialchars($flashSuccess); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-triangle fs-5"></i>
            <div><strong>Error:</strong> <?php echo htmlspecialchars($flashError); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Profile Hero Card -->
    <div class="profile-hero">
        <div class="profile-hero-pattern"></div>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 profile-hero-content">
            <div class="d-flex align-items-center flex-wrap">
                <div class="profile-hero-avatar">
                    <?php echo strtoupper(substr($currentUsername ?? 'A', 0, 2)); ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h2 class="h3 fw-bold mb-0 text-white" id="heroUsernameDisplay"><?php echo htmlspecialchars($currentUsername); ?></h2>
                        <?php if ($isAdminUser): ?>
                            <span class="badge bg-danger profile-badge-role"><i class="fas fa-shield-halved me-1"></i> Administrator</span>
                        <?php else: ?>
                            <span class="badge bg-info profile-badge-role"><i class="fas fa-user-check me-1"></i> Staff Member</span>
                        <?php endif; ?>
                        <span class="badge bg-success profile-badge-role"><i class="fas fa-circle me-1" style="font-size:0.5rem; vertical-align:middle;"></i> Active Session</span>
                    </div>
                    <p class="mb-0 text-white-50" style="font-size: 0.9rem;">
                        <i class="fas fa-envelope me-1 opacity-75"></i> <span id="heroEmailDisplay"><?php echo htmlspecialchars($userInfo['email'] ?? 'No email set'); ?></span>
                        <span class="mx-2 opacity-50">&bull;</span>
                        <i class="fas fa-calendar-alt me-1 opacity-75"></i> Member since <?php echo !empty($userInfo['created_at']) ? date('M Y', strtotime($userInfo['created_at'])) : 'System Setup'; ?>
                    </p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                <button class="btn btn-warning fw-bold px-3 shadow-sm btn-sm text-dark" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-user-edit me-1"></i> Edit Profile
                </button>
                <button class="btn btn-outline-light fw-bold px-3 btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal" onclick="openPasswordTab();">
                    <i class="fas fa-key me-1"></i> Change Password
                </button>
            </div>
        </div>
    </div>

    <!-- Stat Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="profile-stat-card">
                <div class="profile-stat-icon stat-icon-orders">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div>
                    <div class="profile-stat-val"><?php echo number_format($totalOrders); ?></div>
                    <div class="profile-stat-lbl">Orders Processed</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="profile-stat-card">
                <div class="profile-stat-icon stat-icon-sales">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <div class="profile-stat-val text-success">₱<?php echo number_format($totalSales, 2); ?></div>
                    <div class="profile-stat-lbl">Total Net Revenue</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="profile-stat-card">
                <div class="profile-stat-icon stat-icon-avg">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <div class="profile-stat-val">₱<?php echo number_format($avgOrderValue, 2); ?></div>
                    <div class="profile-stat-lbl">Avg Order Value</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="profile-stat-card">
                <div class="profile-stat-icon stat-icon-status">
                    <i class="fas fa-shield-check"></i>
                </div>
                <div>
                    <div class="profile-stat-val" style="font-size:1.1rem;"><?php echo !empty($userInfo['last_login']) ? date('M j, g:i a', strtotime($userInfo['last_login'])) : 'Active Now'; ?></div>
                    <div class="profile-stat-lbl">Last Account Activity</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="row">
        <!-- Left Column: User Account Information -->
        <div class="col-lg-6">
            <!-- Account Details Card -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <h5><i class="fas fa-id-card text-primary me-1"></i> Account Details</h5>
                    <button class="btn btn-outline-primary btn-sm fw-semibold" style="font-size:0.78rem;" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="fas fa-edit me-1"></i> Edit Details
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="profile-field-row">
                        <div class="profile-field-name"><i class="fas fa-user-circle text-muted"></i> Username</div>
                        <div class="profile-field-val" id="dispUsername"><?php echo htmlspecialchars($currentUsername); ?></div>
                    </div>
                    <div class="profile-field-row">
                        <div class="profile-field-name"><i class="fas fa-envelope text-muted"></i> Email Address</div>
                        <div class="profile-field-val" id="dispEmail"><?php echo htmlspecialchars($userInfo['email'] ?? 'Not set'); ?></div>
                    </div>
                    <div class="profile-field-row">
                        <div class="profile-field-name"><i class="fas fa-user-shield text-muted"></i> User Role</div>
                        <div class="profile-field-val">
                            <?php if ($isAdminUser): ?>
                                <span class="badge bg-danger"><i class="fas fa-shield-alt me-1"></i> Administrator</span>
                            <?php else: ?>
                                <span class="badge bg-info"><i class="fas fa-user me-1"></i> Staff Member</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="profile-field-row">
                        <div class="profile-field-name"><i class="fas fa-lock text-muted"></i> Security Protection</div>
                        <div class="profile-field-val">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                <i class="fas fa-shield-check me-1"></i> Password Protected
                            </span>
                        </div>
                    </div>
                    <div class="profile-field-row">
                        <div class="profile-field-name"><i class="fas fa-calendar-check text-muted"></i> Account Registered</div>
                        <div class="profile-field-val"><?php echo !empty($userInfo['created_at']) ? date('F j, Y', strtotime($userInfo['created_at'])) : 'System Initialization'; ?></div>
                    </div>
                </div>
            </div>

            <!-- Quick Navigation Actions -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <h5><i class="fas fa-bolt text-warning me-1"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php if (SimpleSecurity::hasPermission('transaction')): ?>
                        <div class="col-6">
                            <a href="transaction.php" class="btn btn-primary w-100 fw-bold btn-sm py-2">
                                <i class="fas fa-cash-register me-1"></i> New Transaction
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="col-6">
                            <button class="btn btn-warning w-100 fw-bold btn-sm py-2 text-dark" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-user-edit me-1"></i> Edit Profile
                            </button>
                        </div>
                        <?php if (SimpleSecurity::hasPermission('settings')): ?>
                        <div class="col-6">
                            <a href="setting.php" class="btn btn-secondary w-100 fw-bold btn-sm py-2">
                                <i class="fas fa-cog me-1"></i> System Settings
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SimpleSecurity::hasPermission('activity_logs')): ?>
                        <div class="col-6">
                            <a href="activity_logs.php" class="btn btn-outline-info w-100 fw-bold btn-sm py-2">
                                <i class="fas fa-history me-1"></i> Activity Logs
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (SimpleSecurity::hasPermission('backup')): ?>
                        <div class="col-6">
                            <a href="backup.php" class="btn btn-outline-primary w-100 fw-bold btn-sm py-2">
                                <i class="fas fa-database me-1"></i> Backup &amp; Restore
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Security Summary & Recent Activity -->
        <div class="col-lg-6">
            <!-- Access & Permissions Summary Card -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <h5><i class="fas fa-key text-info me-1"></i> Permissions & Access</h5>
                </div>
                <div class="card-body">
                    <?php if ($isAdminUser): ?>
                        <div class="alert alert-success d-flex align-items-center mb-0 gap-2 py-2" style="font-size:0.88rem;">
                            <i class="fas fa-shield-alt text-success fs-5"></i>
                            <div>
                                <strong>Full Administrator Access</strong>
                                <div class="text-muted" style="font-size:0.8rem;">You have unrestricted administrative privileges across all system features.</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php 
                            $granted = $_SESSION['custom_permissions'] ?? ['transaction', 'returns', 'products', 'stock_movement', 'purchase_order'];
                            foreach ($granted as $permKey):
                                $label = ucwords(str_replace('_', ' ', $permKey));
                            ?>
                                <span class="badge bg-light text-dark border px-2 py-2 fw-semibold" style="font-size:0.82rem;">
                                    <i class="fas fa-check-circle text-success me-1"></i> <?php echo htmlspecialchars($label); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity Timeline -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <h5><i class="fas fa-history text-secondary me-1"></i> Recent Activity</h5>
                    <?php if (SimpleSecurity::hasPermission('activity_logs')): ?>
                        <a href="activity_logs.php" class="text-decoration-none fw-bold" style="font-size:0.82rem;">View All <i class="fas fa-arrow-right ms-1"></i></a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentActivities)): ?>
                        <div class="timeline-wrapper">
                            <?php foreach ($recentActivities as $act): ?>
                                <div class="timeline-node">
                                    <div class="timeline-dot"></div>
                                    <div class="fw-bold" style="font-size:0.88rem; color:var(--text);">
                                        <?php echo htmlspecialchars($act['action']); ?> &bull; <span class="text-muted fw-normal"><?php echo htmlspecialchars($act['module']); ?></span>
                                    </div>
                                    <div style="font-size:0.82rem; color:var(--text-muted);" class="mt-1">
                                        <?php echo htmlspecialchars($act['description']); ?>
                                    </div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);" class="mt-1 opacity-75">
                                        <i class="far fa-clock me-1"></i><?php echo date('M j, Y &bull; g:i a', strtotime($act['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted" style="font-size:0.88rem;">
                            <i class="fas fa-clock fa-2x mb-2 opacity-50 d-block"></i>
                            No activity logged for your user account yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg" style="border-radius:18px; border:1px solid var(--border); overflow:hidden;">
            <div class="modal-header px-4 py-3" style="background:var(--card-bg); border-bottom:1px solid var(--border-light);">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="editProfileModalLabel">
                    <i class="fas fa-user-cog text-primary"></i> Edit Profile & Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalProfileMessages"></div>

                <!-- Tab navigation -->
                <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-3 border" id="profileTabs" role="tablist" style="background:var(--border-lightest) !important;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold py-2" id="details-tab" data-bs-toggle="pill" data-bs-target="#details-tab-pane" type="button" role="tab" aria-controls="details-tab-pane" aria-selected="true">
                            <i class="fas fa-user-edit me-1"></i> Account Details
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold py-2" id="password-tab" data-bs-toggle="pill" data-bs-target="#password-tab-pane" type="button" role="tab" aria-controls="password-tab-pane" aria-selected="false">
                            <i class="fas fa-key me-1"></i> Change Password
                        </button>
                    </li>
                </ul>

                <form id="updateProfileForm" action="php_action/updateProfile.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <div class="tab-content" id="profileTabsContent">
                        <!-- Details Tab -->
                        <div class="tab-pane fade show active" id="details-tab-pane" role="tabpanel" aria-labelledby="details-tab" tabindex="0">
                            <div class="mb-3">
                                <label for="profUsername" class="form-label fw-bold" style="font-size:0.88rem;">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                                    <input type="text" class="form-control" id="profUsername" name="username" value="<?php echo htmlspecialchars($userInfo['username']); ?>" required minlength="3" maxlength="50">
                                </div>
                                <div class="form-text" style="font-size:0.78rem;">Username used for logging into the inventory system.</div>
                            </div>
                            <div class="mb-3">
                                <label for="profEmail" class="form-label fw-bold" style="font-size:0.88rem;">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control" id="profEmail" name="email" value="<?php echo htmlspecialchars($userInfo['email'] ?? ''); ?>" placeholder="user@domain.com">
                                </div>
                                <div class="form-text" style="font-size:0.78rem;">Used for system notifications and account communication.</div>
                            </div>
                        </div>

                        <!-- Password Tab -->
                        <div class="tab-pane fade" id="password-tab-pane" role="tabpanel" aria-labelledby="password-tab" tabindex="0">
                            <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size:0.82rem;">
                                <i class="fas fa-info-circle fs-6 text-info"></i>
                                <span>Leave password fields empty if you don't wish to change your password.</span>
                            </div>

                            <div class="mb-3">
                                <label for="profCurrentPassword" class="form-label fw-bold" style="font-size:0.88rem;">Current Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control" id="profCurrentPassword" name="current_password" placeholder="Enter current password">
                                    <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="#profCurrentPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="profNewPassword" class="form-label fw-bold" style="font-size:0.88rem;">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key text-muted"></i></span>
                                    <input type="password" class="form-control" id="profNewPassword" name="new_password" placeholder="Enter new password">
                                    <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="#profNewPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="profConfirmPassword" class="form-label fw-bold" style="font-size:0.88rem;">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-check-double text-muted"></i></span>
                                    <input type="password" class="form-control" id="profConfirmPassword" name="confirm_password" placeholder="Re-enter new password">
                                    <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="#profConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Live Password Requirements Tracker -->
                            <div class="p-3 bg-light rounded-3 border" style="background:var(--border-lightest) !important;">
                                <div class="fw-bold mb-2 text-muted" style="font-size:0.8rem; text-transform:uppercase;">Password Requirements</div>
                                <div class="row g-2">
                                    <div class="col-6 pw-check-item" id="reqLength"><i class="far fa-circle me-1"></i> At least 8 characters</div>
                                    <div class="col-6 pw-check-item" id="reqUpper"><i class="far fa-circle me-1"></i> Uppercase letter (A-Z)</div>
                                    <div class="col-6 pw-check-item" id="reqLower"><i class="far fa-circle me-1"></i> Lowercase letter (a-z)</div>
                                    <div class="col-6 pw-check-item" id="reqNumber"><i class="far fa-circle me-1"></i> Number (0-9)</div>
                                    <div class="col-12 pw-check-item" id="reqSpecial"><i class="far fa-circle me-1"></i> Special character (!@#$%^&*)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer px-0 pb-0 pt-3 border-top mt-4">
                        <button type="button" class="btn btn-secondary btn-sm px-3 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="saveProfileBtn">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script>
function openPasswordTab() {
    const passwordTabBtn = document.getElementById('password-tab');
    if (passwordTabBtn) {
        const tab = new bootstrap.Tab(passwordTabBtn);
        tab.show();
    }
}

$(document).ready(function() {
    // Automatically trigger edit modal if anchor #editModal is present in URL
    if (window.location.hash === '#editModal') {
        const modalEl = document.getElementById('editProfileModal');
        if (modalEl) {
            const myModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            myModal.show();
        }
    }

    // Toggle password visibility
    $('.toggle-password-btn').on('click', function() {
        const input = $($(this).data('target'));
        const icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Real-time password requirement tracker
    $('#profNewPassword').on('input', function() {
        const val = $(this).val();
        
        function setCheck(id, isValid) {
            const el = $('#' + id);
            const icon = el.find('i');
            if (isValid) {
                el.addClass('valid');
                icon.removeClass('far fa-circle').addClass('fas fa-check-circle');
            } else {
                el.removeClass('valid');
                icon.removeClass('fas fa-check-circle').addClass('far fa-circle');
            }
        }

        setCheck('reqLength', val.length >= 8);
        setCheck('reqUpper', /[A-Z]/.test(val));
        setCheck('reqLower', /[a-z]/.test(val));
        setCheck('reqNumber', /[0-9]/.test(val));
        setCheck('reqSpecial', /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(val));
    });

    // AJAX Form submission
    $('#updateProfileForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const btn = $('#saveProfileBtn');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
        $('#modalProfileMessages').html('');

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize() + '&ajax=1',
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Changes');
                if (response.success) {
                    $('#modalProfileMessages').html('<div class="alert alert-success d-flex align-items-center gap-2"><i class="fas fa-check-circle fs-5"></i> <div><strong>Success!</strong> ' + response.messages + '</div></div>');
                    
                    $('#profileGlobalMessages').html('<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm mb-4" role="alert"><i class="fas fa-check-circle fs-5"></i> <div><strong>Success!</strong> ' + response.messages + '</div><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');

                    if (response.username) {
                        $('#dispUsername').text(response.username);
                        $('#heroUsernameDisplay').text(response.username);
                        $('.profile-hero-avatar').text(response.username.substring(0, 2).toUpperCase());
                        $('.user-name-display, #topNavUserDropdown, .nav-user-name').text(response.username);
                    }
                    if (response.email !== undefined) {
                        const emailText = response.email || 'Not set';
                        $('#dispEmail').text(emailText);
                        $('#heroEmailDisplay').text(emailText);
                    }

                    // Reset password fields
                    $('#profCurrentPassword, #profNewPassword, #profConfirmPassword').val('');
                    $('.pw-check-item').removeClass('valid').find('i').removeClass('fas fa-check-circle').addClass('far fa-circle');

                    setTimeout(function() {
                        const modalEl = document.getElementById('editProfileModal');
                        if (modalEl) {
                            const myModal = bootstrap.Modal.getInstance(modalEl);
                            if (myModal) myModal.hide();
                        }
                        location.reload();
                    }, 1200);
                } else {
                    $('#modalProfileMessages').html('<div class="alert alert-danger d-flex align-items-center gap-2"><i class="fas fa-exclamation-triangle fs-5"></i> <div>' + (response.messages || 'Failed to update profile') + '</div></div>');
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Changes');
                $('#modalProfileMessages').html('<div class="alert alert-danger d-flex align-items-center gap-2"><i class="fas fa-exclamation-triangle fs-5"></i> <div>An error occurred while saving profile details. Please try again.</div></div>');
            }
        });
    });
});
</script>
