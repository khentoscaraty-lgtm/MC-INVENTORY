<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('stock_forecast');
require_once 'includes/header_sidebar.php';
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

    /* Tab styling */
    .nav-tabs .nav-link {
        color: var(--text-muted);
        border: none;
        border-bottom: 2px solid transparent;
        padding: 10px 20px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: var(--transition);
    }
    .nav-tabs .nav-link:hover {
        color: var(--primary);
        border-bottom-color: var(--primary);
        background: transparent;
    }
    .nav-tabs .nav-link.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        background: transparent;
    }
    .tab-content { padding-top: 1.25rem; }

    /* Summary cards */
    .summary-card {
        border-radius: 12px;
        border: 1px solid var(--border-light);
        background: var(--card-bg);
        transition: var(--transition);
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .summary-card.clickable-card {
        cursor: pointer;
        user-select: none;
    }
    .summary-card.clickable-card:hover {
        box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    }
    .summary-card.clickable-card.active-filter-danger {
        box-shadow: 0 0 0 2px var(--danger), 0 6px 16px rgba(198,40,40,0.2);
        background: rgba(198,40,40,0.04);
    }
    .summary-card.clickable-card.active-filter-warning {
        box-shadow: 0 0 0 2px #e65100, 0 6px 16px rgba(230,81,0,0.2);
        background: rgba(230,81,0,0.04);
    }
    .summary-card.clickable-card.active-filter-info {
        box-shadow: 0 0 0 2px var(--warning), 0 6px 16px rgba(255,193,7,0.2);
        background: rgba(255,193,7,0.04);
    }
    .batch-expired-row {
        background: rgba(198, 40, 40, 0.08) !important;
    }
    .batch-expired-row td:first-child {
        border-left: 3px solid var(--danger);
    }

    /* Table styling */
    .table { color: var(--text); font-size: 0.85rem; }
    .table thead th {
        background: var(--input-bg);
        color: var(--text-muted);
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 1px solid var(--border-light);
        padding: 10px 12px;
    }
    .table tbody td {
        padding: 10px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-light);
    }
    .table tbody tr:hover { background: var(--hover-bg); }

    /* Breadcrumb */
    .breadcrumb-nav {
        font-size: 0.82rem;
        color: var(--text-muted);
        margin-bottom: 0.75rem;
    }
    .breadcrumb-nav a { color: var(--primary); text-decoration: none; }
    .breadcrumb-nav a:hover { text-decoration: underline; }

    /* Chart container */
    #trendChart { min-height: 250px; }

    /* DataTables overrides */
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 0.85rem;
        background: var(--input-bg);
        color: var(--text);
    }
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 4px 8px;
        background: var(--input-bg);
        color: var(--text);
    }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
        <li class="breadcrumb-item active">Stock Forecast</li>
    </ol>
</nav>

