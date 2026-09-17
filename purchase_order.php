<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('purchase_order');
require_once 'includes/header_sidebar.php';
?>
<?php require_once 'php_action/unit_helper.php'; ?>

<?php
// Fetch active products for dropdowns & instant search
$products = $connect->query("
    SELECT p.product_id, p.product_name, p.unit_type, p.rate, p.original_price, p.sold_by_weight, 
           p.base_unit, p.purchase_unit, p.conversion_factor, p.sack_size_kg, p.barcode, p.sku,
           c.categories_name, b.brand_name, p.product_image
    FROM product p
    LEFT JOIN categories c ON c.categories_id = p.categories_id
    LEFT JOIN brands b ON b.brand_id = p.brand_id
    WHERE p.status = 1
    ORDER BY p.product_name
");
$productList = [];
while ($p = $products->fetch_assoc()) { $productList[] = $p; }

// Fetch active suppliers with contact info
$suppliers = $connect->query("SELECT supplier_id, supplier_name, contact_person, phone, email, lead_time_days FROM suppliers WHERE status = 1 ORDER BY supplier_name");
$supplierList = [];
while ($s = $suppliers->fetch_assoc()) { $supplierList[] = $s; }

// Fetch PO statistics for KPI cards
$poStatsRes = $connect->query("
    SELECT
        COUNT(*) as total_count,
        COALESCE(SUM(total_amount), 0) as total_val,
        COALESCE(SUM(CASE WHEN status NOT IN ('received', 'cancelled') THEN 1 ELSE 0 END), 0) as sent_count,
        COALESCE(SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END), 0) as received_count,
        COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_count
    FROM purchase_orders
");
$poStats = $poStatsRes ? $poStatsRes->fetch_assoc() : ['total_count'=>0, 'total_val'=>0, 'sent_count'=>0, 'received_count'=>0, 'cancelled_count'=>0];
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

    /* Stat Cards */
    .po-stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        border: 1px solid var(--border) !important;
        background: var(--card-bg, #ffffff) !important;
    }
    .po-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1) !important;
        border-color: var(--primary) !important;
    }

    /* ---- Premium Tab Pills ---- */
    .po-nav-tabs {
        border-bottom: 2px solid #e2e8f0;
        gap: 8px;
        margin-bottom: 1.5rem;
    }
    .po-nav-tabs .nav-link {
        color: var(--text-muted, #64748b);
        border: none;
        border-radius: 10px 10px 0 0;
        padding: 10px 22px;
        font-weight: 700;
        font-size: 0.92rem;
        background: transparent;
        transition: all 0.2s ease;
        position: relative;
    }
    .po-nav-tabs .nav-link:hover {
        color: #ea580c;
        background: rgba(234, 88, 12, 0.05);
    }
    .po-nav-tabs .nav-link.active {
        color: #ea580c;
        background: #ffffff;
        border-bottom: 3px solid #ea580c;
    }
    .po-nav-tabs .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 3px;
        background: #ea580c;
        border-radius: 3px 3px 0 0;
    }

    /* ---- PO Meta Information Card ---- */
    .po-meta-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    .po-ref-badge-wrapper {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .po-ref-badge {
        font-family: 'JetBrains Mono', 'Consolas', monospace;
        font-weight: 800;
        font-size: 1rem;
        color: #ea580c;
        background: rgba(234, 88, 12, 0.09);
        border: 1.5px solid rgba(234, 88, 12, 0.25);
        padding: 6px 14px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        letter-spacing: 0.5px;
    }
    .btn-po-copy {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        color: #64748b;
        padding: 6px 10px;
        font-size: 0.82rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-po-copy:hover {
        background: #ea580c;
        border-color: #ea580c;
        color: #ffffff;
    }

    /* Quick Date Chips */
    .po-date-chips {
        display: flex;
        gap: 4px;
        margin-top: 6px;
        flex-wrap: wrap;
    }
    .po-date-chip {
        font-size: 0.72rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 2px 7px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .po-date-chip:hover {
        background: #ea580c;
        border-color: #ea580c;
        color: #ffffff;
    }

    /* ---- Items Table Card ---- */
    .po-items-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    .po-items-card-header {
        padding: 16px 20px;
        background: #f8fafc;
        border-bottom: 1.5px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .items-table thead th {
        background: #f1f5f9;
        color: #475569;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 14px;
        border-bottom: 2px solid #e2e8f0;
    }
    .items-table tbody td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .items-table tbody tr:hover {
        background-color: #fffbf7;
    }

    .btn-po-add-row {
        background: #ffffff;
        color: #ea580c;
        border: 2px dashed #ea580c;
        border-radius: 12px;
        padding: 10px 20px;
        font-weight: 700;
        font-size: 0.88rem;
        width: 100%;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-po-add-row:hover {
        background: #fff7ed;
        border-color: #c2410c;
        color: #c2410c;
        transform: translateY(-1px);
    }

    .btn-po-delete-row {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #ef4444;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    .btn-po-delete-row:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
        transform: scale(1.08);
    }

    /* ---- Notes & Quick Tags ---- */
    .po-notes-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        height: 100%;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    .po-note-chip {
        font-size: 0.74rem;
        font-weight: 600;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
        border-radius: 20px;
        padding: 4px 10px;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-block;
    }
    .po-note-chip:hover {
        background: #ffedd5;
        border-color: #fdba74;
        color: #c2410c;
    }

    /* ---- Grand Total Showcase Card ---- */
    .po-grand-total-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    .po-grand-total-val {
        font-size: 2rem;
        font-weight: 900;
        color: #16a34a;
        font-family: 'JetBrains Mono', 'Consolas', monospace;
        letter-spacing: -0.5px;
    }

    /* Action Buttons */
    .btn-po-send {
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%) !important;
        border: none !important;
        color: #ffffff !important;
        border-radius: 10px !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 14px rgba(234, 88, 12, 0.3) !important;
        transition: all 0.2s ease !important;
    }
    .btn-po-send:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(234, 88, 12, 0.4) !important;
    }
    .btn-po-pending {
        background: #d97706 !important;
        border: none !important;
        color: #ffffff !important;
        border-radius: 10px !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 14px rgba(217, 119, 6, 0.25) !important;
        transition: all 0.2s ease !important;
    }
    .btn-po-pending:hover {
        transform: translateY(-2px);
        background: #b45309 !important;
    }
    .btn-po-draft {
        border-radius: 10px !important;
        font-weight: 700 !important;
    }

    [data-theme="dark"] .po-meta-card,
    [data-theme="dark"] .po-items-card,
    [data-theme="dark"] .po-notes-card,
    [data-theme="dark"] .po-grand-total-card {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .po-items-card-header,
    [data-theme="dark"] .items-table thead th {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #94a3b8 !important;
    }
    [data-theme="dark"] .items-table tbody td {
        border-color: #334155 !important;
    }
    [data-theme="dark"] .items-table tbody tr:hover {
        background-color: #24334a !important;
    }
    [data-theme="dark"] .po-nav-tabs {
        border-color: #334155 !important;
    }
    [data-theme="dark"] .po-nav-tabs .nav-link.active {
        background: #1e293b !important;
        color: #f97316 !important;
    }
    [data-theme="dark"] .btn-po-copy {
        background: #334155;
        border-color: #475569;
        color: #cbd5e1;
    }
    [data-theme="dark"] .po-date-chip,
    [data-theme="dark"] .po-note-chip {
        background: #0f172a;
        border-color: #334155;
        color: #94a3b8;
    }

    .unit-display {
        font-size: 0.8rem;
        color: var(--text-muted);
        min-width: 50px;
        text-align: center;
    }

    .subtotal-display {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text);
        white-space: nowrap;
    }

    /* Status badges */
    .badge-draft { background: #6c757d; }
    .badge-sent { background: #0d6efd; }
    .badge-partial { background: #ffc107; color: #000; }
    .badge-received { background: #198754; }
    .badge-cancelled { background: #dc3545; }

    /* Alert */
    .alert-po-success {
        border-left: 4px solid var(--success);
    }

    /* Receive table */
    #receiveItemsTable .form-control {
        font-size: 0.85rem;
        padding: 4px 8px;
    }

    /* Product Search Bar in PO table */
    .po-items-table-wrapper {
        overflow: visible !important;
        position: relative;
        min-height: 220px;
    }
    .po-search-container {
        position: relative;
        width: 100%;
    }
    .po-search-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }
    .po-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted, #94a3b8);
        pointer-events: none;
        font-size: 0.82rem;
        transition: color 0.2s ease;
        z-index: 3;
    }
    .po-product-search-input {
        padding-left: 32px !important;
        padding-right: 32px !important;
        font-size: 0.86rem !important;
        height: 36px !important;
        border-radius: 8px !important;
        border: 1.5px solid var(--border, #e2e8f0) !important;
        background: var(--card-bg, #ffffff) !important;
        color: var(--text, #1e293b) !important;
        transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
        text-overflow: ellipsis;
    }
    .po-product-search-input:focus {
        border-color: var(--primary, #e28743) !important;
        box-shadow: 0 0 0 3px rgba(226, 135, 67, 0.18) !important;
        outline: none !important;
    }
    .po-product-search-input.is-selected {
        font-weight: 600;
        color: var(--text, #0f172a);
        background: var(--input-bg, #f8fafc) !important;
        border-color: var(--border, #cbd5e1) !important;
    }
    .po-clear-search-btn {
        position: absolute;
        right: 7px;
        top: 50%;
        transform: translateY(-50%);
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: none;
        background: rgba(148, 163, 184, 0.25);
        color: var(--text-muted, #64748b);
        font-size: 0.72rem;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
        z-index: 4;
        padding: 0;
    }
    .po-clear-search-btn.is-visible {
        display: flex !important;
    }
    .po-clear-search-btn:hover {
        background: #ef4444;
        color: #ffffff;
        transform: translateY(-50%) scale(1.12);
    }
    .po-clear-search-btn:focus {
        outline: none;
    }
    .po-search-results-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        width: 100%;
        min-width: 320px;
        max-width: 480px;
        max-height: 280px;
        overflow-y: auto;
        background: var(--card-bg, #ffffff);
        border: 1.5px solid var(--border, #e2e8f0);
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.18), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        z-index: 1060;
        padding: 4px 0;
        display: none;
        scrollbar-width: thin;
    }
    .po-search-header-hint {
        padding: 6px 12px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        color: var(--text-muted, #94a3b8);
        background: var(--input-bg, #f8fafc);
        border-bottom: 1px solid var(--border, #e2e8f0);
    }
    .po-search-item {
        padding: 8px 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        border-bottom: 1px solid var(--border-light, #f1f5f9);
        transition: background-color 0.12s ease;
    }
    .po-search-item:last-child {
        border-bottom: none;
    }
    .po-search-item:hover,
    .po-search-item.is-active {
        background-color: rgba(226, 135, 67, 0.12);
    }
    .po-search-item-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
        min-width: 0;
    }
    .po-search-item-title {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text, #1e293b);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .po-search-item-title mark {
        background: #fef08a;
        color: #854d0e;
        font-weight: 700;
        border-radius: 2px;
        padding: 0 2px;
    }
    .po-search-item-meta {
        font-size: 0.74rem;
        color: var(--text-muted, #64748b);
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .po-search-item-meta mark {
        background: #fef08a;
        color: #854d0e;
        font-weight: 700;
        border-radius: 2px;
        padding: 0 2px;
    }
    .po-search-item-price-badge {
        font-weight: 700;
        font-size: 0.82rem;
        color: #16a34a;
        background: #f0fdf4;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid #bbf7d0;
        white-space: nowrap;
    }
    .po-search-empty {
        padding: 14px;
        text-align: center;
        color: var(--text-muted, #94a3b8);
        font-size: 0.82rem;
    }
    /* Custom PO theme components */
    .po-stat-val {
        color: var(--text);
    }
    .po-add-row-cell {
        background: #fafafa;
    }
    .po-total-summary-card,
    .po-filter-bar {
        background: var(--input-bg, #f8fafc);
        border-color: var(--border, #e2e8f0) !important;
    }

    [data-theme="dark"] .po-add-row-cell {
        background: var(--card-bg) !important;
    }
    [data-theme="dark"] .po-stat-card {
        background: var(--card-bg) !important;
        border-color: var(--border) !important;
    }
    [data-theme="dark"] .po-stat-card .stat-card-icon.icon-blue {
        background: rgba(37, 99, 235, 0.2) !important;
        color: #60A5FA !important;
    }
    [data-theme="dark"] .po-stat-card .stat-card-icon.icon-amber {
        background: rgba(217, 119, 6, 0.2) !important;
        color: #FBBF24 !important;
    }
    [data-theme="dark"] .po-stat-card .stat-card-icon.icon-green {
        background: rgba(5, 150, 105, 0.2) !important;
        color: #34D399 !important;
    }
    [data-theme="dark"] .po-stat-card .stat-card-icon.icon-gray {
        background: rgba(148, 163, 184, 0.2) !important;
        color: #CBD5E1 !important;
    }
    [data-theme="dark"] .po-stat-card .text-muted {
        color: #A6947F !important;
    }
    [data-theme="dark"] .po-total-summary-card,
    [data-theme="dark"] .po-filter-bar {
        background: var(--input-bg) !important;
        border-color: var(--border) !important;
    }

    [data-theme="dark"] .po-search-results-dropdown {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    }
    [data-theme="dark"] .po-search-header-hint {
        background: #0f172a;
        border-color: #334155;
        color: #94a3b8;
    }
    [data-theme="dark"] .po-search-item {
        border-color: #334155;
    }
    [data-theme="dark"] .po-search-item:hover,
    [data-theme="dark"] .po-search-item.is-active {
        background-color: rgba(226, 135, 67, 0.22);
    }
    [data-theme="dark"] .po-search-item-title {
        color: #f1f5f9;
    }
    [data-theme="dark"] .po-search-item-title mark,
    [data-theme="dark"] .po-search-item-meta mark {
        background: #a16207;
        color: #fef08a;
    }
    [data-theme="dark"] .po-search-item-price-badge {
        background: rgba(22, 163, 74, 0.15);
        border-color: rgba(22, 163, 74, 0.35);
        color: #4ade80;
    }
    [data-theme="dark"] .po-clear-search-btn {
        background: #334155;
        color: #94a3b8;
    }
    [data-theme="dark"] .po-clear-search-btn:hover {
        background: #ef4444;
        color: #ffffff;
    }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
        <li class="breadcrumb-item active">Purchase Orders</li>
    </ol>
</nav>

<div class="page-header-section mb-3">
    <h2><i class="fas fa-file-invoice"></i> Purchase Orders Management</h2>
</div>

<!-- Quick KPI Summary Cards Bar -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 po-stat-card" id="kpiCardAll" onclick="filterPOTable('')" style="cursor:pointer;" title="Click to view all purchase orders">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-card-icon icon-blue" style="background:#EFF6FF; color:#2563EB; width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.25rem;"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <div class="text-muted fw-bold text-uppercase" style="font-size:0.7rem; letter-spacing:0.5px;">TOTAL ORDERS</div>
                    <div class="fw-bold fs-5 po-stat-val"><?php echo number_format($poStats['total_count']); ?> <small class="fs-6 text-muted" style="font-size:0.75rem;">(₱<?php echo number_format($poStats['total_val'], 2); ?>)</small></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 po-stat-card" id="kpiCardSent" onclick="filterPOTable('Sent')" style="cursor:pointer;" title="Click to filter sent orders">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-card-icon icon-amber" style="background:#EFF6FF; color:#2563EB; width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.25rem;"><i class="fas fa-paper-plane"></i></div>
                <div>
                    <div class="text-muted fw-bold text-uppercase" style="font-size:0.7rem; letter-spacing:0.5px;">SENT (AWAITING DELIVERY)</div>
                    <div class="fw-bold fs-5 po-stat-val text-primary"><?php echo number_format($poStats['sent_count']); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 po-stat-card" id="kpiCardReceived" onclick="filterPOTable('Received')" style="cursor:pointer;" title="Click to filter fulfilled orders">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-card-icon icon-green" style="background:#ECFDF5; color:#059669; width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.25rem;"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="text-muted fw-bold text-uppercase" style="font-size:0.7rem; letter-spacing:0.5px;">FULFILLED (RECEIVED)</div>
                    <div class="fw-bold fs-5 po-stat-val text-success"><?php echo number_format($poStats['received_count']); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 po-stat-card" id="kpiCardCancelled" onclick="filterPOTable('Cancelled')" style="cursor:pointer;" title="Click to filter cancelled orders">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-card-icon icon-gray" style="background:#FEF2F2; color:#DC2626; width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.25rem;"><i class="fas fa-ban"></i></div>
                <div>
                    <div class="text-muted fw-bold text-uppercase" style="font-size:0.7rem; letter-spacing:0.5px;">CANCELLED</div>
                    <div class="fw-bold fs-5 po-stat-val text-danger"><?php echo number_format($poStats['cancelled_count']); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert area -->
<div id="poAlertArea"></div>

<div class="card shadow-sm border-0 rounded-3">
    <div class="card-body">        <!-- Nav Tabs -->
        <ul class="nav po-nav-tabs" id="poTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="create-tab" data-bs-toggle="tab" data-bs-target="#createPane" type="button" role="tab">
                    <i class="fas fa-plus-circle me-1" style="color:#ea580c;"></i> Create PO
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="manage-tab" data-bs-toggle="tab" data-bs-target="#managePane" type="button" role="tab">
                    <i class="fas fa-list me-1 text-primary"></i> Manage POs
                </button>
            </li>
        </ul>

        <div class="tab-content" id="poTabContent">

            <!-- ========== TAB 1: CREATE PO ========== -->
            <div class="tab-pane fade show active" id="createPane" role="tabpanel">
                <form id="createPOForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                    <!-- Top PO Header Information Card -->
                    <div class="po-meta-card mb-4">
                        <div class="row g-3 align-items-start">
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-muted small text-uppercase" style="letter-spacing:0.5px;">PO Reference #</label>
                                <div class="po-ref-badge-wrapper">
                                    <div class="po-ref-badge" id="poNumber">Loading...</div>
                                    <button type="button" class="btn-po-copy" onclick="copyPONumber()" title="Copy PO Number">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <span class="text-muted" style="font-size:0.72rem;">System auto-generated identifier</span>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold d-flex justify-content-between align-items-center" style="font-size:0.84rem;">
                                    <span>Supplier <span class="text-danger">*</span></span>
                                    <a href="suppliers.php" target="_blank" class="text-decoration-none fw-semibold" style="font-size:0.75rem; color:#ea580c;"><i class="fas fa-plus me-1"></i>New</a>
                                </label>
                                <select name="supplierId" id="poSupplierId" class="form-select fw-semibold" required onchange="onSupplierSelected(this)">
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($supplierList as $s): ?>
                                        <option value="<?php echo $s['supplier_id']; ?>" 
                                            data-contact="<?php echo htmlspecialchars($s['contact_person'] ?? '', ENT_QUOTES); ?>"
                                            data-phone="<?php echo htmlspecialchars($s['phone'] ?? '', ENT_QUOTES); ?>"
                                            data-email="<?php echo htmlspecialchars($s['email'] ?? '', ENT_QUOTES); ?>"
                                            data-lead="<?php echo intval($s['lead_time_days'] ?? 0); ?>">
                                            <?php echo htmlspecialchars($s['supplier_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="supplierMetaHint" class="mt-1 small text-muted" style="display:none; font-size:0.75rem;">
                                    <span id="supplierMetaText"></span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold" style="font-size:0.84rem;">Order Date <span class="text-danger">*</span></label>
                                <input type="date" name="orderDate" id="poOrderDate" class="form-control fw-semibold" value="<?php echo date('Y-m-d'); ?>" required>
                                <span class="text-muted" style="font-size:0.72rem;">Issue date for restock order</span>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold" style="font-size:0.84rem;">Expected Delivery</label>
                                <input type="date" name="expectedDate" id="poExpectedDate" class="form-control fw-semibold">
                                <div class="po-date-chips">
                                    <span class="po-date-chip" onclick="setExpectedDays(0)">Today</span>
                                    <span class="po-date-chip" onclick="setExpectedDays(3)">+3 Days</span>
                                    <span class="po-date-chip" onclick="setExpectedDays(7)">+7 Days</span>
                                    <span class="po-date-chip" onclick="setExpectedDays(14)">+14 Days</span>
                                    <span class="po-date-chip" onclick="setExpectedDays(30)">+30 Days</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items Table Card -->
                    <div class="po-items-card mb-4">
                        <div class="po-items-card-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-boxes-stacked fs-5" style="color:#ea580c;"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Order Items &amp; Quantities</h6>
                                    <span class="text-muted small" style="font-size:0.75rem;">Search and add products to this purchase order</span>
                                </div>
                            </div>
                            <span class="badge bg-light text-dark border px-3 py-1 fw-bold rounded-pill" id="poLinesCount">1 Item Line</span>
                        </div>

                        <div class="table-responsive po-items-table-wrapper">
                            <table class="items-table" id="poItems">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width:36%;"><i class="fas fa-search me-1" style="color:#ea580c;"></i> Product Name / SKU</th>
                                        <th scope="col" style="width:12%; text-align:center;">Quantity</th>
                                        <th scope="col" style="width:16%;">Unit</th>
                                        <th scope="col" style="width:16%; text-align:right;">Unit Cost (₱)</th>
                                        <th scope="col" style="width:14%; text-align:right;">Subtotal</th>
                                        <th scope="col" style="width:6%; text-align:center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="poItemsBody">
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>

                        <div class="p-3 bg-light border-top">
                            <button type="button" class="btn-po-add-row" onclick="addPORow()">
                                <i class="fas fa-plus-circle fs-6"></i> Add Another Product Line
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Summary & Actions -->
                    <div class="row g-4 align-items-stretch">
                        <!-- Left: Notes & Instructions -->
                        <div class="col-lg-7">
                            <div class="po-notes-card">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fas fa-comment-dots text-primary"></i>
                                    <label class="form-label fw-bold mb-0" style="font-size:0.88rem;">Notes &amp; Supplier Instructions</label>
                                </div>
                                <textarea name="notes" id="poNotes" class="form-control" rows="3" placeholder="Optional delivery instructions, warehouse dock notes, or payment terms..."></textarea>
                                
                                <div class="mt-2 d-flex flex-wrap gap-1 align-items-center">
                                    <span class="text-muted small me-1" style="font-size:0.73rem;">Quick notes:</span>
                                    <span class="po-note-chip" onclick="addNoteTag('Urgent Delivery - Priority Stock')">+ Urgent Delivery</span>
                                    <span class="po-note-chip" onclick="addNoteTag('Standard 50kg Sack Packaging Required')">+ Standard Packaging</span>
                                    <span class="po-note-chip" onclick="addNoteTag('Please call driver 1 hour before delivery')">+ Call Before Delivery</span>
                                    <span class="po-note-chip" onclick="addNoteTag('Deliver to Main Warehouse Dock A')">+ Main Warehouse</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Grand Total & Action Buttons -->
                        <div class="col-lg-5">
                            <div class="po-grand-total-card d-flex flex-column justify-content-between h-100">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing:0.5px;">Grand Total</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0 small">Gross Amount</span>
                                    </div>
                                    <div class="po-grand-total-val" id="poTotal">₱0.00</div>
                                </div>

                                <div class="pt-3 border-top mt-3 d-flex justify-content-end flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-secondary px-3 py-2 fw-semibold" onclick="resetPOForm()">
                                        <i class="fas fa-rotate-left me-1"></i> Clear Form
                                    </button>
                                    <button type="button" class="btn btn-po-send px-4 py-2 fw-bold" id="btnSendPO" onclick="savePO('sent')">
                                        <i class="fas fa-paper-plane me-1"></i> Send Order
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ========== TAB 2: MANAGE POs ========== -->
            <div class="tab-pane fade" id="managePane" role="tabpanel">

                <!-- Filter Pills -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 p-2 po-filter-bar rounded-3 border">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-bold text-muted" style="font-size:0.82rem;"><i class="fas fa-filter text-primary me-1"></i> Filter Status:</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-bold px-3 py-1 po-filter-pill-btn active" id="filterBtnAll" onclick="filterPOTable('')">All</button>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 py-1 po-filter-pill-btn" id="filterBtnSent" onclick="filterPOTable('Sent')"><i class="fas fa-paper-plane me-1"></i>Sent</button>
                        <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 py-1 po-filter-pill-btn" id="filterBtnReceived" onclick="filterPOTable('Received')"><i class="fas fa-check-circle me-1"></i>Received</button>
                        <button type="button" class="btn btn-sm btn-outline-danger fw-bold px-3 py-1 po-filter-pill-btn" id="filterBtnCancelled" onclick="filterPOTable('Cancelled')"><i class="fas fa-ban me-1"></i>Cancelled</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary fw-bold px-3 py-1" onclick="initPOTable()"><i class="fas fa-rotate me-1"></i> Refresh Table</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="poTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th scope="col">PO #</th>
                                <th scope="col">Supplier</th>
                                <th scope="col">Order Date</th>
                                <th scope="col">Expected</th>
                                <th scope="col" class="text-center">Items</th>
                                <th scope="col" class="text-end">Total</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div><!-- /tab-content -->

    </div><!-- /card-body -->
</div><!-- /card -->

<!-- ========== VIEW PO MODAL ========== -->
<div class="modal fade" id="viewPOModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice text-warning me-2"></i>Purchase Order: <span id="viewPONumber" class="font-monospace"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-3 g-2 p-3 bg-light rounded-3 border">
                    <div class="col-md-4"><strong>Supplier:</strong> <span id="viewSupplier" class="fw-semibold"></span></div>
                    <div class="col-md-4"><strong>Order Date:</strong> <span id="viewOrderDate"></span></div>
                    <div class="col-md-4"><strong>Expected:</strong> <span id="viewExpectedDate"></span></div>
                    <div class="col-md-4"><strong>Status:</strong> <span id="viewStatus"></span></div>
                    <div class="col-md-4"><strong>Created By:</strong> <span id="viewCreatedBy"></span></div>
                    <div class="col-md-4"><strong>Total Amount:</strong> <span id="viewTotal" class="fw-bold text-success"></span></div>
                </div>
                <div class="mb-3" id="viewNotesRow" style="display:none;">
                    <div class="p-2 bg-warning-subtle rounded border border-warning" style="font-size:0.85rem;">
                        <strong>Notes:</strong> <span id="viewNotes"></span>
                    </div>
                </div>
                <div class="table-responsive rounded-3 border">
                    <table class="table table-sm table-striped mb-0" id="viewItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col" class="text-center">Quantity</th>
                                <th scope="col" class="text-center">Unit</th>
                                <th scope="col" class="text-end">Unit Cost</th>
                                <th scope="col" class="text-end">Subtotal</th>
                                <th scope="col" class="text-center">Received</th>
                                <th scope="col" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-primary fw-bold" id="btnModalPrintPO" onclick="printPO($('#viewPOModal').data('po-id'))"><i class="fas fa-print me-1"></i> Print PO Voucher</button>
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========== RECEIVE PO MODAL ========== -->
<div class="modal fade" id="receivePOModal" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width: 95%;">
        <div class="modal-content shadow-lg" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-truck-loading me-2"></i>Receive Purchase Order: <span id="receivePONumber" class="font-monospace"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="receivePOId">
                <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-radius:8px; font-size:0.88rem;">
                    <div><i class="fas fa-building me-2"></i> Supplier: <strong id="receiveSupplier"></strong></div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary"><i class="fas fa-layer-group me-1"></i> Per-Batch Receiving</span>
                        <span class="badge bg-light text-dark border"><i class="fas fa-shield-halved me-1 text-success"></i> Separate Batches (FEFO Tracked)</span>
                    </div>
                </div>
                <div class="p-2 mb-3 bg-light rounded-3 border" style="font-size:0.82rem; color:var(--text-muted);">
                    <i class="fas fa-info-circle text-primary me-1"></i> <strong>Receiving Instructions:</strong> Confirm or enter the actual received quantity, unique batch number, lot number, unit, cost, and expiry date. Click <strong><i class="fas fa-plus text-primary"></i> Split</strong> if a delivery contains multiple batches with different expiry dates. Each confirmed line creates a separate inventory batch and updates available stock in Feeds Inventory &amp; POS.
                </div>
                <div class="table-responsive rounded-3 border mb-3">
                    <table class="table table-sm align-middle mb-0" id="receiveItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="min-width:130px;">Product</th>
                                <th scope="col" style="min-width:130px;">Batch # <span class="text-danger">*</span></th>
                                <th scope="col" style="min-width:110px;">Lot #</th>
                                <th scope="col" class="text-center" style="width:85px;">Ordered</th>
                                <th scope="col" class="text-center" style="width:85px;">Prev. Recv</th>
                                <th scope="col" style="width:115px;">Receive Qty <span class="text-danger">*</span></th>
                                <th scope="col" style="min-width:115px;">Unit</th>
                                <th scope="col" style="width:115px;">Unit Cost (₱) <span class="text-danger">*</span></th>
                                <th scope="col" style="width:135px;">Expiry Date</th>
                                <th scope="col" class="text-center" style="width:90px;">Action</th>
                            </tr>
                        </thead>
                        <tbody><!-- populated by JS --></tbody>
                    </table>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date Received <span class="text-danger">*</span></label>
                        <input type="date" id="receivedDate" class="form-control fw-bold" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Receiving Remarks / Notes</label>
                        <textarea id="receiveNotes" class="form-control" rows="2" placeholder="Optional notes (e.g., delivery receipt no., driver/carrier name, condition of goods)..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success fw-bold px-4" id="btnConfirmReceive" data-loading-text="Processing..." onclick="confirmReceive()">
                    <i class="fas fa-check me-1"></i> Confirm &amp; Add Stock
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script>
var productList = <?php echo json_encode($productList); ?>;
window.sackSizeKg = <?php echo json_encode(getSackSizeKg($connect)); ?>;
</script>
<script src="custom/js/purchase_order.js?v=<?= time() ?>"></script>
