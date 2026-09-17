<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requireAdmin();
require_once 'includes/header_sidebar.php';
?>

<style>
    /* ============================================================
       USER MANAGEMENT — Premium Redesign
       ============================================================ */
    .users-container { min-height: calc(100vh - 120px); }

    /* ---- Page Header Section ---- */
    .page-header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
        padding-top: 12px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .page-header-title {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .page-header-icon {
        background: linear-gradient(135deg, var(--primary), var(--gold));
        color: #fff;
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 14px rgba(201,84,12,0.25);
    }
    .page-header-title h2 {
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        margin: 0;
    }
    .page-header-title p {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin: 0;
        margin-top: 2px;
    }

    /* ---- Stat Cards Row ---- */
    .stats-row {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
        min-width: 160px;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--shadow);
    }
    .stat-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .stat-card-icon.total { background: var(--primary-light); color: var(--primary); }
    .stat-card-icon.admin { background: #FEF3C7; color: #92400E; }
    .stat-card-icon.staff { background: #DEF7EC; color: #03543F; }
    [data-theme="dark"] .stat-card-icon.admin { background: #3A2E10; color: #E8A317; }
    [data-theme="dark"] .stat-card-icon.staff { background: #1A3A2A; color: #6EE7A0; }
    
    .stat-card-label {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-card-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--text);
        line-height: 1.2;
    }

    /* ---- Unified Card Container ---- */
    .users-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .users-card-header {
        background: transparent;
        border-bottom: 1px solid var(--border);
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    .users-card-header h3 {
        font-family: var(--font-display);
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text);
        margin: 0;
    }

    /* ---- Action Buttons ---- */
    .btn-tab-action {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 700;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(201,84,12,0.2);
    }
    .btn-tab-action:hover {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(201,84,12,0.3);
    }

    /* ---- Table Action Buttons ---- */
    .btn-action-edit {
        background: var(--border-lightest);
        color: var(--text-muted);
        border: 1px solid var(--border);
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        font-size: 0.8rem;
    }
    .btn-action-edit:hover {
        background: var(--primary-light);
        color: var(--primary);
        border-color: var(--primary);
        transform: translateY(-1px);
    }
    .btn-action-delete {
        background: #FDE8E8;
        color: #DC3545;
        border: 1px solid #FECACA;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        margin-left: 6px;
        font-size: 0.8rem;
    }
    .btn-action-delete:hover {
        background: #DC3545;
        color: #fff;
        border-color: #DC3545;
        transform: translateY(-1px);
    }
    [data-theme="dark"] .btn-action-delete {
        background: #3A1515;
        color: #FCA5A5;
        border-color: #5A2020;
    }
    [data-theme="dark"] .btn-action-delete:hover {
        background: #DC3545;
        color: #fff;
    }

    /* ---- Status Badges ---- */
    .badge-in-stock, .badge-low-stock {
        font-weight: 600;
        font-size: 0.73rem;
        padding: 5px 10px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-in-stock {
        background-color: #DEF7EC;
        color: #03543F;
    }
    .badge-low-stock {
        background-color: #FEF3C7;
        color: #92400E;
    }
    [data-theme="dark"] .badge-in-stock {
        background-color: #1A3A2A;
        color: #6EE7A0;
    }
    [data-theme="dark"] .badge-low-stock {
        background-color: #3A2E10;
        color: #FCD34D;
    }

    /* ---- Modal Styling ---- */
    .modal-content {
        border-radius: 16px;
        border: 1px solid var(--border);
        overflow: hidden;
    }
    .modal-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        border-bottom: none;
        padding: 18px 24px;
    }
    .modal-header .modal-title {
        font-family: var(--font-display);
        font-weight: 700;
        font-size: 1.1rem;
    }
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }
    .modal-header .btn-close:hover {
        opacity: 1;
    }
    .modal-body {
        padding: 24px;
    }
    .modal-footer {
        border-top: 1px solid var(--border);
        padding: 16px 24px;
        background: var(--border-lightest);
    }
    [data-theme="dark"] .modal-footer {
        background: var(--input-bg);
    }
    .modal-footer .btn {
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 20px;
    }

    /* Form Fields */
    .form-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text);
        margin-bottom: 6px;
    }
    .form-control, .form-select {
        border-radius: 10px;
        border-color: var(--border);
        padding: 10px 14px;
        font-size: 0.9rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(201,84,12,0.08);
    }

    /* Delete Confirmation Modal */
    .delete-confirm-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #FDE8E8;
        color: #DC3545;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 16px;
    }
    [data-theme="dark"] .delete-confirm-icon {
        background: #3A1515;
        color: #FCA5A5;
    }