<div class="page-header-section">
    <h2><i class="fas fa-chart-line"></i> Stock Forecast & Expiry Management</h2>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#forecastTab" type="button" role="tab">
            <i class="fas fa-chart-bar me-1"></i> Forecast
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#expiryTab" type="button" role="tab">
            <i class="fas fa-clock me-1"></i> Expiry Tracker
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- ═══════════ FORECAST TAB ═══════════ -->
    <div class="tab-pane fade show active" id="forecastTab" role="tabpanel">

        <!-- Status Summary Cards -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="summary-card p-3 text-center" style="border-left: 4px solid var(--success);">
                    <div class="h3 mb-1" id="safeCount" style="color: var(--success); font-weight:700;">0</div>
                    <div style="color: var(--text-muted); font-size: 0.8rem; font-weight:600;">Safe (&gt;21 days)</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card p-3 text-center" style="border-left: 4px solid var(--warning);">
                    <div class="h3 mb-1" id="reorderCount" style="color: var(--warning); font-weight:700;">0</div>
                    <div style="color: var(--text-muted); font-size: 0.8rem; font-weight:600;">Reorder Soon (&le;21 days)</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card p-3 text-center" style="border-left: 4px solid var(--danger);">
                    <div class="h3 mb-1" id="urgentCount" style="color: var(--danger); font-weight:700;">0</div>
                    <div style="color: var(--text-muted); font-size: 0.8rem; font-weight:600;">Urgent (&le;7 days)</div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="d-flex justify-content-end mb-2">
            <select id="productTypeFilter" class="form-select" style="width: 200px; font-size: 0.85rem;">
                <option value="">All Product Types</option>
            </select>
        </div>

        <!-- Forecast Table -->
        <div class="card" style="border-radius:12px; border:1px solid var(--border-light); background:var(--card-bg);">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="forecastTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col">Current Stock</th>
                                <th scope="col">Avg/Day</th>
                                <th scope="col">Days Left</th>
                                <th scope="col">Reorder Needed</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody id="forecastTableBody">
                            <tr><td colspan="6" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Trend Chart -->
        <div class="card mt-3" style="border-radius:12px; border:1px solid var(--border-light); background:var(--card-bg);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 style="font-weight: 700; color: var(--text); margin:0;">
                        <i class="fas fa-chart-area me-1" style="color:var(--primary);"></i> Stock Level Trend
                    </h6>
                    <select id="trendProduct" class="form-select" style="width: 250px; font-size: 0.85rem;">
                        <option value="">Select Product...</option>
                    </select>
                </div>
                <div style="position:relative; height:280px;">
                    <canvas id="trendChart"></canvas>
                </div>
                <div id="trendPlaceholder" class="text-center py-4" style="color:var(--text-muted); font-size:0.85rem;">
                    <i class="fas fa-chart-line fa-2x mb-2" style="opacity:0.3;"></i><br>
                    Select a product above to view stock trend and projections
                </div>
            </div>
        </div>

        <!-- Reorder Suggestions -->
        <div class="card mt-3" style="border-radius:12px; border:1px solid var(--border-light); background:var(--card-bg);">
            <div class="card-body">
                <h6 style="font-weight: 700; color: var(--text); margin-bottom:1rem;">
                    <i class="fas fa-lightbulb me-1" style="color: var(--primary);"></i> Reorder Suggestions
                </h6>
                <div class="table-responsive">
                    <table class="table" id="reorderTable">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col">Suggested Qty</th>
                                <th scope="col">Best Supplier</th>
                                <th scope="col">Est. Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- populated by JS -->
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-success mt-2" onclick="createPOFromSuggestions()" style="font-size:0.85rem;">
                    <i class="fas fa-file-invoice me-1"></i> Create Purchase Order
                </button>
            </div>
        </div>

    </div><!-- /forecastTab -->

    <!-- ═══════════ EXPIRY TAB ═══════════ -->
    <div class="tab-pane fade" id="expiryTab" role="tabpanel">

        <!-- Expiry Summary Cards -->
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="summary-card clickable-card p-3 text-center expiry-summary-card" id="cardFilterExpired" style="border-left: 4px solid var(--danger);" title="Click to filter expired batches" role="button">
                    <div class="h3 mb-1" id="expiredCount" style="color: var(--danger); font-weight:700;">0</div>
                    <div style="color: var(--text-muted); font-size: 0.8rem; font-weight:600;">Expired <i class="fas fa-filter ms-1" style="font-size:0.7rem;opacity:0.5;"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card clickable-card p-3 text-center expiry-summary-card" id="cardFilterWeek" style="border-left: 4px solid #e65100;" title="Click to filter batches expiring this week" role="button">
                    <div class="h3 mb-1" id="expiringWeekCount" style="color: #e65100; font-weight:700;">0</div>
                    <div style="color: var(--text-muted); font-size: 0.8rem; font-weight:600;">Expiring This Week <i class="fas fa-filter ms-1" style="font-size:0.7rem;opacity:0.5;"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card clickable-card p-3 text-center expiry-summary-card" id="cardFilterMonth" style="border-left: 4px solid var(--warning);" title="Click to filter batches expiring this month" role="button">
                    <div class="h3 mb-1" id="expiringMonthCount" style="color: var(--warning); font-weight:700;">0</div>
                    <div style="color: var(--text-muted); font-size: 0.8rem; font-weight:600;">Expiring This Month <i class="fas fa-filter ms-1" style="font-size:0.7rem;opacity:0.5;"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card clickable-card p-3 text-center expiry-summary-card" id="cardFilterAll" style="border-left: 4px solid var(--primary);" title="Click to view all batches" role="button">
                    <div style="font-size: 0.78rem; color: var(--text-muted); font-weight:600; margin-bottom:4px;">Total At-Risk Value</div>
                    <div class="h4 mb-0" id="atRiskValue" style="color: var(--primary); font-weight:700;">₱0.00</div>
                </div>
            </div>
        </div>

        <!-- Filter bar above expiry table -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="btn-group btn-group-sm" role="group" id="expiryFilterBtnGroup" aria-label="Expiry Filters">
                <button type="button" class="btn btn-outline-secondary active" data-expiry-filter="all">All Batches</button>
                <button type="button" class="btn btn-outline-danger" data-expiry-filter="expired"><i class="fas fa-exclamation-triangle me-1"></i>Expired Only</button>
                <button type="button" class="btn btn-outline-warning" data-expiry-filter="expiring_week"><i class="fas fa-clock me-1"></i>Expiring This Week</button>
                <button type="button" class="btn btn-outline-info" data-expiry-filter="expiring_month"><i class="fas fa-calendar-alt me-1"></i>Expiring This Month</button>
            </div>
            <div id="expiryActiveFilterBadge" style="display:none;">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size:0.8rem;">
                    <span id="expiryFilterLabel">Filtered: Expired Only</span>
                    <button type="button" class="btn-close ms-2" style="font-size:0.55rem; vertical-align:middle;" id="clearExpiryFilterBtn" aria-label="Clear filter"></button>
                </span>
            </div>
        </div>

        <!-- Expiry Table -->
        <div class="card" style="border-radius:12px; border:1px solid var(--border-light); background:var(--card-bg);">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="expiryTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col">Batch #</th>
                                <th scope="col">Qty Remaining</th>
                                <th scope="col">Expiry Date</th>
                                <th scope="col">Days Left</th>
                                <th scope="col">Value</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody id="expiryTableBody">
                            <tr><td colspan="7" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /expiryTab -->

</div><!-- /tab-content -->

<?php require_once 'includes/footer_sidebar.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="custom/js/stock_forecast.js?v=<?= time() ?>"></script>
<script>
$('#trendProduct').on('change', function() {
    if ($(this).val()) { $('#trendPlaceholder').hide(); }
    else { $('#trendPlaceholder').show(); }
});
</script>
