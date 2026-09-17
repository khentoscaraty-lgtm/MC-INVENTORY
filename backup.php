<?php
require_once __DIR__ . '/bootstrap_simple.php';
// Only admins or users with backup or settings permission may access backups
if (!SimpleSecurity::isAdmin() && !SimpleSecurity::hasPermission('backup') && !SimpleSecurity::hasPermission('settings')) {
    SimpleSecurity::redirect('dashboard_secure.php?error=access_denied');
}
require_once 'includes/header_sidebar.php';
?>

<style>
/* ── Backup page custom styles ───────────────────────────────────── */
.backup-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.metric-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 20px 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.22s ease, box-shadow 0.22s ease;
    position: relative;
    overflow: hidden;
}
.metric-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}
.metric-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 14px 14px 0 0;
}
.metric-card.mc-primary::before   { background: linear-gradient(90deg, var(--primary), var(--primary-dark)); }
.metric-card.mc-success::before   { background: linear-gradient(90deg, #10b981, #059669); }
.metric-card.mc-info::before      { background: linear-gradient(90deg, #3b82f6, #2563eb); }
.metric-card.mc-warning::before   { background: linear-gradient(90deg, #f59e0b, #d97706); }
.metric-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.metric-card.mc-primary .metric-icon   { background: rgba(var(--primary-rgb, 99,102,241), .12); color: var(--primary); }
.metric-card.mc-success .metric-icon   { background: rgba(16,185,129,.12); color: #10b981; }
.metric-card.mc-info .metric-icon      { background: rgba(59,130,246,.12); color: #3b82f6; }
.metric-card.mc-warning .metric-icon   { background: rgba(245,158,11,.12); color: #f59e0b; }
.metric-details .metric-label { font-size: .78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; font-weight: 600; margin-bottom: 2px; }
.metric-details .metric-value { font-size: 1.35rem; font-weight: 700; color: var(--text); line-height: 1.2; }
.metric-details .metric-sub   { font-size: .75rem; color: var(--text-muted); margin-top: 2px; }

/* ── Tabs ────────────────────────────────────────────────────────── */
.backup-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--border);
    margin-bottom: 20px;
}
.backup-tab {
    padding: 12px 24px;
    font-size: .9rem;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all .2s ease;
    background: none;
    border-top: none; border-left: none; border-right: none;
}
.backup-tab:hover { color: var(--primary); }
.backup-tab.active { color: var(--primary); border-bottom-color: var(--primary); }

/* ── Table ───────────────────────────────────────────────────────── */
.backup-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.backup-table thead th {
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: var(--text-muted);
    font-weight: 700;
    padding: 12px 14px;
    border-bottom: 2px solid var(--border);
    background: var(--body-bg);
    white-space: nowrap;
}
.backup-table tbody tr {
    transition: background .15s ease;
}
.backup-table tbody tr:hover {
    background: var(--primary-light, rgba(99,102,241,0.04));
}
.backup-table tbody td {
    padding: 14px;
    border-bottom: 1px solid var(--border);
    font-size: .88rem;
    color: var(--text);
    vertical-align: middle;
}
.backup-filename {
    font-weight: 600;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 8px;
}
.backup-filename i { color: var(--warning, #f59e0b); font-size: .95rem; }
.backup-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 600;
}
.backup-status.verified {
    background: rgba(16,185,129,.1);
    color: #10b981;
}
.checksum-text {
    font-family: 'Courier New', monospace;
    font-size: .8rem;
    color: var(--text-muted);
    cursor: pointer;
}
.checksum-text:hover { color: var(--primary); }

/* ── Action Buttons ──────────────────────────────────────────────── */
.backup-actions {
    display: flex;
    gap: 6px;
}
.backup-actions .btn {
    width: 34px;
    height: 34px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: .85rem;
    transition: all .2s ease;
}
.backup-actions .btn:hover { transform: scale(1.1); }

/* ── Pagination ──────────────────────────────────────────────────── */
.backup-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 0 4px;
    flex-wrap: wrap;
    gap: 10px;
}
.backup-pagination .page-info { font-size: .85rem; color: var(--text-muted); }
.backup-pagination .page-btns { display: flex; gap: 4px; align-items: center; }
.backup-pagination .page-btns button {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--card-bg);
    color: var(--text);
    font-size: .85rem;
    cursor: pointer;
    transition: all .2s ease;
    display: flex; align-items: center; justify-content: center;
}
.backup-pagination .page-btns button:hover { border-color: var(--primary); color: var(--primary); }
.backup-pagination .page-btns button.active-page { background: var(--primary); color: #fff; border-color: var(--primary); }
.backup-pagination .page-btns button:disabled { opacity: .4; cursor: not-allowed; }

/* ── Info Banner ─────────────────────────────────────────────────── */
.restore-warning-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-size: .85rem;
    font-weight: 500;
}
.restore-warning-banner.info {
    background: rgba(59,130,246,.08);
    color: #3b82f6;
    border: 1px solid rgba(59,130,246,.15);
}
.restore-warning-banner.warning {
    background: rgba(245,158,11,.08);
    color: #d97706;
    border: 1px solid rgba(245,158,11,.15);
}



/* ── Create Button ───────────────────────────────────────────────── */
.btn-backup-create {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: 10px;
    font-weight: 700;
    font-size: .88rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all .25s ease;
    box-shadow: 0 4px 14px rgba(16,185,129,0.25);
}
.btn-backup-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16,185,129,0.35);
    color: #fff;
}
.btn-backup-create:active { transform: scale(.97); }
.btn-backup-create:disabled {
    opacity: .6; cursor: not-allowed; transform: none;
    box-shadow: none;
}

/* ── Note Input ──────────────────────────────────────────────────── */
.note-input {
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 9px 14px;
    font-size: .85rem;
    background: var(--card-bg);
    color: var(--text);
    width: 260px;
    transition: border-color .2s ease;
}
.note-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb, 99,102,241), .12);
}
.note-input::placeholder { color: var(--text-muted); }

/* ── Panels ──────────────────────────────────────────────────────── */
.tab-panel {
    display: none;
}
.tab-panel.active {
    display: block;
}

.restore-card {
    background: var(--body-bg);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 20px;
}
.restore-card-header {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ── Skeleton loader ─────────────────────────────────────────────── */
.skeleton {
    background: linear-gradient(90deg, var(--border) 25%, var(--body-bg) 50%, var(--border) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 6px;
    display: inline-block;
}
@keyframes shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ── Toast ────────────────────────────────────────────────────────── */
.backup-toast {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 9999;
    min-width: 320px;
    padding: 16px 22px;
    border-radius: 12px;
    font-size: .88rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    transform: translateX(120%);
    transition: transform .35s cubic-bezier(.34,1.56,.64,1);
    pointer-events: auto;
}
.backup-toast.show { transform: translateX(0); }
.backup-toast.success { background: #10b981; color: #fff; }
.backup-toast.error   { background: #ef4444; color: #fff; }
.backup-toast.info    { background: #3b82f6; color: #fff; }

/* ── Switch Toggle ───────────────────────────────────────────────── */
.form-switch .form-check-input {
    width: 2.8em;
    height: 1.5em;
    cursor: pointer;
}
.form-switch .form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

/* ── Empty state ─────────────────────────────────────────────────── */
.empty-state {
    text-align: center;
    padding: 50px 30px;
    color: var(--text-muted);
}
.empty-state i { font-size: 3rem; margin-bottom: 14px; opacity: .3; }
.empty-state p { font-size: .92rem; margin: 0; }

@media (max-width: 768px) {
    .backup-metrics { grid-template-columns: 1fr 1fr; }
    .note-input { width: 100%; }
    .backup-table { font-size: .82rem; }
    .backup-table thead th, .backup-table tbody td { padding: 10px 8px; }
}
@media (max-width: 480px) {
    .backup-metrics { grid-template-columns: 1fr; }
}
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent p-0 mb-2">
        <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
        <li class="breadcrumb-item active">Backup & Restore</li>
    </ol>
</nav>

<!-- Page Header -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
    <div>
        <h2 style="font-size:1.2rem;font-weight:700;color:var(--text);margin:0;display:flex;align-items:center;gap:10px;">
            <i class="fas fa-database" style="color:var(--primary);font-size:1.05rem;"></i> Backup & Restore
        </h2>
        <p style="font-size:.82rem;color:var(--text-muted);margin:4px 0 0;">Safely back up your system database and restore it anytime you need.</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <input type="text" id="backupNote" class="note-input" placeholder="Optional note (e.g. before update)" maxlength="200">
        <button class="btn-backup-create" id="btnCreateBackup" onclick="createBackup()">
            <i class="fas fa-cloud-arrow-up"></i> Create New Backup
        </button>
    </div>
</div>

<!-- Metrics Cards -->
<div class="backup-metrics" id="metricsArea">
    <div class="metric-card mc-primary">
        <div class="metric-icon"><i class="fas fa-clock"></i></div>
        <div class="metric-details">
            <div class="metric-label">Last Backup</div>
            <div class="metric-value" id="metricLastBackup"><span class="skeleton" style="width:120px;height:22px;"></span></div>
            <div class="metric-sub" id="metricLastBackupRelative">&nbsp;</div>
        </div>
    </div>
    <div class="metric-card mc-success">
        <div class="metric-icon"><i class="fas fa-layer-group"></i></div>
        <div class="metric-details">
            <div class="metric-label">Total Backups</div>
            <div class="metric-value" id="metricTotalBackups"><span class="skeleton" style="width:40px;height:22px;"></span></div>
            <div class="metric-sub">Backups stored on disk</div>
        </div>
    </div>
    <div class="metric-card mc-info">
        <div class="metric-icon"><i class="fas fa-hard-drive"></i></div>
        <div class="metric-details">
            <div class="metric-label">Database Size</div>
            <div class="metric-value" id="metricDbSize"><span class="skeleton" style="width:80px;height:22px;"></span></div>
            <div class="metric-sub">Current database size</div>
        </div>
    </div>
    <div class="metric-card mc-warning">
        <div class="metric-icon"><i class="fas fa-fingerprint"></i></div>
        <div class="metric-details">
            <div class="metric-label">Latest Checksum</div>
            <div class="metric-value" id="metricChecksum" style="font-size:1rem;font-family:'Courier New',monospace;"><span class="skeleton" style="width:140px;height:22px;"></span></div>
            <div class="metric-sub"><i class="fas fa-circle-check" style="color:#10b981;"></i> Verified</div>
        </div>
    </div>
</div>

<!-- Main Card with Tabs -->
<div class="card" style="border-radius:14px;">
    <div class="card-body">
        <div class="backup-tabs">
            <button class="backup-tab active" onclick="switchTab('stored')" id="tabStored">
                <i class="fas fa-folder-open me-1"></i> Stored Backups
            </button>
            <button class="backup-tab" onclick="switchTab('restore')" id="tabRestore">
                <i class="fas fa-rotate-left me-1"></i> Restore Database
            </button>
            <button class="backup-tab" onclick="switchTab('settings')" id="tabSettings">
                <i class="fas fa-sliders me-1"></i> Auto-Backup Settings
            </button>
        </div>

        <!-- 1. Stored Backups Panel -->
        <div id="panelStored" class="tab-panel active">
            <div class="restore-warning-banner info">
                <i class="fas fa-circle-info"></i>
                All backup files are safely saved on disk and remain accessible even after database updates or page navigation.
            </div>
            <div id="backupsTableContainer">
                <div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);opacity:.5;"></i></div>
            </div>
        </div>

        <!-- 2. Restore Database Panel -->
        <div id="panelRestore" class="tab-panel">
            <div class="restore-warning-banner warning">
                <i class="fas fa-triangle-exclamation"></i>
                <strong>Warning:</strong>&nbsp; Restoring a backup will <strong>replace all current database tables</strong> with the data in the selected backup. This action cannot be undone.
            </div>

            <div class="row g-4">
                <!-- Option 1: Select Stored Backup -->
                <div class="col-md-6">
                    <div class="restore-card h-100">
                        <div class="restore-card-header">
                            <i class="fas fa-server text-primary"></i> Option 1: Restore Stored Backup
                        </div>
                        <p class="text-muted" style="font-size:0.85rem;margin-bottom:16px;">Choose from your history of verified backups saved on the server.</p>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.83rem;">Select Backup File</label>
                            <select class="form-select" id="restoreBackupSelect" style="border-radius:10px;padding:10px 14px;">
                                <option value="">-- Loading backup files... --</option>
                            </select>
                        </div>

                        <div id="restoreBackupInfo" style="display:none;margin-bottom:16px;background:var(--card-bg);border:1px solid var(--border);border-radius:10px;padding:14px;font-size:.83rem;">
                        </div>

                        <button class="btn btn-warning w-100 fw-bold" id="btnRestoreSelect" onclick="restoreSelectedBackup()" disabled style="border-radius:10px;padding:10px;">
                            <i class="fas fa-rotate-left me-1"></i> Restore Selected Backup
                        </button>
                    </div>
                </div>

                <!-- Option 2: Upload .sql File -->
                <div class="col-md-6">
                    <div class="restore-card h-100">
                        <div class="restore-card-header">
                            <i class="fas fa-upload text-success"></i> Option 2: Upload & Restore .sql File
                        </div>
                        <p class="text-muted" style="font-size:0.85rem;margin-bottom:16px;">Select a <code>.sql</code> backup file from your computer to restore directly.</p>

                        <form id="uploadRestoreForm" enctype="multipart/form-data" onsubmit="return false;">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size:0.83rem;">Choose .sql Backup File</label>
                                <input type="file" class="form-control" id="restoreSqlFileInput" accept=".sql" style="border-radius:10px;padding:9px 12px;">
                            </div>

                            <button class="btn btn-danger w-100 fw-bold" id="btnRestoreUpload" onclick="restoreUploadedBackup()" style="border-radius:10px;padding:10px;margin-top:14px;">
                                <i class="fas fa-file-import me-1"></i> Upload & Restore Database
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Auto-Backup Settings Panel -->
        <div id="panelSettings" class="tab-panel">
            <div class="card p-4" style="border:1px solid var(--border);border-radius:12px;max-width:650px;margin:0 auto;background:var(--body-bg);">
                <h5 class="fw-bold mb-3" style="color:var(--text);"><i class="fas fa-robot text-primary me-2"></i> Automatic Scheduled Backups</h5>
                <p class="text-muted" style="font-size:0.88rem;margin-bottom:20px;">Configure automated periodic backups. When enabled, the system will automatically create a database snapshot at your chosen interval as users navigate the system.</p>

                <div class="form-check form-switch mb-4 d-flex align-items-center gap-3">
                    <input class="form-check-input ms-0" type="checkbox" id="autoBackupEnabledSwitch" style="font-size:1.2rem;">
                    <label class="form-check-label fw-bold cursor-pointer" for="autoBackupEnabledSwitch" style="color:var(--text);font-size:0.95rem;">
                        Enable Auto-Backup
                    </label>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold" style="color:var(--text);font-size:0.88rem;">Backup Frequency / Interval</label>
                    <select class="form-select" id="autoBackupIntervalSelect" style="border-radius:10px;padding:10px 14px;">
                        <option value="1">Every 1 Hour</option>
                        <option value="6">Every 6 Hours</option>
                        <option value="12">Every 12 Hours</option>
                        <option value="24" selected>Every 24 Hours (Daily)</option>
                        <option value="168">Every 7 Days (Weekly)</option>
                    </select>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top flex-wrap gap-2">
                    <span id="autoBackupStatusText" class="text-muted" style="font-size:0.83rem;">
                        Status: <strong class="text-danger">Disabled</strong>
                    </span>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-success fw-bold px-3" id="btnRunAutoNow" onclick="triggerAutoBackupNow()" style="border-radius:10px;font-size:0.88rem;">
                            <i class="fas fa-play me-1"></i> Generate Auto Backup Now
                        </button>
                        <button class="btn btn-primary fw-bold px-4" id="btnSaveAutoSettings" onclick="saveAutoBackupSettings()" style="border-radius:10px;font-size:0.88rem;">
                            <i class="fas fa-save me-1"></i> Save Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>



<!-- Toast Container -->
<div id="backupToast" class="backup-toast"></div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:1px solid var(--border);overflow:hidden;">
            <div class="modal-header" style="border-bottom:1px solid var(--border);padding:18px 22px;">
                <h5 class="modal-title" id="confirmModalTitle" style="font-weight:700;font-size:1rem;display:flex;align-items:center;gap:8px;"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmModalBody" style="padding:22px;font-size:.9rem;color:var(--text);">
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);padding:14px 22px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmModalAction" style="border-radius:8px;font-weight:700;">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script>
// ── State ────────────────────────────────────────────────────────────
let currentPage     = 1;
let perPage         = 10;
let totalBackups    = 0;
let allBackupsList  = [];

// ── Init ─────────────────────────────────────────────────────────────
$(document).ready(function() {
    loadMetrics();
    loadBackups();
    loadAllBackupsForRestore();
    loadAutoBackupSettings();

    // Dropdown selection change
    $('#restoreBackupSelect').on('change', function() {
        const id = $(this).val();
        const btn = $('#btnRestoreSelect');
        const info = $('#restoreBackupInfo');
        if (id) {
            btn.prop('disabled', false);
            const b = allBackupsList.find(x => x.id == id);
            if (b) {
                info.html(`
                    <strong><i class="fas fa-file-code me-1 text-primary"></i> ${escapeHtml(b.filename)}</strong><br>
                    <small class="text-muted">Created: ${b.created_at_formatted} &bull; Size: ${b.filesize_formatted}</small><br>
                    <small class="text-muted">Checksum: <code>${b.checksum_short}</code></small>
                    ${b.note ? '<br><small class="text-muted">Note: ' + escapeHtml(b.note) + '</small>' : ''}
                `).show();
            }
        } else {
            btn.prop('disabled', true);
            info.hide();
        }
    });

    // Auto backup switch status text toggle
    $('#autoBackupEnabledSwitch').on('change', function() {
        updateAutoBackupStatusText($(this).is(':checked'));
    });
});

// ── Load Metrics ─────────────────────────────────────────────────────
function loadMetrics() {
    $.getJSON('php_action/backupAction.php?action=metrics', function(resp) {
        if (resp.success && resp.metrics) {
            const m = resp.metrics;
            $('#metricLastBackup').text(m.last_backup);
            $('#metricLastBackupRelative').text(m.last_backup_relative);
            $('#metricTotalBackups').text(m.total_backups);
            $('#metricDbSize').text(m.database_size);
            $('#metricChecksum').text(m.latest_checksum);
        }
    });
}

// ── Load Backups List (Paginated Table) ──────────────────────────────
function loadBackups(page) {
    page = page || currentPage;
    currentPage = page;
    $('#backupsTableContainer').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);opacity:.5;"></i></div>');
    $.getJSON('php_action/backupAction.php?action=list&page=' + page + '&limit=' + perPage, function(resp) {
        if (resp.success) {
            totalBackups = resp.total;
            renderTable(resp);
        }
    });
}

// ── Load ALL Backups for Restore Dropdown ────────────────────────────
function loadAllBackupsForRestore() {
    $.getJSON('php_action/backupAction.php?action=list_all', function(resp) {
        if (resp.success && resp.backups) {
            allBackupsList = resp.backups;
            const sel = $('#restoreBackupSelect');
            sel.empty().append('<option value="">-- Select a backup file --</option>');
            if (allBackupsList.length === 0) {
                sel.append('<option value="" disabled>No backup files available</option>');
            } else {
                allBackupsList.forEach(function(b) {
                    sel.append(`<option value="${b.id}">${b.filename} (${b.created_at_formatted} — ${b.filesize_formatted})</option>`);
                });
            }
        }
    });
}

// ── Render Table ─────────────────────────────────────────────────────
function renderTable(data) {
    if (!data.backups || data.backups.length === 0) {
        $('#backupsTableContainer').html(`
            <div class="empty-state">
                <i class="fas fa-box-open d-block"></i>
                <p>No backups found on disk. Click <strong>"Create New Backup"</strong> to generate one.</p>
            </div>
        `);
        return;
    }

    let html = '<table class="backup-table"><thead><tr>';
    html += '<th>BACKUP NAME</th><th>CREATED AT</th><th>SIZE</th><th>CHECKSUM</th><th>STATUS</th><th>ACTIONS</th>';
    html += '</tr></thead><tbody>';

    data.backups.forEach(function(b) {
        html += `<tr id="backup-row-${b.id}">`;
        html += `<td>
                    <div class="backup-filename"><i class="fas fa-file-code"></i> ${escapeHtml(b.filename)}</div>
                    ${b.note ? '<div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;padding-left:24px;"><i class="fas fa-sticky-note me-1"></i>' + escapeHtml(b.note) + '</div>' : ''}
                 </td>`;
        html += `<td>
                    <div style="font-weight:600;">${b.created_at_formatted}</div>
                    <div style="font-size:.75rem;color:var(--text-muted);">${b.time_ago}</div>
                 </td>`;
        html += `<td style="font-weight:600;">${b.filesize_formatted}</td>`;
        html += `<td><span class="checksum-text" title="${escapeHtml(b.checksum)}" onclick="copyChecksum('${escapeHtml(b.checksum)}')">${b.checksum_short}</span></td>`;
        html += `<td><span class="backup-status verified"><i class="fas fa-circle-check"></i> ${escapeHtml(b.status || 'Verified')}</span></td>`;
        html += `<td>
                    <div class="backup-actions">
                        <button class="btn btn-outline-primary" title="Download" onclick="downloadBackup(${b.id})"><i class="fas fa-download"></i></button>
                        <button class="btn btn-outline-warning" title="Restore" onclick="confirmRestore(${b.id}, '${escapeHtml(b.filename)}')"><i class="fas fa-rotate-left"></i></button>
                        <button class="btn btn-outline-danger" title="Delete" onclick="confirmDelete(${b.id}, '${escapeHtml(b.filename)}')"><i class="fas fa-trash"></i></button>
                    </div>
                 </td>`;
        html += '</tr>';
    });

    html += '</tbody></table>';

    // Pagination
    const totalPages = data.pages || 1;
    const from = (data.page - 1) * data.limit + 1;
    const to   = Math.min(data.page * data.limit, data.total);
    html += `<div class="backup-pagination">
        <span class="page-info">Showing ${from} to ${to} of ${data.total} backups</span>
        <div class="page-btns">
            <button onclick="loadBackups(${data.page - 1})" ${data.page <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
    for (let i = 1; i <= totalPages; i++) {
        html += `<button onclick="loadBackups(${i})" class="${i === data.page ? 'active-page' : ''}">${i}</button>`;
    }
    html += `<button onclick="loadBackups(${data.page + 1})" ${data.page >= totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>`;

    $('#backupsTableContainer').html(html);
}

// ── Create Backup ────────────────────────────────────────────────────
function createBackup() {
    const btn = $('#btnCreateBackup');
    const note = $('#backupNote').val().trim();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');

    $.post('php_action/backupAction.php?action=create', { note: note }, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-cloud-arrow-up"></i> Create New Backup');
        if (resp.success) {
            showToast('success', '<i class="fas fa-check-circle"></i> ' + resp.message);
            $('#backupNote').val('');
            loadMetrics();
            loadBackups(1);
            loadAllBackupsForRestore();
        } else {
            showToast('error', '<i class="fas fa-times-circle"></i> ' + resp.message);
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-cloud-arrow-up"></i> Create New Backup');
        showToast('error', '<i class="fas fa-times-circle"></i> Server error creating backup.');
    });
}

// ── Load Auto Backup Settings ────────────────────────────────────────
function loadAutoBackupSettings() {
    $.getJSON('php_action/backupAction.php?action=get_auto_settings', function(resp) {
        if (resp.success && resp.settings) {
            const s = resp.settings;
            $('#autoBackupEnabledSwitch').prop('checked', s.auto_backup_enabled);
            $('#autoBackupIntervalSelect').val(s.auto_backup_interval);
            updateAutoBackupStatusText(s.auto_backup_enabled);
        }
    });
}

// ── Save Auto Backup Settings ────────────────────────────────────────
function saveAutoBackupSettings() {
    const btn = $('#btnSaveAutoSettings');
    const enabled = $('#autoBackupEnabledSwitch').is(':checked') ? '1' : '0';
    const interval = $('#autoBackupIntervalSelect').val();

    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    $.post('php_action/backupAction.php?action=save_auto_settings', {
        enabled: enabled,
        interval: interval
    }, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Settings');
        if (resp.success) {
            showToast('success', '<i class="fas fa-check-circle"></i> ' + resp.message);
            updateAutoBackupStatusText(enabled === '1');
            loadMetrics();
            loadBackups(1);
            loadAllBackupsForRestore();
        } else {
            showToast('error', '<i class="fas fa-times-circle"></i> ' + resp.message);
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Settings');
        showToast('error', '<i class="fas fa-times-circle"></i> Failed to save settings.');
    });
}

function triggerAutoBackupNow() {
    const btn = $('#btnRunAutoNow');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generating...');
    $.post('php_action/backupAction.php?action=run_auto_backup', function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-play me-1"></i> Generate Auto Backup Now');
        if (resp.success) {
            showToast('success', '<i class="fas fa-check-circle me-1"></i> Auto-backup created successfully! Saved in Stored Backups.');
            loadMetrics();
            loadBackups(1);
            loadAllBackupsForRestore();
        } else {
            showToast('error', '<i class="fas fa-times-circle me-1"></i> ' + resp.message);
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-play me-1"></i> Generate Auto Backup Now');
        showToast('error', '<i class="fas fa-times-circle me-1"></i> Server error generating auto-backup.');
    });
}

function updateAutoBackupStatusText(enabled) {
    if (enabled) {
        $('#autoBackupStatusText').html('Status: <strong class="text-success"><i class="fas fa-circle-check me-1"></i> Active (Auto-Saving On)</strong>');
    } else {
        $('#autoBackupStatusText').html('Status: <strong class="text-danger"><i class="fas fa-circle-xmark me-1"></i> Disabled</strong>');
    }
}

// ── Download ─────────────────────────────────────────────────────────
function downloadBackup(id) {
    window.location.href = 'php_action/backupAction.php?action=download&id=' + id;
}

// ── Confirm Delete ───────────────────────────────────────────────────
function confirmDelete(id, filename) {
    $('#confirmModalTitle').html('<i class="fas fa-trash text-danger"></i> Delete Backup File');
    $('#confirmModalBody').html(`
        <p>Are you sure you want to delete this backup file from disk?</p>
        <div style="background:var(--body-bg);border-radius:8px;padding:12px;font-size:.85rem;">
            <strong>${escapeHtml(filename)}</strong>
        </div>
        <p class="text-danger mt-2 mb-0" style="font-size:.82rem;"><i class="fas fa-exclamation-triangle me-1"></i> This action cannot be undone.</p>
    `);
    $('#confirmModalAction').off('click').on('click', function() {
        deleteBackup(id);
    }).text('Delete File').removeClass('btn-warning').addClass('btn-danger');
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

function deleteBackup(id) {
    bootstrap.Modal.getInstance(document.getElementById('confirmModal'))?.hide();
    $.post('php_action/backupAction.php?action=delete', { backup_id: id }, function(resp) {
        if (resp.success) {
            showToast('success', '<i class="fas fa-check-circle"></i> ' + resp.message);
            loadMetrics();
            loadBackups();
            loadAllBackupsForRestore();
        } else {
            showToast('error', '<i class="fas fa-times-circle"></i> ' + resp.message);
        }
    }, 'json');
}

// ── Confirm Restore (from Table Row or Option 1) ──────────────────────
function confirmRestore(id, filename) {
    $('#confirmModalTitle').html('<i class="fas fa-rotate-left text-warning"></i> Restore Database');
    $('#confirmModalBody').html(`
        <p>Are you sure you want to restore the database from this backup file?</p>
        <div style="background:var(--body-bg);border-radius:8px;padding:12px;font-size:.85rem;">
            <strong>${escapeHtml(filename)}</strong>
        </div>
        <p class="text-danger mt-2 mb-0" style="font-size:.82rem;"><i class="fas fa-exclamation-triangle me-1"></i> This will <strong>replace all current database tables</strong> and cannot be undone.</p>
    `);
    $('#confirmModalAction').off('click').on('click', function() {
        doRestore(id);
    }).text('Restore Now').removeClass('btn-danger').addClass('btn-warning');
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

function restoreSelectedBackup() {
    const id = $('#restoreBackupSelect').val();
    if (!id) return;
    const item = allBackupsList.find(x => x.id == id);
    const filename = item ? item.filename : 'Selected Backup';
    confirmRestore(id, filename);
}

function doRestore(id) {
    bootstrap.Modal.getInstance(document.getElementById('confirmModal'))?.hide();
    showToast('info', '<i class="fas fa-spinner fa-spin me-1"></i> Restoring database... Please wait.');
    $.post('php_action/backupAction.php?action=restore', { backup_id: id }, function(resp) {
        if (resp.success) {
            showToast('success', '<i class="fas fa-check-circle me-1"></i> ' + resp.message);
            loadMetrics();
            loadBackups(1);
            loadAllBackupsForRestore();
        } else {
            showToast('error', '<i class="fas fa-times-circle me-1"></i> ' + resp.message);
        }
    }, 'json').fail(function() {
        showToast('error', '<i class="fas fa-times-circle me-1"></i> Restore request encountered a server error.');
    });
}

// ── Restore Uploaded .sql File ────────────────────────────────────────
function restoreUploadedBackup() {
    const fileInput = document.getElementById('restoreSqlFileInput');
    if (!fileInput.files || fileInput.files.length === 0) {
        showToast('error', '<i class="fas fa-exclamation-circle me-1"></i> Please choose a .sql backup file to upload.');
        return;
    }

    const file = fileInput.files[0];
    if (!file.name.toLowerCase().endsWith('.sql')) {
        showToast('error', '<i class="fas fa-exclamation-circle me-1"></i> Only .sql files are allowed.');
        return;
    }

    $('#confirmModalTitle').html('<i class="fas fa-file-import text-danger"></i> Upload & Restore Database');
    $('#confirmModalBody').html(`
        <p>Are you sure you want to upload and restore this SQL file?</p>
        <div style="background:var(--body-bg);border-radius:8px;padding:12px;font-size:.85rem;">
            <strong><i class="fas fa-file-code text-primary me-1"></i> ${escapeHtml(file.name)}</strong> (${(file.size / 1024).toFixed(1)} KB)
        </div>
        <p class="text-danger mt-2 mb-0" style="font-size:.82rem;"><i class="fas fa-exclamation-triangle me-1"></i> This will <strong>replace all current database tables</strong> with the contents of this uploaded file.</p>
    `);
    $('#confirmModalAction').off('click').on('click', function() {
        doRestoreUploaded();
    }).text('Upload & Restore Now').removeClass('btn-warning').addClass('btn-danger');
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

function doRestoreUploaded() {
    bootstrap.Modal.getInstance(document.getElementById('confirmModal'))?.hide();
    const fileInput = document.getElementById('restoreSqlFileInput');
    if (!fileInput.files || fileInput.files.length === 0) return;

    const formData = new FormData();
    formData.append('sql_file', fileInput.files[0]);

    showToast('info', '<i class="fas fa-spinner fa-spin me-1"></i> Uploading & restoring database... Please wait.');

    $.ajax({
        url: 'php_action/backupAction.php?action=restore_upload',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                showToast('success', '<i class="fas fa-check-circle me-1"></i> ' + resp.message);
                $('#uploadRestoreForm')[0].reset();
                loadMetrics();
                loadBackups(1);
                loadAllBackupsForRestore();
            } else {
                showToast('error', '<i class="fas fa-times-circle me-1"></i> ' + resp.message);
            }
        },
        error: function() {
            showToast('error', '<i class="fas fa-times-circle me-1"></i> Server error processing uploaded SQL file.');
        }
    });
}

// ── Copy Checksum ────────────────────────────────────────────────────
function copyChecksum(cs) {
    navigator.clipboard.writeText(cs).then(function() {
        showToast('success', '<i class="fas fa-clipboard-check"></i> Checksum copied to clipboard!');
    });
}

// ── Tab Switching ────────────────────────────────────────────────────
function switchTab(tab) {
    $('.backup-tab').removeClass('active');
    $('.tab-panel').removeClass('active');

    if (tab === 'stored') {
        $('#tabStored').addClass('active');
        $('#panelStored').addClass('active');
    } else if (tab === 'restore') {
        $('#tabRestore').addClass('active');
        $('#panelRestore').addClass('active');
        loadAllBackupsForRestore(); // ensure dropdown has latest list
    } else if (tab === 'settings') {
        $('#tabSettings').addClass('active');
        $('#panelSettings').addClass('active');
    }
}

// ── Toast Notification ───────────────────────────────────────────────
function showToast(type, message) {
    const t = $('#backupToast');
    t.removeClass('show success error info').addClass(type).html(message);
    setTimeout(function() { t.addClass('show'); }, 50);
    setTimeout(function() { t.removeClass('show'); }, 4500);
}

// ── Escape HTML helper ──────────────────────────────────────────────
function escapeHtml(text) {
    if (!text) return '';
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}
</script>
