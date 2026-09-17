<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('reports');
require_once 'includes/header_sidebar.php';
?>

<?php
global $connect;

// ── Summary stats for top report cards (Orders + Transactions minus Returns) ──
$stmt = $connect->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(paid),0) AS rev FROM orders WHERE order_status = 1");
$stmt->execute();
$orderStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $connect->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(total_payable),0) AS rev FROM transactions WHERE transaction_status = 1");
$stmt->execute();
$txStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $connect->prepare("SELECT COALESCE(SUM(refund_amount),0) AS rev FROM sales_returns WHERE status = 'completed'");
$stmt->execute();
$retStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalCount = intval($orderStats['c'] ?? 0) + intval($txStats['c'] ?? 0);
$orderPaid = floatval($orderStats['rev'] ?? 0);
$txNet = max(0, floatval($txStats['rev'] ?? 0) - floatval($retStats['rev'] ?? 0));
$totalRevenue = $orderPaid + $txNet;

$stmt = $connect->prepare("SELECT COUNT(*) AS c FROM product WHERE status = 1");
$stmt->execute();
$prodCount = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$stmt = $connect->prepare("SELECT COUNT(*) AS c FROM product WHERE status = 1 AND (quantity <= COALESCE(reorder_level, 3) OR quantity <= 3)");
$stmt->execute();
$lowCount = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// Load store logo as base64 for PDF & Print reports
$reportLogoBase64 = '';
$reportLogoPath = __DIR__ . '/logo_round_thumb.png';
if (!file_exists($reportLogoPath)) {
    $reportLogoPath = __DIR__ . '/logo_round.png';
}
if (!file_exists($reportLogoPath)) {
    $reportLogoPath = __DIR__ . '/assests/images/logo_round.png';
}
if (!file_exists($reportLogoPath)) {
    $reportLogoPath = __DIR__ . '/logo.png';
}
if (file_exists($reportLogoPath)) {
    $reportLogoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($reportLogoPath));
}
?>

<style>
/* ── Report Page Styles ───────────────────────────────────────── */
:root {
    --primary-orange: #ea580c;
    --primary-orange-light: #fff7ed;
    --primary-orange-dark: #c2410c;
    --dark-navy: #0f172a;
    --slate-sidebar: #1e293b;
}

.rpt-nav { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 1.25rem; }
.rpt-tab {
    padding: .6rem 1.15rem; border-radius: 10px; font-size: .85rem; font-weight: 700;
    border: 1.5px solid var(--border); background: var(--card-bg); color: var(--text-muted);
    cursor: pointer; transition: all .2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px;
}
.rpt-tab:hover { border-color: var(--primary-orange); color: var(--primary-orange); transform: translateY(-1px); }
.rpt-tab.active { background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%); color: #fff; border-color: #ea580c; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25); }
.rpt-tab i { font-size: .85rem; }

.rpt-section { display: none; }
.rpt-section.active { display: block; }