</style>

<div class="users-container">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
            <li class="breadcrumb-item active">Users</li>
        </ol>
    </nav>

    <!-- Page Header with Stats -->
    <div class="page-header-section">
        <div class="page-header-title">
            <div class="page-header-icon"><i class="fas fa-users"></i></div>
            <div>
                <h2>Manage Users</h2>
                <p>Add and configure staff and administrator accounts and set page access permissions.</p>
            </div>
        </div>
        
        <?php
        $totalUsers = $connect->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
        $adminUsers = $connect->query("SELECT COUNT(*) as c FROM users WHERE role='admin'")->fetch_assoc()['c'];
        $staffUsers = $connect->query("SELECT COUNT(*) as c FROM users WHERE role='staff'")->fetch_assoc()['c'];
        ?>
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-card-icon total"><i class="fas fa-users-viewfinder"></i></div>
                <div>
                    <div class="stat-card-label">Total Users</div>
                    <div class="stat-card-value"><?= $totalUsers ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon admin"><i class="fas fa-user-shield"></i></div>
                <div>
                    <div class="stat-card-label">Administrators</div>
                    <div class="stat-card-value"><?= $adminUsers ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon staff"><i class="fas fa-users-gear"></i></div>
                <div>
                    <div class="stat-card-label">Cashier / Staff</div>
                    <div class="stat-card-value"><?= $staffUsers ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="users-card">
        <div class="users-card-header">
            <h3>User Accounts List</h3>
            <button class="btn btn-tab-action" data-bs-toggle="modal" id="addUserModalBtn" data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
        <div class="card-body p-4">
            <div class="remove-messages"></div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="manageUserTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th scope="col">Username</th>
                            <th scope="col">Email Address</th>
                            <th scope="col" style="width: 160px;">Role</th>
                            <th scope="col" style="width: 160px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     DIALOGS & MODALS — Users Management
     ============================================================ -->

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="submitUserForm" action="php_action/createUser.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus me-2"></i>Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height:500px; overflow:auto;">
                    <div id="add-user-messages"></div>

                    <div class="mb-3 form-group">
                        <label for="userName" class="form-label">Username</label>
                        <input type="text" class="form-control" id="userName" placeholder="Enter username" name="userName" autocomplete="off">
                    </div>
                    <div class="mb-3 form-group">
                        <label for="upassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="upassword" placeholder="Enter password" name="upassword" autocomplete="off">
                        <div class="form-text" style="font-size: 0.72rem; color: var(--text-muted);"><i class="fas fa-info-circle me-1"></i>Must be at least 8 characters with uppercase, lowercase, a number, and a special character.</div>
                    </div>
                    <div class="mb-3 form-group">
                        <label for="uemail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="uemail" placeholder="Enter email address" name="uemail" autocomplete="off">
                    </div>
                    <div class="mb-3 form-group">
                        <label for="urole" class="form-label">Role</label>
                        <select class="form-select" id="urole" name="urole">
                            <option value="staff" selected>Cashier / Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createUserBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height:500px; overflow:auto;">
                <div class="div-loading">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-pulse fa-2x" style="color:var(--primary);"></i>
                        <p class="mt-2" style="font-size:.85rem;color:var(--text-muted);">Loading user...</p>
                    </div>
                </div>

                <div class="div-result">
                    <form id="editUserForm" action="php_action/editUser.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        <div id="edit-user-messages"></div>

                        <div class="mb-3 form-group">
                            <label for="edituserName" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edituserName" placeholder="Enter username" name="edituserName" autocomplete="off">
                        </div>
                        <div class="mb-3 form-group">
                            <label for="editPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="editPassword" placeholder="Leave blank to keep current password" name="editPassword" autocomplete="off">
                            <div class="form-text" style="font-size: 0.72rem; color: var(--text-muted);"><i class="fas fa-info-circle me-1"></i>If changing, must be at least 8 characters with uppercase, lowercase, a number, and a special character.</div>
                        </div>
                        <div class="mb-3 form-group">
                            <label for="editRole" class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="editRole">
                                <option value="staff">Cashier / Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="modal-footer editUserFooter">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                            <button type="submit" class="btn btn-success" id="editUserBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Permissions Modal -->
