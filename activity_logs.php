<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('activity_logs');
require_once 'includes/header_sidebar.php';
?>

<nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0 mb-2">
            <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
            <li class="breadcrumb-item active">Activity Logs</li>
        </ol>
    </nav>

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:1rem;">
        <h2 style="font-size:1.15rem;font-weight:700;color:var(--text);margin:0;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-history" style="color:var(--primary);font-size:.95rem;"></i> Activity Logs
        </h2>
        <?php if (SimpleSecurity::isAdmin()): ?>
        <button class="btn btn-danger btn-sm" onclick="clearOldLogs()">
            <i class="fas fa-trash me-1"></i> Clear Logs (30+ days)
        </button>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body">

            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Module</label>
                    <select class="form-select" id="filterModule">
                        <option value="">All Modules</option>
                        <option value="Auth">Authentication</option>
                        <option value="Products">Products</option>
                        <option value="Brands">Brands</option>
                        <option value="Categories">Categories</option>
                        <option value="Orders">Orders</option>
                        <option value="Transactions">Transactions</option>
                        <option value="Users">Users</option>
                        <option value="Settings">Settings</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Action</label>
                    <select class="form-select" id="filterAction">
                        <option value="">All Actions</option>
                        <option value="CREATE">Create</option>
                        <option value="UPDATE">Update</option>
                        <option value="DELETE">Delete</option>
                        <option value="LOGIN">Login</option>
                        <option value="LOGOUT">Logout</option>
                        <option value="PRINT">Print</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">User</label>
                    <select class="form-select" id="filterUser">
                        <option value="">All Users</option>
                        <?php
                        if ($userStmt = $connect->prepare("SELECT DISTINCT username FROM activity_logs WHERE username IS NOT NULL AND username != '' ORDER BY username")) {
                            $userStmt->execute();
                            $userResult = $userStmt->get_result();
                            while ($u = $userResult->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            $userStmt->close();
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="text" class="form-control" id="filterDate" placeholder="Select date...">
                </div>
            </div>

            <div class="clear-messages"></div>

            <div class="table-responsive">
                <table class="table table-hover" id="activityLogsTable" style="width:100%;">
                    <thead>
                        <tr>
                            <th scope="col" style="width:5%;">#</th>
                            <th scope="col" style="width:12%;">Date/Time</th>
                            <th scope="col" style="width:10%;">User</th>
                            <th scope="col" style="width:8%;">Action</th>
                            <th scope="col" style="width:10%;">Module</th>
                            <th scope="col">Description</th>
                            <th scope="col" style="width:10%;">IP Address</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script>
var logsTable;

$(document).ready(function() {
    $('#navActivityLogs').addClass('active');

    // Initialize datepicker
    if ($.fn.datepicker) {
        $("#filterDate").datepicker({ dateFormat: 'yy-mm-dd' });
    }

    // Suppress default alert popup on AJAX error and log to console
    $.fn.dataTable.ext.errMode = 'none';
    $('#activityLogsTable').on('error.dt', function(e, settings, techNote, message) {
        console.error('DataTables error:', message);
    });

    // Initialize DataTable
    logsTable = $('#activityLogsTable').DataTable({
        'ajax': {
            url: 'php_action/fetchActivityLogs.php',
            type: 'GET',
            data: function(d) {
                d.module = $('#filterModule').val();
                d.action = $('#filterAction').val();
                d.user = $('#filterUser').val();
                d.date = $('#filterDate').val();
            },
            error: function(xhr, error, thrown) {
                if (xhr.status === 401 || xhr.status === 403) {
                    window.location.href = 'login_secure.php?error=session_expired';
                }
            }
        },
        'order': [[0, 'desc']],
        'pageLength': 25,
        'responsive': true,
        'processing': true,
        'language': {
            'emptyTable': '<div style="text-align:center;padding:2rem;color:#999;"><i class="fas fa-history" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3;"></i>No activity logs found</div>',
            'zeroRecords': '<div style="text-align:center;padding:1rem;color:#999;">No matching activity logs</div>',
            'processing': '<div class="dt-loading-spinner"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading…</div>'
        }
    });

    // Filter change handlers
    $('#filterModule, #filterAction, #filterUser').on('change', function() {
        logsTable.ajax.reload();
    });
    $('#filterDate').on('change', function() {
        logsTable.ajax.reload();
    });
});

function clearOldLogs() {
    if (!confirm('This will delete all activity logs older than 30 days. Continue?')) return;

    $.ajax({
        url: 'php_action/clearActivityLogs.php',
        type: 'POST',
        data: {
            csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                logsTable.ajax.reload();
                $('.clear-messages').html('<div class="alert alert-success"><button type="button" class="close" data-bs-dismiss="alert">&times;</button><strong><i class="fas fa-check-circle"></i></strong> ' + response.messages + '</div>');
                $(".alert-success").delay(3000).fadeOut(500);
            } else {
                $('.clear-messages').html('<div class="alert alert-danger"><button type="button" class="close" data-bs-dismiss="alert">&times;</button><strong><i class="fas fa-exclamation-circle"></i></strong> ' + (response.messages || 'Failed to clear logs') + '</div>');
            }
        },
        error: function(xhr) {
            $('.clear-messages').html('<div class="alert alert-danger"><button type="button" class="close" data-bs-dismiss="alert">&times;</button><strong><i class="fas fa-exclamation-circle"></i></strong> An error occurred while clearing logs.</div>');
        }
    });
}
</script>