.rpt-quick-ranges { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: .8rem; }
.rpt-quick-btn {
    padding: .35rem .85rem; border-radius: 20px; font-size: .78rem; font-weight: 600;
    border: 1.5px solid var(--border); background: var(--card-bg); color: var(--text-muted);
    cursor: pointer; transition: all .15s;
}
.rpt-quick-btn:hover { border-color: var(--primary-orange); color: var(--primary-orange); }
.rpt-quick-btn.active { background: #ea580c; color: #fff; border-color: #ea580c; }

.rpt-filters {
    display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;
    margin-bottom: 1.25rem; padding: 1.25rem; background: var(--card-bg);
    border-radius: 12px; border: 1.5px solid var(--border);
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}
.rpt-filters .fg { display: flex; flex-direction: column; gap: 6px; }
.rpt-filters label { font-size: .78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em; }
.rpt-filters input, .rpt-filters select {
    border: 1.5px solid var(--border); border-radius: 8px; padding: .5rem .8rem;
    font-size: .88rem; min-width: 170px; font-weight: 500;
}
.rpt-filters input:focus, .rpt-filters select:focus { border-color: #ea580c; outline: none; box-shadow: 0 0 0 3px rgba(234,88,12,.12); }

.rpt-actions { display: flex; gap: 8px; margin-left: auto; align-items: flex-end; }
.rpt-btn {
    padding: .5rem 1rem; border-radius: 8px; font-size: .82rem; font-weight: 700;
    border: none; cursor: pointer; transition: all .2s;
    display: inline-flex; align-items: center; gap: 6px;
}
.rpt-btn-gen { background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%); color: #fff; box-shadow: 0 2px 6px rgba(234,88,12,0.25); }
.rpt-btn-gen:hover { background: #c2410c; transform: translateY(-1px); }
.rpt-btn-print { background: #1e293b; color: #fff; }
.rpt-btn-print:hover { background: #0f172a; }
.rpt-btn-pdf { background: #dc2626; color: #fff; box-shadow: 0 2px 6px rgba(220,38,38,0.25); }
.rpt-btn-pdf:hover { background: #b91c1c; transform: translateY(-1px); }
.rpt-btn-csv { background: #059669; color: #fff; }
.rpt-btn-csv:hover { background: #047857; }
.rpt-btn:disabled { opacity: .45; cursor: not-allowed; transform: none !important; }

/* Summary cards */
.rpt-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 1.25rem; }
.rpt-sum-card {
    background: var(--card-bg); border: 1.5px solid var(--border);
    border-radius: 12px; padding: 1rem 1.25rem; text-align: left;
    position: relative; overflow: hidden; transition: transform .2s, box-shadow .2s;
    display: flex; align-items: center; justify-content: space-between;
}
.rpt-sum-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.06); }
.rpt-sum-card .card-info { z-index: 1; }
.rpt-sum-card .val { font-size: 1.45rem; font-weight: 800; color: var(--text); line-height: 1.2; }
.rpt-sum-card .lbl { font-size: .75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }
.rpt-sum-card .card-icon {
    width: 46px; height: 46px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; opacity: 0.9;
}
.card-icon-sales { background: #fff7ed; color: #ea580c; }
.card-icon-rev { background: #ecfdf5; color: #059669; }
.card-icon-prod { background: #eff6ff; color: #2563eb; }
.card-icon-low { background: #fef2f2; color: #dc2626; }

/* Dynamic Sales Report Header Banner */
.sales-report-banner {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #fff; border-radius: 12px; padding: 1.25rem 1.5rem;
    margin-bottom: 1.25rem; border-left: 5px solid #ea580c;
    display: flex; justify-content: space-between; align-items: center; flex-wrap: gap; gap: 12px;
}
.banner-title h3 { font-size: 1.25rem; font-weight: 800; margin: 0 0 4px 0; letter-spacing: 0.5px; color: #fff; }
.banner-title p { font-size: .85rem; color: #cbd5e1; margin: 0; }
.banner-badge {
    background: rgba(234, 88, 12, 0.2); border: 1px solid rgba(234, 88, 12, 0.5);
    padding: 6px 14px; border-radius: 20px; font-size: .8rem; font-weight: 700; color: #fb923c;
    display: inline-flex; align-items: center; gap: 6px;
}

/* 2-Column Analytics Overview */
.report-analytics-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1.25rem;
}
.analytics-card {
    background: var(--card-bg); border: 1.5px solid var(--border);
    border-radius: 12px; padding: 1.15rem;
}
.analytics-card-title {
    font-size: .85rem; font-weight: 800; color: var(--text); text-transform: uppercase;
    letter-spacing: .05em; margin-bottom: .85rem; display: flex; align-items: center; gap: 6px;
}
.fin-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 6px 0; border-bottom: 1px dashed var(--border); font-size: .85rem;
}
.fin-row:last-child { border-bottom: none; }
.fin-row.total-row {
    border-top: 2px solid #ea580c; border-bottom: 2px solid #ea580c;
    padding: 10px 0; margin-top: 6px; font-size: 1.05rem; font-weight: 900; color: #ea580c;
}
.pay-pill-list { display: flex; flex-direction: column; gap: 8px; }
.pay-pill-item {
    display: flex; justify-content: space-between; align-items: center;
    background: var(--border-lightest); padding: 8px 12px; border-radius: 8px;
    font-size: .85rem; font-weight: 600;
}
.pay-pill-item .badge { font-size: .75rem; padding: 4px 8px; }

/* Report table */
.rpt-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.rpt-table th {
    background: #1e293b; color: #fff; font-size: .74rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    padding: .75rem .65rem; border-bottom: 2px solid #0f172a; text-align: left;
}
.rpt-table td { padding: .65rem .65rem; border-bottom: 1px solid var(--border-lightest); color: var(--text); vertical-align: middle; }
.rpt-table tbody tr:hover td { background: rgba(234, 88, 12, 0.03); }
.rpt-table tfoot td {
    font-weight: 800; background: #f8fafc; border-top: 2px solid var(--border);
    padding: .85rem .65rem; color: var(--text); font-size: .88rem;
}

/* Empty preview */
.rpt-preview-empty {
    text-align: center; padding: 3.5rem 1rem; color: #94a3b8;
}
.rpt-preview-empty i { font-size: 2.75rem; display: block; margin-bottom: .75rem; color: #cbd5e1; }
.rpt-preview-empty p { font-size: .92rem; margin: 0; }

/* Dark Mode overrides for Report page */
[data-theme="dark"] .card-icon-sales { background: rgba(234, 88, 12, 0.2); color: #fb923c; }
[data-theme="dark"] .card-icon-rev { background: rgba(5, 150, 105, 0.2); color: #34d399; }
[data-theme="dark"] .card-icon-prod { background: rgba(37, 99, 235, 0.2); color: #60a5fa; }
[data-theme="dark"] .card-icon-low { background: rgba(220, 38, 38, 0.2); color: #f87171; }
[data-theme="dark"] .rpt-table tfoot td { background: var(--input-bg); border-color: var(--border); color: var(--text); }
[data-theme="dark"] .pay-pill-item { background: var(--input-bg); color: var(--text); }
[data-theme="dark"] .rpt-filters { background: var(--card-bg); border-color: var(--border); }
[data-theme="dark"] .rpt-filters input, [data-theme="dark"] .rpt-filters select { background: var(--input-bg); color: var(--text); border-color: var(--border); }
[data-theme="dark"] .analytics-card { background: var(--card-bg); border-color: var(--border); }
[data-theme="dark"] .fin-row { border-color: var(--border); }
[data-theme="dark"] .rpt-sum-card { background: var(--card-bg); border-color: var(--border); }
[data-theme="dark"] .rpt-sum-card .val { color: var(--text); }
[data-theme="dark"] .rpt-preview-empty { color: #8A7A6C; }
[data-theme="dark"] .rpt-preview-empty i { color: #5A4A3C; }

@media (max-width: 768px) {
    .report-analytics-grid { grid-template-columns: 1fr; }
    .rpt-filters { flex-direction: column; }
    .rpt-filters .fg { width: 100%; }
    .rpt-filters input, .rpt-filters select { min-width: 100%; }
    .rpt-actions { margin-left: 0; width: 100%; justify-content: stretch; }
    .rpt-actions .rpt-btn { flex: 1; justify-content: center; }
}
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent p-0 mb-2">
        <li class="breadcrumb-item"><a href="dashboard_secure.php" style="color:#ea580c;text-decoration:none;">Home</a></li>
        <li class="breadcrumb-item active">Reports</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h2 style="font-size:1.25rem;font-weight:800;color:var(--text);margin:0;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-chart-line" style="color:#ea580c;"></i> Reports &amp; Analytics Center
    </h2>
</div>

<!-- Top Summary Cards -->
<div class="rpt-summary no-print">
    <div class="rpt-sum-card">
        <div class="card-info">
            <div class="val"><?= number_format($totalCount) ?></div>
            <div class="lbl">All-Time Sales &amp; Orders</div>
        </div>
        <div class="card-icon card-icon-sales"><i class="fas fa-shopping-cart"></i></div>
    </div>
    <div class="rpt-sum-card">
        <div class="card-info">
            <div class="val">₱ <?= number_format($totalRevenue, 2) ?></div>
            <div class="lbl">All-Time Net Revenue</div>
        </div>
        <div class="card-icon card-icon-rev"><i class="fas fa-wallet"></i></div>
    </div>
    <div class="rpt-sum-card">
        <div class="card-info">
            <div class="val"><?= number_format($prodCount) ?></div>
            <div class="lbl">Total Products</div>
        </div>
        <div class="card-icon card-icon-prod"><i class="fas fa-boxes-stacked"></i></div>
    </div>
    <div class="rpt-sum-card" id="lowStockSumCard" style="cursor:pointer;" title="Click to view low-stock items in Inventory Report">
        <div class="card-info">
            <div class="val" style="color:<?= $lowCount > 0 ? '#dc2626' : '#059669' ?>;"><?= number_format($lowCount) ?></div>
            <div class="lbl">Low Stock Alert</div>
        </div>
        <div class="card-icon card-icon-low"><i class="fas fa-triangle-exclamation"></i></div>
    </div>
</div>

<!-- Report Type Tabs -->
<div class="rpt-nav no-print">
    <button class="rpt-tab active" data-target="salesReport"><i class="fas fa-receipt"></i> Sales Report</button>
    <button class="rpt-tab" data-target="inventoryReport"><i class="fas fa-warehouse"></i> Inventory Report</button>
    <button class="rpt-tab" data-target="productReport"><i class="fas fa-chart-pie"></i> Product Sales</button>
</div>

<!-- ═══ 1. SALES REPORT ═══ -->
<div class="rpt-section active" id="salesReport">
    <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <div class="card-body p-4">
            <form id="salesReportForm" class="no-print">
                <div class="rpt-quick-ranges" data-start="#salesStartDate" data-end="#salesEndDate">
                    <button type="button" class="rpt-quick-btn" data-range="today">Today</button>
                    <button type="button" class="rpt-quick-btn" data-range="week">This Week</button>
                    <button type="button" class="rpt-quick-btn active" data-range="month">This Month</button>
                    <button type="button" class="rpt-quick-btn" data-range="year">This Year</button>
                </div>
                <div class="rpt-filters">
                    <div class="fg">
                        <label><i class="fas fa-calendar-alt me-1 text-primary"></i> Start Date</label>
                        <input type="text" id="salesStartDate" placeholder="mm/dd/yyyy" autocomplete="off">
                    </div>
                    <div class="fg">
                        <label><i class="fas fa-calendar-alt me-1 text-primary"></i> End Date</label>
                        <input type="text" id="salesEndDate" placeholder="mm/dd/yyyy" autocomplete="off">
                    </div>
                    <div class="rpt-actions">
                        <button type="submit" class="rpt-btn rpt-btn-gen"><i class="fas fa-magnifying-glass"></i> Generate Report</button>
                        <button type="button" class="rpt-btn rpt-btn-pdf" id="pdfSalesBtn" disabled><i class="fas fa-file-pdf"></i> Export PDF</button>
                        <button type="button" class="rpt-btn rpt-btn-print" id="printSalesBtn" disabled><i class="fas fa-print"></i> Print</button>
                        <button type="button" class="rpt-btn rpt-btn-csv" id="csvSalesBtn" disabled><i class="fas fa-file-csv"></i> CSV</button>
                    </div>
                </div>
            </form>

            <div id="salesReportResult">
                <div class="rpt-preview-empty">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <p>Select a date range and click <strong>Generate Report</strong> to inspect real-time sales transactions and revenue.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ 2. INVENTORY REPORT ═══ -->
<div class="rpt-section" id="inventoryReport">
    <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <div class="card-body p-4">
            <div class="rpt-filters no-print">
                <div class="fg">
                    <label><i class="fas fa-filter me-1 text-primary"></i> Filter Products</label>
                    <select id="invFilter">
                        <option value="all">All Products</option>
                        <option value="low">Low Stock Only</option>
                        <option value="out">Out of Stock</option>
                    </select>
                </div>
                <div class="rpt-actions">
                    <button type="button" class="rpt-btn rpt-btn-gen" id="genInvBtn"><i class="fas fa-magnifying-glass"></i> Generate</button>
                    <button type="button" class="rpt-btn rpt-btn-pdf" id="pdfInvBtn" disabled><i class="fas fa-file-pdf"></i> PDF</button>
                    <button type="button" class="rpt-btn rpt-btn-print" id="printInvBtn" disabled><i class="fas fa-print"></i> Print</button>
                    <button type="button" class="rpt-btn rpt-btn-csv" id="csvInvBtn" disabled><i class="fas fa-file-csv"></i> CSV</button>
                </div>
            </div>

            <div id="invReportResult">
                <div class="rpt-preview-empty">
                    <i class="fas fa-warehouse"></i>
                    <p>Click <strong>Generate</strong> to view the real-time inventory status report.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ 3. PRODUCT SALES REPORT ═══ -->
<div class="rpt-section" id="productReport">
    <div class="card border-0 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <div class="card-body p-4">
            <div class="rpt-quick-ranges no-print" data-start="#prodStartDate" data-end="#prodEndDate">
                <button type="button" class="rpt-quick-btn" data-range="today">Today</button>
                <button type="button" class="rpt-quick-btn" data-range="week">This Week</button>
                <button type="button" class="rpt-quick-btn active" data-range="month">This Month</button>
                <button type="button" class="rpt-quick-btn" data-range="year">This Year</button>
            </div>
            <div class="rpt-filters no-print">
                <div class="fg">
                    <label><i class="fas fa-calendar-alt me-1 text-primary"></i> Start Date</label>
                    <input type="text" id="prodStartDate" placeholder="mm/dd/yyyy" autocomplete="off">
                </div>
                <div class="fg">
                    <label><i class="fas fa-calendar-alt me-1 text-primary"></i> End Date</label>
                    <input type="text" id="prodEndDate" placeholder="mm/dd/yyyy" autocomplete="off">
                </div>
                <div class="rpt-actions">
                    <button type="button" class="rpt-btn rpt-btn-gen" id="genProdBtn"><i class="fas fa-magnifying-glass"></i> Generate</button>
                    <button type="button" class="rpt-btn rpt-btn-pdf" id="pdfProdBtn" disabled><i class="fas fa-file-pdf"></i> PDF</button>
                    <button type="button" class="rpt-btn rpt-btn-print" id="printProdBtn" disabled><i class="fas fa-print"></i> Print</button>
                    <button type="button" class="rpt-btn rpt-btn-csv" id="csvProdBtn" disabled><i class="fas fa-file-csv"></i> CSV</button>
                </div>
            </div>

            <div id="prodReportResult">
                <div class="rpt-preview-empty">
                    <i class="fas fa-chart-pie"></i>
                    <p>Select a date range to see top-performing product sales.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jsPDF and autoTable CDNs for high-res PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script>
var REPORT_LOGO_BASE64 = <?= json_encode($reportLogoBase64) ?>;

$(document).ready(function() {
    $('#navReport').addClass('active');

    function setBtnLoading($btn, isLoading) {
        if (isLoading) {
            if (!$btn.data('orig-html')) $btn.data('orig-html', $btn.html());
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Generating…');
        } else {
            var orig = $btn.data('orig-html');
            if (orig) $btn.html(orig);
            $btn.prop('disabled', false);
        }
    }

    $("#salesStartDate, #salesEndDate, #prodStartDate, #prodEndDate").datepicker({ dateFormat: 'mm/dd/yy' });

    function fmtMDY(d) {
        var mm = ('0' + (d.getMonth() + 1)).slice(-2);
        var dd = ('0' + d.getDate()).slice(-2);
        return mm + '/' + dd + '/' + d.getFullYear();
    }

    function setQuickRange($group, range) {
        var $start = $($group.data('start'));
        var $end = $($group.data('end'));
        var now = new Date();
        var start, end = new Date(now);

        if (range === 'today') {
            start = new Date(now);
        } else if (range === 'week') {
            start = new Date(now);
            var day = start.getDay();
            var diffToMonday = (day === 0) ? 6 : day - 1;
            start.setDate(start.getDate() - diffToMonday);
        } else if (range === 'month') {
            start = new Date(now.getFullYear(), now.getMonth(), 1);
        } else if (range === 'year') {
            start = new Date(now.getFullYear(), 0, 1);
        } else {
            return;
        }

        $start.val(fmtMDY(start));
        $end.val(fmtMDY(end));
        $group.find('.rpt-quick-btn').removeClass('active');
        $group.find('.rpt-quick-btn[data-range="' + range + '"]').addClass('active');
    }

    $('.rpt-quick-btn').on('click', function() {
        var $group = $(this).closest('.rpt-quick-ranges');
        setQuickRange($group, $(this).data('range'));
    });

    // Default to "This Month"
    setQuickRange($('.rpt-quick-ranges[data-start="#salesStartDate"]'), 'month');
    setQuickRange($('.rpt-quick-ranges[data-start="#prodStartDate"]'), 'month');

    // Tab switching
    $('.rpt-tab').click(function() {
        $('.rpt-tab').removeClass('active');
        $(this).addClass('active');
        $('.rpt-section').removeClass('active');
        $('#' + $(this).data('target')).addClass('active');
    });

    // Low Stock card jump
    $('#lowStockSumCard').on('click', function() {
        $('.rpt-tab[data-target="inventoryReport"]').trigger('click');
        $('#invFilter').val('low');
        $('#genInvBtn').trigger('click');
    });

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text).replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    // ══════════════════════════════════════════════
    // SALES REPORT GENERATION
    // ══════════════════════════════════════════════
    $('#salesReportForm').on('submit', function(e) {
        e.preventDefault();
        var s = $('#salesStartDate').val(), en = $('#salesEndDate').val();
        if (!s || !en) { alert('Please select both Start Date and End Date.'); return; }

        var $genBtn = $('#salesReportForm .rpt-btn-gen');
        setBtnLoading($genBtn, true);

        $.post('php_action/getOrderReport.php', { startDate: s, endDate: en, format: 'json' })
        .done(function(data) {
            if (!data || !data.orders || data.orders.length === 0) {
                $('#salesReportResult').html('<div class="rpt-preview-empty"><i class="fas fa-inbox"></i><p>No sales transactions or orders found for the period <strong>' + escapeHtml(s) + ' — ' + escapeHtml(en) + '</strong>.</p></div>');
                $('#printSalesBtn, #pdfSalesBtn, #csvSalesBtn').prop('disabled', true);
                window._salesData = null;
                return;
            }

            var sum = data.summary || {};
            var meta = data.meta || {};
            var pay = data.payment_breakdown || {};

            var html = '<div class="rpt-printable" id="salesPrintArea">';

            var logoHtml = REPORT_LOGO_BASE64 ? '<img src="' + REPORT_LOGO_BASE64 + '" alt="Logo" style="height:36px; width:36px; object-fit:contain; background:transparent;" class="me-2">' : '';

            // 1. Report Header Banner
            html += '<div class="sales-report-banner" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">' +
                '<div style="display:flex; align-items:center; gap:12px;">' +
                    logoHtml +
                    '<div class="banner-title">' +
                        '<h3><i class="fas fa-file-invoice me-2 text-warning"></i>' + escapeHtml(meta.business_name || 'Agrivet Inventory Supply') + '</h3>' +
                        '<p><i class="fas fa-calendar-days me-1 text-warning"></i> Period: <strong>' + escapeHtml(meta.start_date) + ' — ' + escapeHtml(meta.end_date) + '</strong> &nbsp;|&nbsp; <i class="fas fa-clock me-1"></i> Generated: <strong>' + escapeHtml(meta.generated_at) + '</strong></p>' +
                    '</div>' +
                '</div>' +
                '<div class="banner-badge">' +
                    '<i class="fas fa-user-shield me-1"></i> Prepared By: ' + escapeHtml(meta.prepared_by || 'Admin') +
                '</div>' +
            '</div>';

            // 2. Summary KPI Cards (Period Specific)
            html += '<div class="rpt-summary">' +
                '<div class="rpt-sum-card">' +
                    '<div class="card-info">' +
                        '<div class="val">' + Number(sum.total_orders || 0).toLocaleString() + '</div>' +
                        '<div class="lbl">Period Sales &amp; Orders</div>' +
                    '</div>' +
                    '<div class="card-icon card-icon-sales"><i class="fas fa-shopping-bag"></i></div>' +
                '</div>' +
                '<div class="rpt-sum-card">' +
                    '<div class="card-info">' +
                        '<div class="val" style="color:#ea580c;">₱ ' + Number(sum.net_revenue || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</div>' +
                        '<div class="lbl">Period Net Revenue</div>' +
                    '</div>' +
                    '<div class="card-icon card-icon-rev"><i class="fas fa-money-bill-wave"></i></div>' +
                '</div>' +
                '<div class="rpt-sum-card">' +
                    '<div class="card-info">' +
                        '<div class="val">' + Number(sum.total_products || 0).toLocaleString() + '</div>' +
                        '<div class="lbl">Total Products</div>' +
                    '</div>' +
                    '<div class="card-icon card-icon-prod"><i class="fas fa-cubes"></i></div>' +
                '</div>' +
                '<div class="rpt-sum-card">' +
                    '<div class="card-info">' +
                        '<div class="val" style="color:' + ((sum.low_stock_count || 0) > 0 ? '#dc2626' : '#059669') + ';">' + Number(sum.low_stock_count || 0).toLocaleString() + '</div>' +
                        '<div class="lbl">Low Stock Alert</div>' +
                    '</div>' +
                    '<div class="card-icon card-icon-low"><i class="fas fa-triangle-exclamation"></i></div>' +
                '</div>' +
            '</div>';

            // 3. Two-Column Analytics Overview
            html += '<div class="report-analytics-grid">' +
                '<div class="analytics-card">' +
                    '<div class="analytics-card-title"><i class="fas fa-calculator text-primary me-1"></i> Financial Summary</div>' +
                    '<div class="fin-row"><span>Gross Sales Amount:</span><strong>₱ ' + Number(sum.total_gross || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</strong></div>' +
                    '<div class="fin-row"><span>Total Discounts Granted:</span><span class="text-danger">-₱ ' + Number(sum.total_discount || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span></div>' +
                    '<div class="fin-row"><span>Refunds &amp; Returns:</span><span class="text-danger">-₱ ' + Number(sum.total_refund || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span></div>' +
                    '<div class="fin-row total-row"><span>FINAL NET REVENUE:</span><span>₱ ' + Number(sum.net_revenue || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span></div>' +
                '</div>' +
                '<div class="analytics-card">' +
                    '<div class="analytics-card-title"><i class="fas fa-credit-card text-success me-1"></i> Payment Method Breakdown</div>' +
                    '<div class="pay-pill-list">';

            for (var pm in pay) {
                if (pay.hasOwnProperty(pm) && (pay[pm].count > 0 || pm === 'Cash' || pm === 'GCash')) {
                    var pmIcon = pm === 'Cash' ? 'fa-money-bill-1' : (pm === 'GCash' ? 'fa-mobile-screen-button' : (pm === 'Maya' ? 'fa-wallet' : 'fa-building-columns'));
                    html += '<div class="pay-pill-item">' +
                        '<span><i class="fas ' + pmIcon + ' me-2 text-muted"></i>' + escapeHtml(pm) + ' <span class="badge bg-secondary ms-1">' + pay[pm].count + ' sales</span></span>' +
                        '<span class="fw-bold">₱ ' + Number(pay[pm].amount || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span>' +
                    '</div>';
                }
            }

            html += '</div></div></div>';

            // 4. Detailed Sales Transactions Table
            html += '<div class="table-responsive" style="border:1.5px solid var(--border); border-radius:12px; overflow:hidden;">' +
                '<table class="rpt-table">' +
                '<thead><tr>' +
                    '<th scope="col" class="text-center" style="width:4%;">#</th>' +
                    '<th scope="col" class="text-center" style="width:14%;">Invoice / OR #</th>' +
                    '<th scope="col" class="text-center" style="width:14%;">Date &amp; Time</th>' +
                    '<th scope="col" style="width:18%;">Customer</th>' +
                    '<th scope="col" class="text-center" style="width:9%;">Cashier</th>' +
                    '<th scope="col" class="text-center" style="width:9%;">Payment</th>' +
                    '<th scope="col" class="text-end" style="width:8%;">Gross (₱)</th>' +
                    '<th scope="col" class="text-end" style="width:8%;">Disc (₱)</th>' +
                    '<th scope="col" class="text-end" style="width:8%;">Refund (₱)</th>' +
                    '<th scope="col" class="text-end" style="width:8%;">Net (₱)</th>' +
                '</tr></thead><tbody>';

            var totalGross = 0, totalDisc = 0, totalRefunded = 0, totalNet = 0;
            data.orders.forEach(function(o, i) {
                var gross = parseFloat(o.gross_amount) || 0;
                var disc = parseFloat(o.discount_amount) || 0;
                var ref = parseFloat(o.refunded_amount) || 0;
                var net = parseFloat(o.net_amount) || 0;

                totalGross += gross;
                totalDisc += disc;
                totalRefunded += ref;
                totalNet += net;

                var isPos = (o.source_type === 'POS Sale');
                var typeBadge = isPos ? '<span class="badge" style="background:#ea580c; font-size:0.68rem;">POS</span>' : '<span class="badge bg-secondary" style="font-size:0.68rem;">Order</span>';
                var refNo = escapeHtml(o.ref_no || ('ORD-' + o.order_id));
                var saleDt = escapeHtml(o.sale_datetime || o.sale_date || '');

                var payBadge = '<span class="badge bg-light text-dark border">' + escapeHtml(o.payment_method || 'Cash') + '</span>';

                html += '<tr>' +
                    '<td class="text-center text-muted">' + (i + 1) + '</td>' +
                    '<td class="text-center"><span class="font-monospace fw-bold text-dark">' + refNo + '</span> ' + typeBadge + '</td>' +
                    '<td class="text-center"><small class="text-muted">' + saleDt + '</small></td>' +
                    '<td><span class="fw-semibold">' + escapeHtml(o.client_name || 'Walk-in Customer') + '</span></td>' +
                    '<td class="text-center"><small class="text-muted">' + escapeHtml(o.cashier_name || 'Staff') + '</small></td>' +
                    '<td class="text-center">' + payBadge + '</td>' +
                    '<td class="text-end fw-semibold">' + gross.toFixed(2) + '</td>' +
                    '<td class="text-end ' + (disc > 0 ? 'text-danger' : 'text-muted') + '">' + (disc > 0 ? ('-' + disc.toFixed(2)) : '0.00') + '</td>' +
                    '<td class="text-end ' + (ref > 0 ? 'text-danger fw-bold' : 'text-muted') + '">' + (ref > 0 ? ('-' + ref.toFixed(2)) : '0.00') + '</td>' +
                    '<td class="text-end fw-bold text-dark" style="background:rgba(234,88,12,0.04);">' + net.toFixed(2) + '</td>' +
                '</tr>';
            });

            html += '</tbody><tfoot><tr>' +
                '<td colspan="6" class="text-end" style="font-size:.9rem; font-weight:800; color:#1e293b;">TOTAL SUMMARY:</td>' +
                '<td class="text-end font-monospace" style="font-size:.9rem;">₱ ' + totalGross.toFixed(2) + '</td>' +
                '<td class="text-end text-danger font-monospace" style="font-size:.9rem;">-₱ ' + totalDisc.toFixed(2) + '</td>' +
                '<td class="text-end text-danger font-monospace" style="font-size:.9rem;">-₱ ' + totalRefunded.toFixed(2) + '</td>' +
                '<td class="text-end font-monospace fw-bold" style="font-size:1rem; color:#ea580c; background:rgba(234,88,12,0.08);">₱ ' + totalNet.toFixed(2) + '</td>' +
            '</tr></tfoot></table></div>';

            html += '<div style="margin-top:1.25rem; font-size:.78rem; color:#94a3b8; text-align:center;" class="no-print">' +
                '<i class="fas fa-shield-halved me-1"></i> Official System Sales Audit • Generated on ' + escapeHtml(meta.generated_at) +
            '</div>';
            html += '</div>';

            $('#salesReportResult').html(html);
            $('#printSalesBtn, #pdfSalesBtn, #csvSalesBtn').prop('disabled', false);

            window._salesData = data;
        })
        .fail(function() {
            $('#salesReportResult').html('<div class="rpt-preview-empty"><i class="fas fa-exclamation-triangle text-danger"></i><p>Could not generate sales report. Please check server logs.</p></div>');
            $('#printSalesBtn, #pdfSalesBtn, #csvSalesBtn').prop('disabled', true);
        })
        .always(function() {
            setBtnLoading($genBtn, false);
        });
    });

    // ══════════════════════════════════════════════
    // PROFESSIONAL SALES REPORT PDF EXPORT
    // ══════════════════════════════════════════════
    function exportProfessionalSalesPDF(data) {
        if (!data || !data.orders || !window.jspdf) {
            alert('No sales report data available for export.');
            return;
        }

        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF('p', 'mm', 'a4'); // Portrait A4 (210 x 297 mm)
        var pageWidth = doc.internal.pageSize.getWidth();
        var pageHeight = doc.internal.pageSize.getHeight();
        var meta = data.meta || {};
        var sum = data.summary || {};
        var pay = data.payment_breakdown || {};

        // 1. Top Orange Header Bar (Brand Accent)
        doc.setFillColor(234, 88, 12); // #ea580c Agrivet Orange
        doc.rect(0, 0, pageWidth, 24, 'F');

        var textStartX = 14;
        if (REPORT_LOGO_BASE64) {
            try {
                // Circular emblem: 17mm x 17mm, placed at x=14, y=3.5
                doc.addImage(REPORT_LOGO_BASE64, 'PNG', 14, 3.5, 17, 17);
                textStartX = 35;
            } catch (e) {
                console.warn('Could not add logo to PDF:', e);
                textStartX = 14;
            }
        }

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(13);
        doc.setTextColor(255, 255, 255);
        doc.text(String(meta.business_name || 'AGRIVET INVENTORY SUPPLY').toUpperCase(), textStartX, 11);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(255, 237, 213); // #ffedd5
        doc.text('OFFICIAL SALES & REVENUE REPORT', textStartX, 18);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(255, 255, 255);
        doc.text('CONFIDENTIAL AUDIT', pageWidth - 14, 15, { align: 'right' });

        // 2. Metadata Information Ribbon
        var startY = 30;
        doc.setFillColor(248, 250, 252); // #f8fafc
        doc.setDrawColor(226, 232, 240); // #e2e8f0
        doc.roundedRect(14, startY, pageWidth - 28, 14, 2, 2, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.setTextColor(15, 23, 42); // #0f172a
        doc.text('REPORT PERIOD:', 18, startY + 6);
        doc.setFont('helvetica', 'normal');
        doc.text(String(meta.start_date) + ' to ' + String(meta.end_date), 46, startY + 6);

        doc.setFont('helvetica', 'bold');
        doc.text('GENERATED ON:', 18, startY + 11);
        doc.setFont('helvetica', 'normal');
        doc.text(String(meta.generated_at), 46, startY + 11);

        doc.setFont('helvetica', 'bold');
        doc.text('PREPARED BY:', 125, startY + 6);
        doc.setFont('helvetica', 'normal');
        doc.text(String(meta.prepared_by || 'Admin') + ' (' + String(meta.prepared_by_role || 'Staff') + ')', 152, startY + 6);

        doc.setFont('helvetica', 'bold');
        doc.text('STORE ADDRESS:', 125, startY + 11);
        doc.setFont('helvetica', 'normal');
        doc.text(String(meta.business_address || 'Poblacion Salay Mis. Or.'), 152, startY + 11);

        // 3. Four Executive KPI Cards
        var cardY = startY + 18;
        var cardWidth = (pageWidth - 28 - 9) / 4;
        var cardHeight = 16;

        var kpis = [
            { label: 'TOTAL ORDERS', val: String(Number(sum.total_orders || 0).toLocaleString()), color: [15, 23, 42] },
            { label: 'NET REVENUE', val: 'PHP ' + Number(sum.net_revenue || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}), color: [234, 88, 12] },
            { label: 'TOTAL PRODUCTS', val: String(Number(sum.total_products || 0).toLocaleString()), color: [37, 99, 235] },
            { label: 'LOW STOCK ALERT', val: String(Number(sum.low_stock_count || 0).toLocaleString()) + ' Items', color: [220, 38, 38] }
        ];

        kpis.forEach(function(kpi, idx) {
            var cx = 14 + idx * (cardWidth + 3);
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor(226, 232, 240);
            doc.roundedRect(cx, cardY, cardWidth, cardHeight, 1.5, 1.5, 'FD');

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(6.5);
            doc.setTextColor(100, 116, 139);
            doc.text(kpi.label, cx + 4, cardY + 5.5);

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9.5);
            doc.setTextColor(kpi.color[0], kpi.color[1], kpi.color[2]);
            doc.text(kpi.val, cx + 4, cardY + 12);
        });

        // 4. Detailed Sales Transactions Table
        var tableHeaders = ['#', 'Invoice/OR #', 'Date & Time', 'Customer Name', 'Staff', 'Payment', 'Gross (PHP)', 'Disc (PHP)', 'Refund (PHP)', 'Net (PHP)'];
        var tableRows = [];

        data.orders.forEach(function(o, i) {
            var gross = parseFloat(o.gross_amount) || 0;
            var disc = parseFloat(o.discount_amount) || 0;
            var ref = parseFloat(o.refunded_amount) || 0;
            var net = parseFloat(o.net_amount) || 0;

            tableRows.push([
                String(i + 1),
                String(o.ref_no || ('ORD-' + o.order_id)),
                String(o.sale_datetime || o.sale_date || ''),
                String(o.client_name || 'Walk-in Customer'),
                String(o.cashier_name || 'Staff'),
                String(o.payment_method || 'Cash'),
                gross.toFixed(2),
                disc > 0 ? ('-' + disc.toFixed(2)) : '0.00',
                ref > 0 ? ('-' + ref.toFixed(2)) : '0.00',
                net.toFixed(2)
            ]);
        });

        var totalDiscNum = Number(sum.total_discount || 0);
        var totalRefNum = Number(sum.total_refund || 0);

        var tableFoot = [
            [
                { content: 'TOTAL SUMMARY:', colSpan: 6, styles: { halign: 'right', fontStyle: 'bold' } },
                { content: Number(sum.total_gross || 0).toFixed(2), styles: { halign: 'right', fontStyle: 'bold' } },
                { content: (totalDiscNum > 0 ? ('-' + totalDiscNum.toFixed(2)) : '0.00'), styles: { halign: 'right', fontStyle: 'bold', textColor: totalDiscNum > 0 ? [220, 38, 38] : [30, 41, 59] } },
                { content: (totalRefNum > 0 ? ('-' + totalRefNum.toFixed(2)) : '0.00'), styles: { halign: 'right', fontStyle: 'bold', textColor: totalRefNum > 0 ? [220, 38, 38] : [30, 41, 59] } },
                { content: Number(sum.net_revenue || 0).toFixed(2), styles: { halign: 'right', fontStyle: 'bold', textColor: [234, 88, 12] } }
            ]
        ];

        doc.autoTable({
            head: [tableHeaders],
            body: tableRows,
            foot: tableFoot,
            startY: cardY + cardHeight + 4,
            tableWidth: 182,
            margin: { left: 14, right: 14, bottom: 25 },
            styles: {
                font: 'helvetica',
                fontSize: 7.2,
                cellPadding: 2,
                valign: 'middle',
                textColor: [30, 41, 59],
                lineColor: [226, 232, 240],
                lineWidth: 0.1
            },
            headStyles: {
                fillColor: [234, 88, 12], // Agrivet Orange
                textColor: [255, 255, 255],
                fontStyle: 'bold',
                fontSize: 7,
                valign: 'middle'
            },
            alternateRowStyles: {
                fillColor: [248, 250, 252]
            },
            footStyles: {
                fillColor: [241, 245, 249],
                textColor: [30, 41, 59],
                fontStyle: 'bold',
                fontSize: 7.5,
                valign: 'middle'
            },
            columnStyles: {
                0: { halign: 'center', cellWidth: 8 },
                1: { halign: 'center', fontStyle: 'bold', cellWidth: 26 },
                2: { halign: 'center', cellWidth: 28 },
                3: { cellWidth: 34 },
                4: { halign: 'center', cellWidth: 16 },
                5: { halign: 'center', cellWidth: 16 },
                6: { halign: 'right', cellWidth: 15 },
                7: { halign: 'right', cellWidth: 12 },
                8: { halign: 'right', cellWidth: 12 },
                9: { halign: 'right', fontStyle: 'bold', cellWidth: 15 }
            },
            didDrawPage: function(hookData) {
                // Page footer
                var str = 'Page ' + doc.internal.getNumberOfPages();
                doc.setFontSize(7);
                doc.setTextColor(148, 163, 184);
                doc.text(str, pageWidth - 14, pageHeight - 8, { align: 'right' });
                doc.text('Agrivet Inventory Supply Management System • Confidential', 14, pageHeight - 8);
            }
        });

        // 5. Financial Summary & Signatures Box on Final Page
        var finalY = doc.lastAutoTable.finalY + 6;
        if (finalY > pageHeight - 45) {
            doc.addPage();
            finalY = 20;
        }

        // Summary Box Left
        var sumBoxWidth = (pageWidth - 28 - 8) / 2;
        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(226, 232, 240);
        doc.roundedRect(14, finalY, sumBoxWidth, 26, 2, 2, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(15, 23, 42);
        doc.text('EXECUTIVE REVENUE BREAKDOWN', 18, finalY + 5);

        doc.setFontSize(7);
        doc.setFont('helvetica', 'normal');
        doc.text('Gross Sales Total:', 18, finalY + 10);
        doc.text('PHP ' + Number(sum.total_gross || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}), 14 + sumBoxWidth - 4, finalY + 10, { align: 'right' });

        doc.text('Discounts & Reductions:', 18, finalY + 15);
        doc.text('-PHP ' + Number(sum.total_discount || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}), 14 + sumBoxWidth - 4, finalY + 15, { align: 'right' });

        doc.text('Returns & Refunds:', 18, finalY + 19);
        doc.text('-PHP ' + Number(sum.total_refund || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}), 14 + sumBoxWidth - 4, finalY + 19, { align: 'right' });

        doc.setFont('helvetica', 'bold');
        doc.setTextColor(234, 88, 12);
        doc.text('FINAL NET REVENUE:', 18, finalY + 23.5);
        doc.text('PHP ' + Number(sum.net_revenue || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}), 14 + sumBoxWidth - 4, finalY + 23.5, { align: 'right' });

        // Payment Method Box Right
        var payX = 14 + sumBoxWidth + 8;
        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(226, 232, 240);
        doc.roundedRect(payX, finalY, sumBoxWidth, 26, 2, 2, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(15, 23, 42);
        doc.text('PAYMENT CHANNEL AUDIT', payX + 4, finalY + 5);

        doc.setFontSize(7);
        doc.setFont('helvetica', 'normal');
        var payY = finalY + 10;
        for (var pMethod in pay) {
            if (pay.hasOwnProperty(pMethod) && (pay[pMethod].count > 0 || pMethod === 'Cash' || pMethod === 'GCash')) {
                doc.text(String(pMethod) + ' (' + pay[pMethod].count + '):', payX + 4, payY);
                doc.text('PHP ' + Number(pay[pMethod].amount || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}), payX + sumBoxWidth - 4, payY, { align: 'right' });
                payY += 4.5;
                if (payY > finalY + 24) break;
            }
        }

        // Signature Blocks
        var sigY = finalY + 33;
        if (sigY < pageHeight - 18) {
            var leftCenter = 14 + sumBoxWidth / 2;
            var rightCenter = payX + sumBoxWidth / 2;
            doc.setDrawColor(148, 163, 184);
            doc.line(leftCenter - 30, sigY + 8, leftCenter + 30, sigY + 8);
            doc.line(rightCenter - 30, sigY + 8, rightCenter + 30, sigY + 8);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.setTextColor(100, 116, 139);
            doc.text('Prepared By: ' + String(meta.prepared_by || 'Admin'), leftCenter, sigY + 11.5, { align: 'center' });
            doc.text('Verified & Approved By: Manager', rightCenter, sigY + 11.5, { align: 'center' });
        }

        // Save PDF
        var rawStart = meta.raw_start_date || 'start';
        var rawEnd = meta.raw_end_date || 'end';
        doc.save('Agrivet_Sales_Report_' + rawStart + '_to_' + rawEnd + '.pdf');
    }

    $('#pdfSalesBtn').click(function() {
        if (window._salesData) {
            exportProfessionalSalesPDF(window._salesData);
        }
    });

    // CSV
    $('#csvSalesBtn').click(function() {
        if (!window._salesData) return;
        var rows = [['#','Invoice / OR #','Type','Date & Time','Customer','Staff / Cashier','Payment Method','Gross (PHP)','Discount (PHP)','Refund (PHP)','Net Revenue (PHP)']];
        window._salesData.orders.forEach(function(o,i) {
            var gross = parseFloat(o.gross_amount) || 0;
            var disc = parseFloat(o.discount_amount) || 0;
            var ref = parseFloat(o.refunded_amount) || 0;
            var net = parseFloat(o.net_amount) || 0;
            rows.push([
                i + 1,
                o.ref_no || ('ORD-' + o.order_id),
                o.source_type || 'Order',
                o.sale_datetime || o.sale_date,
                o.client_name || 'Walk-in Customer',
                o.cashier_name || 'Staff',
                o.payment_method || 'Cash',
                gross.toFixed(2),
                disc.toFixed(2),
                ref.toFixed(2),
                net.toFixed(2)
            ]);
        });
        var sum = window._salesData.summary || {};
        rows.push(['TOTAL', '', '', '', '', '', '', Number(sum.total_gross || 0).toFixed(2), Number(sum.total_discount || 0).toFixed(2), Number(sum.total_refund || 0).toFixed(2), Number(sum.net_revenue || 0).toFixed(2)]);
        downloadCSV(rows, 'Agrivet_Sales_Report');
    });

    // ══════════════════════════════════════════════
    // INVENTORY REPORT
    // ══════════════════════════════════════════════
    $('#genInvBtn').click(function() {
        var filter = $('#invFilter').val();
        var $genBtn = $(this);
        setBtnLoading($genBtn, true);
        $.get('php_action/getInventoryReport.php', { filter: filter })
        .done(function(data) {
            if (!data || !data.products || data.products.length === 0) {
                $('#invReportResult').html('<div class="rpt-preview-empty"><i class="fas fa-inbox"></i><p>No products found for this inventory filter.</p></div>');
                $('#printInvBtn, #pdfInvBtn, #csvInvBtn').prop('disabled', true);
                return;
            }

            var invLogoHtml = REPORT_LOGO_BASE64 ? '<img src="' + REPORT_LOGO_BASE64 + '" alt="Logo" style="height:36px; width:36px; object-fit:contain; background:transparent;" class="me-2">' : '';

            var html = '<div class="rpt-printable" id="invPrintArea">';
            html += '<div class="sales-report-banner" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">' +
                '<div style="display:flex; align-items:center; gap:12px;">' +
                    invLogoHtml +
                    '<div class="banner-title">' +
                        '<h3><i class="fas fa-warehouse me-2 text-warning"></i>Agrivet Inventory Supply</h3>' +
                        '<p><i class="fas fa-boxes-stacked me-1 text-warning"></i> Inventory Status Report &nbsp;|&nbsp; Showing ' + data.products.length + ' products</p>' +
                    '</div>' +
                '</div>' +
                '<div class="banner-badge">' +
                    '<i class="fas fa-clock me-1"></i> Generated: ' + new Date().toLocaleDateString() +
                '</div>' +
            '</div>';

            html += '<div class="table-responsive" style="border:1.5px solid var(--border); border-radius:12px; overflow:hidden;"><table class="rpt-table"><thead><tr><th scope="col">#</th><th scope="col">Product</th><th scope="col">Brand</th><th scope="col">Category</th><th scope="col">Unit</th><th scope="col" class="text-end">Price</th><th scope="col" class="text-end">Qty</th><th scope="col" class="text-end">Sold</th><th scope="col" class="text-end">Remaining</th><th scope="col">Status</th></tr></thead><tbody>';

            var totalQty = 0, totalSold = 0, totalRem = 0;
            data.products.forEach(function(p, i) {
                var q = parseFloat(p.quantity || 0);
                var s = parseFloat(p.sold || 0);
                var rem = parseFloat(p.available_quantity || 0);
                totalQty += q; totalSold += s; totalRem += rem;
                var statusCls = rem <= 0 ? 'color:#dc2626;' : (rem <= 3 ? 'color:#ea580c;' : 'color:#059669;');
                var statusTxt = rem <= 0 ? 'Out of Stock' : (rem <= 3 ? 'Low Stock' : 'In Stock');
                html += '<tr><td>' + (i+1) + '</td><td style="font-weight:700;">' + escapeHtml(p.product_name) + '</td><td>' + escapeHtml(p.brand_name) + '</td><td>' + escapeHtml(p.categories_name) + '</td>';
                html += '<td>' + escapeHtml(p.unit_type||'Piece') + '</td><td class="text-end">' + parseFloat(p.rate).toFixed(2) + '</td>';
                html += '<td class="text-end">' + q.toFixed(2) + '</td><td class="text-end">' + s.toFixed(2) + '</td>';
                html += '<td class="text-end" style="font-weight:700;">' + rem.toFixed(2) + '</td>';
                html += '<td style="' + statusCls + 'font-weight:700;font-size:.78rem;">' + statusTxt + '</td></tr>';
            });

            html += '</tbody><tfoot><tr><td colspan="6" class="text-end">TOTAL</td><td class="text-end">' + totalQty.toFixed(2) + '</td><td class="text-end">' + totalSold.toFixed(2) + '</td><td class="text-end" style="color:#ea580c;">' + totalRem.toFixed(2) + '</td><td></td></tr></tfoot></table></div></div>';
            $('#invReportResult').html(html);
            $('#printInvBtn, #pdfInvBtn, #csvInvBtn').prop('disabled', false);
            window._invData = data;
        })
        .fail(function() {
            $('#invReportResult').html('<div class="rpt-preview-empty"><i class="fas fa-exclamation-triangle text-danger"></i><p>Could not generate inventory report.</p></div>');
        })
        .always(function() {
            setBtnLoading($genBtn, false);
        });
    });

    $('#printInvBtn').click(function() { printReport('invPrintArea'); });
    $('#pdfInvBtn').click(function() {
        if (!window._invData) return;
        var totalQty = 0, totalSold = 0, totalRem = 0;
        var rows = window._invData.products.map(function(p,i) {
            var q = parseFloat(p.quantity || 0);
            var s = parseFloat(p.sold || 0);
            var rem = parseFloat(p.available_quantity || 0);
            totalQty += q; totalSold += s; totalRem += rem;
            return [i+1, p.product_name, p.brand_name, p.categories_name, p.unit_type||'Piece',
                parseFloat(p.rate).toFixed(2), q.toFixed(2), s.toFixed(2), rem.toFixed(2),
                rem <= 0 ? 'Out of Stock' : (rem <= 3 ? 'Low Stock' : 'In Stock')];
        });
        var foot = [
            [
                { content: 'TOTAL:', colSpan: 6, styles: { halign: 'right', fontStyle: 'bold' } },
                { content: totalQty.toFixed(2), styles: { halign: 'right', fontStyle: 'bold' } },
                { content: totalSold.toFixed(2), styles: { halign: 'right', fontStyle: 'bold' } },
                { content: totalRem.toFixed(2), styles: { halign: 'right', fontStyle: 'bold', textColor: [234, 88, 12] } },
                { content: '', styles: { halign: 'center' } }
            ]
        ];
        exportGenericPDF('Agrivet Inventory Report', ['#','Product','Brand','Category','Unit','Price','Qty','Sold','Remaining','Status'], rows, 'Agrivet_Inventory_Report', foot);
    });

    $('#csvInvBtn').click(function() {
        if (!window._invData) return;
        var totalQty = 0, totalSold = 0, totalRem = 0;
        var rows = [['#','Product','Brand','Category','Unit','Price','Qty','Sold','Remaining','Status']];
        window._invData.products.forEach(function(p,i) {
            var q = parseFloat(p.quantity || 0);
            var s = parseFloat(p.sold || 0);
            var rem = parseFloat(p.available_quantity || 0);
            totalQty += q; totalSold += s; totalRem += rem;
            rows.push([i+1, p.product_name, p.brand_name, p.categories_name, p.unit_type||'Piece',
                parseFloat(p.rate).toFixed(2), q.toFixed(2), s.toFixed(2), rem.toFixed(2),
                rem <= 0 ? 'Out of Stock' : (rem <= 3 ? 'Low Stock' : 'In Stock')]);
        });
        rows.push(['TOTAL', '', '', '', '', '', totalQty.toFixed(2), totalSold.toFixed(2), totalRem.toFixed(2), '']);
        downloadCSV(rows, 'Agrivet_Inventory_Report');
    });

    // ══════════════════════════════════════════════
    // PRODUCT SALES REPORT
    // ══════════════════════════════════════════════
    $('#genProdBtn').click(function() {
        var s = $('#prodStartDate').val(), en = $('#prodEndDate').val();
        if (!s || !en) { alert('Please select both Start Date and End Date.'); return; }

        var $genBtn = $(this);
        setBtnLoading($genBtn, true);
        $.get('php_action/getProductSalesReport.php', { startDate: s, endDate: en })
        .done(function(data) {
            if (!data || !data.products || data.products.length === 0) {
                $('#prodReportResult').html('<div class="rpt-preview-empty"><i class="fas fa-inbox"></i><p>No product sales found for this period.</p></div>');
                $('#printProdBtn, #pdfProdBtn, #csvProdBtn').prop('disabled', true);
                return;
            }

            var prodLogoHtml = REPORT_LOGO_BASE64 ? '<img src="' + REPORT_LOGO_BASE64 + '" alt="Logo" style="height:36px; width:36px; object-fit:contain; background:transparent;" class="me-2">' : '';

            var html = '<div class="rpt-printable" id="prodPrintArea">';
            html += '<div class="sales-report-banner" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">' +
                '<div style="display:flex; align-items:center; gap:12px;">' +
                    prodLogoHtml +
                    '<div class="banner-title">' +
                        '<h3><i class="fas fa-chart-pie me-2 text-warning"></i>Agrivet Inventory Supply</h3>' +
                        '<p><i class="fas fa-calendar-days me-1 text-warning"></i> Product Sales Report: <strong>' + escapeHtml(s) + ' — ' + escapeHtml(en) + '</strong></p>' +
                    '</div>' +
                '</div>' +
                '<div class="banner-badge">' +
                    '<i class="fas fa-clock me-1"></i> Generated: ' + new Date().toLocaleDateString() +
                '</div>' +
            '</div>';

            html += '<div class="table-responsive" style="border:1.5px solid var(--border); border-radius:12px; overflow:hidden;"><table class="rpt-table"><thead><tr><th scope="col">#</th><th scope="col">Product</th><th scope="col">Brand</th><th scope="col">Unit</th><th scope="col" class="text-end">Unit Price</th><th scope="col" class="text-end">Qty Sold</th><th scope="col" class="text-end">Total Revenue</th></tr></thead><tbody>';

            var totalQty = 0, totalRev = 0;
            data.products.forEach(function(p, i) {
                var qty = parseInt(p.qty_sold) || 0; var rev = parseFloat(p.revenue) || 0;
                totalQty += qty; totalRev += rev;
                html += '<tr><td>' + (i+1) + '</td><td style="font-weight:700;">' + escapeHtml(p.product_name) + '</td><td>' + escapeHtml(p.brand_name) + '</td>';
                html += '<td>' + escapeHtml(p.unit_type||'Piece') + '</td><td class="text-end">' + parseFloat(p.rate).toFixed(2) + '</td>';
                html += '<td class="text-end">' + qty + '</td><td class="text-end" style="font-weight:700;">' + rev.toFixed(2) + '</td></tr>';
            });

            html += '</tbody><tfoot><tr><td colspan="5" class="text-end">TOTAL</td><td class="text-end">' + totalQty + '</td><td class="text-end" style="color:#ea580c;">' + totalRev.toFixed(2) + '</td></tr></tfoot></table></div></div>';
            $('#prodReportResult').html(html);
            $('#printProdBtn, #pdfProdBtn, #csvProdBtn').prop('disabled', false);
            window._prodData = data;
        })
        .fail(function() {
            $('#prodReportResult').html('<div class="rpt-preview-empty"><i class="fas fa-exclamation-triangle text-danger"></i><p>Could not generate product sales report.</p></div>');
        })
        .always(function() {
            setBtnLoading($genBtn, false);
        });
    });

    $('#printProdBtn').click(function() { printReport('prodPrintArea'); });
    $('#pdfProdBtn').click(function() {
        if (!window._prodData) return;
        var totalQty = 0, totalRev = 0;
        var rows = window._prodData.products.map(function(p,i) {
            var qty = parseInt(p.qty_sold) || 0;
            var rev = parseFloat(p.revenue) || 0;
            totalQty += qty; totalRev += rev;
            return [i+1, p.product_name, p.brand_name, p.unit_type||'Piece',
                parseFloat(p.rate).toFixed(2), qty, rev.toFixed(2)];
        });
        var foot = [
            [
                { content: 'TOTAL:', colSpan: 5, styles: { halign: 'right', fontStyle: 'bold' } },
                { content: String(totalQty), styles: { halign: 'right', fontStyle: 'bold' } },
                { content: totalRev.toFixed(2), styles: { halign: 'right', fontStyle: 'bold', textColor: [234, 88, 12] } }
            ]
        ];
        var dateSub = ($('#prodStartDate').val() && $('#prodEndDate').val()) ? ($('#prodStartDate').val() + ' — ' + $('#prodEndDate').val()) : new Date().toLocaleDateString();
        exportGenericPDF('Product Sales Report (' + dateSub + ')', ['#','Product','Brand','Unit','Unit Price','Qty Sold','Total Revenue'], rows, 'Agrivet_Product_Sales', foot);
    });

    $('#csvProdBtn').click(function() {
        if (!window._prodData) return;
        var totalQty = 0, totalRev = 0;
        var rows = [['#','Product','Brand','Unit','Unit Price','Qty Sold','Total Revenue']];
        window._prodData.products.forEach(function(p,i) {
            var qty = parseInt(p.qty_sold) || 0;
            var rev = parseFloat(p.revenue) || 0;
            totalQty += qty; totalRev += rev;
            rows.push([i+1, p.product_name, p.brand_name, p.unit_type||'Piece',
                parseFloat(p.rate).toFixed(2), qty, rev.toFixed(2)]);
        });
        rows.push(['TOTAL', '', '', '', '', totalQty, totalRev.toFixed(2)]);
        downloadCSV(rows, 'Agrivet_Product_Sales');
    });

    // ══════════════════════════════════════════════
    // PRINT & GENERIC EXPORT HELPERS
    // ══════════════════════════════════════════════
    function printReport(areaId) {
        var content = document.getElementById(areaId);
        if (!content) return;
        var w = window.open('', 'Report', 'width=980,height=750');
        w.document.write('<html><head><title>Agrivet Inventory Supply - Sales Report</title>');
        w.document.write('<style>' +
            'body{font-family:Arial,Helvetica,sans-serif;padding:20px;color:#0f172a;font-size:12px;background:#fff;}' +
            '.sales-report-banner{background:#1e293b!important;color:#fff!important;border-radius:8px;padding:15px;margin-bottom:15px;border-left:5px solid #ea580c;}' +
            '.sales-report-banner h3{font-size:16px;margin:0 0 5px 0;color:#fff!important;}' +
            '.sales-report-banner p{font-size:11px;color:#cbd5e1!important;margin:0;}' +
            '.rpt-summary{display:flex;gap:10px;margin-bottom:15px;}' +
            '.rpt-sum-card{flex:1;border:1px solid #cbd5e1;border-radius:8px;padding:10px;text-align:left;background:#f8fafc;}' +
            '.rpt-sum-card .val{font-size:15px;font-weight:bold;color:#0f172a;}' +
            '.rpt-sum-card .lbl{font-size:9px;text-transform:uppercase;color:#64748b;margin-top:3px;}' +
            '.report-analytics-grid{display:flex;gap:10px;margin-bottom:15px;}' +
            '.analytics-card{flex:1;border:1px solid #cbd5e1;border-radius:8px;padding:10px;background:#fff;}' +
            '.analytics-card-title{font-weight:bold;font-size:11px;margin-bottom:8px;text-transform:uppercase;color:#0f172a;}' +
            '.fin-row{display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px dashed #e2e8f0;font-size:11px;}' +
            '.fin-row.total-row{border-top:2px solid #ea580c;border-bottom:2px solid #ea580c;font-weight:bold;color:#ea580c;margin-top:5px;}' +
            '.pay-pill-list{display:flex;flex-direction:column;gap:5px;}' +
            '.pay-pill-item{display:flex;justify-content:space-between;background:#f8fafc;padding:5px 8px;border-radius:4px;font-size:11px;}' +
            'table{width:100%;border-collapse:collapse;margin-bottom:15px;}' +
            'th{background:#1e293b!important;color:#fff!important;font-size:9.5px;text-transform:uppercase;padding:8px 6px;border:1px solid #0f172a;text-align:left;}' +
            'td{padding:6px 6px;border:1px solid #e2e8f0;font-size:10.5px;}' +
            'tfoot td{font-weight:bold;background:#f1f5f9;border-top:2px solid #cbd5e1;}' +
            '.text-end{text-align:right;}' +
            '.text-center{text-align:center;}' +
            '.no-print{display:none!important;}' +
            '</style>');
        w.document.write('</head><body>');
        w.document.write(content.innerHTML);
        w.document.write('<div style="margin-top:25px;text-align:center;font-size:9px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:8px;">Agrivet Inventory Supply Management System • Confidential</div>');
        w.document.write('</body></html>');
        w.document.close();
        w.focus();
        setTimeout(function() { w.print(); w.close(); }, 400);
    }

    function exportGenericPDF(title, headers, rows, filename, foot) {
        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF('l', 'mm', 'a4');
        var pageWidth = doc.internal.pageSize.getWidth();
        doc.setFillColor(234, 88, 12);
        doc.rect(0, 0, pageWidth, 22, 'F');

        var textStartX = 14;
        if (REPORT_LOGO_BASE64) {
            try {
                doc.addImage(REPORT_LOGO_BASE64, 'PNG', 14, 3, 16, 16);
                textStartX = 34;
            } catch (e) {
                textStartX = 14;
            }
        }

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(13);
        doc.setTextColor(255, 255, 255);
        doc.text('AGRIVET INVENTORY SUPPLY', textStartX, 11);
        doc.setFontSize(8.5);
        doc.setTextColor(255, 237, 213);
        doc.text(title, textStartX, 17.5);

        var autoTableConfig = {
            head: [headers],
            body: rows,
            startY: 24,
            styles: { fontSize: 8, cellPadding: 2.5 },
            headStyles: { fillColor: [30, 41, 59], textColor: 255, fontStyle: 'bold', fontSize: 7.5 },
            alternateRowStyles: { fillColor: [248, 250, 252] },
            footStyles: { fillColor: [241, 245, 249], textColor: [234, 88, 12], fontStyle: 'bold', fontSize: 8 }
        };

        if (foot && foot.length > 0) {
            autoTableConfig.foot = Array.isArray(foot[0]) ? foot : [foot];
        }

        doc.autoTable(autoTableConfig);
        doc.setFontSize(7);
        doc.setTextColor(148, 163, 184);
        doc.text('Generated: ' + new Date().toLocaleString(), 14, doc.internal.pageSize.getHeight() - 6);
        doc.save(filename + '_' + new Date().toISOString().slice(0,10) + '.pdf');
    }

    function downloadCSV(rows, filename) {
        var csv = rows.map(function(r) { return r.map(function(c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(','); }).join('\n');
        var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename + '_' + new Date().toISOString().slice(0,10) + '.csv';
        a.click();
    }
});
</script>