<div class="modal fade" id="permissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="permissionsForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <input type="hidden" name="user_id" id="permissionsUserId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionsModalLabel"><i class="fas fa-key me-2"></i>Page Permissions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 450px; overflow-y: auto;">
                    <div id="permissions-messages"></div>
                    <p style="font-size:.85rem;color:var(--text-muted); margin-bottom: 20px;">Control which pages this staff account can use.</p>

                    <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);margin-bottom:.6rem;">Sales Access <span style="font-weight:400;text-transform:none;letter-spacing:0;">(granted by default — uncheck to restrict)</span></p>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="transaction" id="permTransaction">
                        <label class="form-check-label" for="permTransaction">Transaction / POS</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="orders" id="permOrders">
                        <label class="form-check-label" for="permOrders">Wholesale Orders</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="customer_orders" id="permCustomerOrders">
                        <label class="form-check-label" for="permCustomerOrders">Customer Orders</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="returns" id="permReturns">
                        <label class="form-check-label" for="permReturns">Returns &amp; Refunds</label>
                    </div>

                    <hr class="my-3">

                    <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);margin-bottom:.6rem;">Inventory Access <span style="font-weight:400;text-transform:none;letter-spacing:0;">(granted by default — uncheck to restrict)</span></p>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="products" id="permProducts">
                        <label class="form-check-label" for="permProducts">Products</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="stock_movement" id="permStockMovement">
                        <label class="form-check-label" for="permStockMovement">Stock In / Out</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="purchase_order" id="permPurchaseOrder">
                        <label class="form-check-label" for="permPurchaseOrder">Purchase Orders</label>
                    </div>

                    <hr class="my-3">

                    <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);margin-bottom:.6rem;">Admin Pages <span style="font-weight:400;text-transform:none;letter-spacing:0;">(blocked by default — check to grant)</span></p>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="settings" id="permSettings">
                        <label class="form-check-label" for="permSettings">Settings</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="reports" id="permReports">
                        <label class="form-check-label" for="permReports">Reports</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="stock_forecast" id="permStockForecast">
                        <label class="form-check-label" for="permStockForecast">Stock Forecast</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="activity_logs" id="permActivityLogs">
                        <label class="form-check-label" for="permActivityLogs">Activity Logs</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="backup" id="permBackup">
                        <label class="form-check-label" for="permBackup">Backup &amp; Restore</label>
                    </div>
                </div>
                <div class="modal-footer" style="flex-direction:column;align-items:stretch;gap:.5rem;">
                    <p style="font-size:.75rem;color:var(--text-muted);margin:0;text-align:center;">Takes effect next time this user logs in.</p>
                    <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                        <button type="submit" class="btn btn-success" id="savePermissionsBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove User Modal -->
<div class="modal fade" tabindex="-1" aria-hidden="true" id="removeUserModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #DC3545, #B02A37);">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Remove User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="removeUserMessages"></div>
                <div class="text-center py-3">
                    <div class="delete-confirm-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p style="font-size:.9rem;color:var(--text);margin:0;font-weight:600;">Are you sure you want to remove this user?</p>
                    <p style="font-size:.78rem;color:var(--text-muted);margin-top:.5rem;">This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer removeUserFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                <button type="button" class="btn btn-danger" id="removeUserBtn" data-loading-text="Loading..."><i class="fas fa-trash me-1"></i>Remove</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script src="custom/js/user.js?v=<?= time() ?>"></script>
