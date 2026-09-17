<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('stock_movement');
require_once 'includes/header_sidebar.php';
?>
<?php require_once 'php_action/unit_helper.php'; ?>
<?php require_once 'php_action/inventory_service.php'; ?>

<?php
// Fetch active products for dropdowns with full metadata (category, brand, image, type, units)
$availabilitySql = inventoryAvailabilitySql('p');
$products = $connect->query(
    "SELECT p.product_id, p.product_name, p.unit_type, p.rate, p.original_price, p.sold_by_weight,
            p.base_unit, p.purchase_unit, p.conversion_factor, p.sack_size_kg,
            p.expiry_tracking, p.product_type, p.feed_type, p.product_image,
            c.categories_name, b.brand_name,
            {$availabilitySql} AS available_quantity
     FROM product p
     LEFT JOIN categories c ON c.categories_id = p.categories_id
     LEFT JOIN brands b ON b.brand_id = p.brand_id
     WHERE p.status = 1 AND p.active = 1
     ORDER BY p.product_name"
);
$productList = [];
while ($p = $products->fetch_assoc()) { $productList[] = $p; }

// Fetch active suppliers
$suppliers = $connect->query("SELECT supplier_id, supplier_name FROM suppliers WHERE status = 1 ORDER BY supplier_name");
$supplierList = [];
while ($s = $suppliers->fetch_assoc()) { $supplierList[] = $s; }

// Physical Count tab: only expiry/batch-tracked products go through
// inventory_service.php's recountInventory() (it always adjusts into a
// batch — see php_action/recountProduct.php's comment), so the dropdown is
// scoped to those products only rather than reusing the full $productList.
$recountEligible = $connect->query("SELECT product_id, product_name FROM product WHERE status = 1 AND expiry_tracking = 1 ORDER BY product_name");
$recountProductList = [];
while ($rp = $recountEligible->fetch_assoc()) { $recountProductList[] = $rp; }
$isAdminForRecount = intval($_SESSION['userId'] ?? 0) === 1 || ($_SESSION['user_role'] ?? '') === 'admin';
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
    .tab-content {
        padding-top: 1.25rem;
    }

    /* Line items table */
    .items-table {
        width: 100%;
        border-collapse: collapse;
    }
    .items-table thead th {
        background: var(--input-bg);
        color: var(--text-muted);
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 8px 10px;
        border-bottom: 2px solid var(--border);
    }
    .items-table tbody td {
        padding: 6px 8px;
        border-bottom: 1px solid var(--border-light);
        vertical-align: middle;
    }
    .items-table .form-control,
    .items-table .form-select {
        font-size: 0.85rem;
        padding: 4px 8px;
    }
    .items-table .btn-remove-row {
        color: var(--danger);
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        padding: 4px 8px;
        border-radius: 4px;
        transition: var(--transition);
    }
    .items-table .btn-remove-row:hover {
        background: rgba(198, 40, 40, 0.1);
    }

    /* Total display */
    .total-display {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--primary);
    }

    /* Reference number */
    .ref-number {
        font-family: monospace;
        font-weight: 600;
        color: var(--primary);
        background: var(--primary-light);
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    [data-theme="dark"] .ref-number {
        background: rgba(232, 163, 23, 0.15);
    }

    /* Unit Selection Cards Grid & Cards */
    .unit-pill-grid {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 12px !important;
        margin-bottom: 8px;
    }
    .unit-pill-card {
        flex: 1 1 130px;
        max-width: 220px;
        min-width: 125px;
        border: 2px solid var(--border, #cbd5e1);
        border-radius: 12px;
        padding: 10px 14px;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        display: flex;
        align-items: center;
        gap: 10px;
        user-select: none;
    }
    .unit-pill-card:hover {
        border-color: #ea580c;
        background: #fff7ed;
        transform: translateY(-1px);
    }
    .unit-pill-card.active, .unit-pill-card.active-vitamin {
        border-color: #ea580c !important;
        background: #fff7ed !important;
        box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.18) !important;
    }
    .unit-pill-card.active .unit-pill-title, .unit-pill-card.active-vitamin .unit-pill-title {
        color: #c2410c !important;
        font-weight: 800 !important;
    }
    .unit-pill-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
        color: #64748b;
    }
    .unit-pill-card.active .unit-pill-icon, .unit-pill-card.active-vitamin .unit-pill-icon {
        background: #ffedd5 !important;
        color: #ea580c !important;
    }
    .unit-pill-title {
        font-size: 0.86rem;
        font-weight: 700;
        color: var(--text, #1e293b);
        line-height: 1.2;
    }
    .unit-pill-sub {
        font-size: 0.74rem;
        color: var(--text-muted, #64748b);
    }

    /* Product Meta Header Box */
    .prod-meta-box {
        background: var(--input-bg, #f8fafc);
        border: 1px solid var(--border, #e2e8f0);
        border-radius: 10px;
        padding: 8px 14px;
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .prod-meta-item {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.82rem;
    }
    .prod-meta-lbl {
        font-weight: 700;
        color: var(--text-muted, #64748b);
    }
    .prod-meta-val {
        font-weight: 700;
        color: var(--text, #0f172a);
    }

    /* Badges */
    .badge-feed {
        background: rgba(16, 185, 129, 0.15);
        color: #059669;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 700;
        white-space: nowrap;
    }
    .badge-vitamin {
        background: rgba(13, 110, 253, 0.15);
        color: #2563eb;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 700;
        white-space: nowrap;
    }
    .badge-other {
        background: rgba(108, 117, 125, 0.15);
        color: #475569;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 700;
        white-space: nowrap;
    }

    /* Filter section */
    .filter-section {
        background: var(--card-bg);
        border: 1px solid var(--border-light);
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 1rem;
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

    /* Success alert */
    .alert-stock-success {
        border-left: 4px solid var(--success);
    }

    /* Product Typeahead Search Bar in Stock Movement */
    .sm-search-container {
        position: relative;
        width: 100%;
    }
    .sm-search-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }
    .sm-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
        font-size: 0.88rem;
        z-index: 3;
    }
    .sm-product-search-input {
        padding-left: 36px !important;
        padding-right: 34px !important;
        font-size: 0.9rem !important;
        height: 40px !important;
        border-radius: 10px !important;
        border: 1.5px solid var(--border, #e2e8f0) !important;
        background: var(--card-bg, #ffffff) !important;
        color: var(--text, #1e293b) !important;
        transition: all 0.2s ease;
        text-overflow: ellipsis;
    }
    .sm-product-search-input:focus {
        border-color: #ea580c !important;
        box-shadow: 0 0 0 3.5px rgba(234, 88, 12, 0.15) !important;
        outline: none !important;
    }
    .sm-product-search-input.is-selected {
        font-weight: 700;
        color: #0f172a;
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;
    }
    .sm-clear-search-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: none;
        background: rgba(148, 163, 184, 0.25);
        color: #64748b;
        font-size: 0.75rem;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
        z-index: 4;
        padding: 0;
    }
    .sm-clear-search-btn.is-visible {
        display: flex !important;
    }
    .sm-clear-search-btn:hover {
        background: #ef4444;
        color: #ffffff;
        transform: translateY(-50%) scale(1.12);
    }
    .sm-search-results-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        width: 100%;
        min-width: 320px;
        max-width: 520px;
        max-height: 290px;
        overflow-y: auto;
        background: var(--card-bg, #ffffff);
        border: 1.5px solid var(--border, #e2e8f0);
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
        z-index: 1060;
        padding: 4px 0;
        display: none;
        scrollbar-width: thin;
    }
    .sm-search-header-hint {
        padding: 6px 12px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        color: var(--text-muted, #94a3b8);
        background: var(--input-bg, #f8fafc);
        border-bottom: 1px solid var(--border, #e2e8f0);
    }
    .sm-search-item {
        padding: 9px 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        border-bottom: 1px solid var(--border-light, #f1f5f9);
        transition: background-color 0.12s ease;
    }
    .sm-search-item:last-child {
        border-bottom: none;
    }
    .sm-search-item:hover,
    .sm-search-item.is-active {
        background-color: rgba(234, 88, 12, 0.1);
    }
    .sm-search-item-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
        min-width: 0;
    }
    .sm-search-item-title {
        font-weight: 700;
        font-size: 0.88rem;
        color: var(--text, #1e293b);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sm-search-item-title mark {
        background: #fef08a;
        color: #854d0e;
        font-weight: 700;
        border-radius: 2px;
        padding: 0 2px;
    }
    .sm-search-item-meta {
        font-size: 0.75rem;
        color: var(--text-muted, #64748b);
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .sm-search-item-price-badge {
        font-weight: 700;
        font-size: 0.82rem;
        color: #16a34a;
        background: #f0fdf4;
        padding: 2px 7px;
        border-radius: 6px;
        border: 1px solid #bbf7d0;
        white-space: nowrap;
    }
    .sm-search-empty {
        padding: 16px;
        text-align: center;
        color: var(--text-muted, #94a3b8);
        font-size: 0.84rem;
    }

    [data-theme="dark"] .sm-search-results-dropdown {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    }
    [data-theme="dark"] .sm-search-header-hint {
        background: #0f172a;
        border-color: #334155;
        color: #94a3b8;
    }
    [data-theme="dark"] .sm-search-item {
        border-color: #334155;
    }
    [data-theme="dark"] .sm-search-item:hover,
    [data-theme="dark"] .sm-search-item.is-active {
        background-color: rgba(234, 88, 12, 0.2);
    }
    [data-theme="dark"] .sm-search-item-title {
        color: #f1f5f9;
    }
    [data-theme="dark"] .sm-search-item-title mark {
        background: #a16207;
        color: #fef08a;
    }
    [data-theme="dark"] .sm-search-item-price-badge {
        background: rgba(22, 163, 74, 0.15);
        border-color: rgba(22, 163, 74, 0.35);
        color: #4ade80;
    }
    [data-theme="dark"] .sm-clear-search-btn {
        background: #334155;
        color: #94a3b8;
    }
    [data-theme="dark"] .sm-clear-search-btn:hover {
        background: #ef4444;
        color: #ffffff;
    }

    /* Action Buttons Contrast & Visibility */
    #btnSaveStockOut,
    #btnSaveStockIn {
        color: #ffffff !important;
        font-weight: 700 !important;
        transition: all 0.2s ease !important;
    }
    #btnSaveStockOut i,
    #btnSaveStockIn i {
        color: #ffffff !important;
    }
    #btnSaveStockOut {
        background-color: #c2410c !important;
        border-color: #c2410c !important;
    }
    #btnSaveStockOut:hover,
    #btnSaveStockOut:focus,
    #btnSaveStockOut:active {
        background-color: #9a3412 !important;
        border-color: #9a3412 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(194, 65, 12, 0.4) !important;
    }
    #btnSaveStockIn {
        background-color: #ea580c !important;
        border-color: #ea580c !important;
    }
    #btnSaveStockIn:hover,
    #btnSaveStockIn:focus,
    #btnSaveStockIn:active {
        background-color: #c2410c !important;
        border-color: #c2410c !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.4) !important;
    }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
        <li class="breadcrumb-item active">Stock In / Out</li>
    </ol>
</nav>

<div class="page-header-section">
    <h2><i class="fas fa-exchange-alt"></i> Stock In / Out</h2>
</div>

<!-- Alert area -->
<div id="stockAlertArea"></div>

<div class="card">
    <div class="card-body">

        <!-- Nav Tabs -->
        <ul class="nav nav-tabs" id="stockTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="stockin-tab" data-bs-toggle="tab" data-bs-target="#stockInPane" type="button" role="tab">
                    <i class="fas fa-arrow-down me-1 text-success"></i> Stock In
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stockout-tab" data-bs-toggle="tab" data-bs-target="#stockOutPane" type="button" role="tab">
                    <i class="fas fa-arrow-up me-1 text-danger"></i> Stock Out
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#historyPane" type="button" role="tab">
                    <i class="fas fa-history me-1"></i> Movement History
                </button>
            </li>
            <?php if ($isAdminForRecount): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="recount-tab" data-bs-toggle="tab" data-bs-target="#recountPane" type="button" role="tab">
                    <i class="fas fa-clipboard-check me-1 text-warning"></i> Physical Count
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <div class="tab-content" id="stockTabContent">

            <!-- ========== TAB 1: STOCK IN ========== -->
            <div class="tab-pane fade show active" id="stockInPane" role="tabpanel">
                <form id="stockInForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <!-- Header Inputs -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size:0.88rem;">Reference #</label>
                            <div class="ref-number px-3 py-2 fw-bold" id="siRefNo" style="background:#ffedd5; color:#c2410c; border-radius:10px; font-size:0.95rem;">SI-<?php echo date('Ymd'); ?>-001</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size:0.88rem;">Date Arrived <span class="text-danger">*</span></label>
                            <input type="date" name="movementDate" class="form-control fw-semibold" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size:0.88rem;">Supplier <span class="text-danger">*</span></label>
                            <select name="supplierId" class="form-select fw-semibold" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($supplierList as $s): ?>
                                    <option value="<?php echo $s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size:0.88rem;">Purchase Order</label>
                            <select name="poId" class="form-select fw-semibold">
                                <option value="">None (Direct Entry)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Stock In Line Items Container -->
                    <div id="siItemsContainer">
                        <!-- Dynamic item cards rendered by JS -->
                    </div>

                    <div class="mb-4">
                        <button type="button" class="btn btn-outline-primary fw-bold btn-sm px-3" onclick="addStockInRow()">
                            <i class="fas fa-plus me-1"></i> Add Another Item
                        </button>
                    </div>

                    <!-- Footer Totals & Notes -->
                    <div class="row g-3 align-items-center mb-4">
                        <div class="col-md-7">
                            <label class="form-label fw-bold" style="font-size:0.88rem;">General Remarks / Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional delivery remarks, invoice number, or overall notes..."></textarea>
                        </div>
                        <div class="col-md-5 text-end">
                            <div class="summary-total-box shadow-sm mb-3" style="border-radius:14px;">
                                <div class="text-muted fw-semibold" style="font-size:0.85rem;">Grand Total:</div>
                                <div class="total-display text-primary fw-extrabold" id="siTotalCost" style="font-size:1.6rem;">₱0.00</div>
                            </div>
                            <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm" id="btnSaveStockIn" style="background:#ea580c; color:#ffffff !important; border:none; font-size:1.05rem;">
                                <i class="fas fa-check me-1"></i> Save Stock In
                            </button>
                        </div>
                    </div>


                </form>
            </div>

            <!-- ========== TAB 2: STOCK OUT ========== -->
            <div class="tab-pane fade" id="stockOutPane" role="tabpanel">
                <form id="stockOutForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <!-- Header Inputs -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size:0.88rem;">Reference #</label>
                            <div class="ref-number px-3 py-2 fw-bold" id="soRefNo" style="background:#ffedd5; color:#c2410c; border-radius:10px; font-size:0.95rem;">SO-<?php echo date('Ymd'); ?>-001</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size:0.88rem;">Date <span class="text-danger">*</span></label>
                            <input type="date" name="movementDate" class="form-control fw-semibold" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size:0.88rem;">Reason / Type <span class="text-danger">*</span></label>
                            <select name="reason" class="form-select fw-semibold" required>
                                <option value="">Select Reason</option>
                                <option value="sale">Sale (POS Order)</option>
                                <option value="damaged">Damaged / Spoilage</option>
                                <option value="expired">Expired Stock Write-off</option>
                                <option value="return">Supplier Return</option>
                                <option value="adjustment">Stock Adjustment</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold" style="font-size:0.88rem;">Customer / Recipient</label>
                            <input type="text" name="recipient" class="form-control fw-semibold" placeholder="Walk-in Customer / Department" value="Walk-in Customer">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.88rem;">Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional remarks, reason for issue, customer name, or details..."></textarea>
                    </div>

                    <!-- Item(s) to be issued Table Header -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0" style="font-size:0.95rem; color:var(--text);">Item(s) to be issued</h6>
                        <button type="button" class="btn btn-outline-danger btn-sm fw-bold" onclick="addStockOutRow()">
                            <i class="fas fa-plus me-1"></i> Add Item
                        </button>
                    </div>

                    <!-- Stock Out Line Items Table -->
                    <div class="table-responsive mb-3 border rounded-3" style="border-radius:12px !important; overflow:hidden;">
                        <table class="items-table mb-0" id="stockOutItems">
                            <thead>
                                <tr>
                                    <th scope="col" style="width:30%;">PRODUCT</th>
                                    <th scope="col" style="width:18%;">BATCH / LOT #</th>
                                    <th scope="col" style="width:12%;">AVAILABLE</th>
                                    <th scope="col" style="width:12%;">UNIT</th>
                                    <th scope="col" style="width:10%;">QUANTITY</th>
                                    <th scope="col" style="width:10%;">UNIT COST</th>
                                    <th scope="col" style="width:10%;">SUBTOTAL</th>
                                    <th scope="col" style="width:4%;"></th>
                                </tr>
                            </thead>
                            <tbody id="soItemsBody">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-danger btn-sm fw-bold" onclick="addStockOutRow()">
                            <i class="fas fa-plus me-1"></i> Add Another Item
                        </button>

                        <div class="summary-total-box shadow-sm px-4 py-2 border rounded-3 ms-auto" style="min-width:240px; border-radius:12px !important;">
                            <div class="d-flex justify-content-between text-muted" style="font-size:0.82rem;">
                                <span>Total Items:</span>
                                <strong id="soTotalItemsCount">0</strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted" style="font-size:0.82rem;">
                                <span>Total Quantity:</span>
                                <strong id="soTotalQuantityCount">0</strong>
                            </div>
                            <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                <span class="fw-bold" style="font-size:0.9rem;">Grand Total:</span>
                                <strong class="text-danger fw-extrabold" id="soTotalCost" style="font-size:1.3rem;">₱0.00</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Banners -->
                    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 p-3" style="border-radius:12px; background:rgba(16, 185, 129, 0.1); color:var(--text);">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-link text-success fs-5"></i>
                            <div style="font-size:0.85rem;">
                                <strong>Connected to Stock In:</strong> These items will be deducted from the available stock based on FIFO (by earliest date received).
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success fw-bold" onclick="$('#history-tab').click();">
                            <i class="fas fa-history me-1"></i> View Stock In History
                        </button>
                    </div>

                    <div class="alert alert-info border-0 d-flex align-items-center gap-2 mb-4 p-3" style="border-radius:12px; background:rgba(13, 110, 253, 0.08); color:var(--text); font-size:0.85rem;">
                        <i class="fas fa-info-circle text-primary fs-5"></i>
                        <span>Stock will be deducted from inventory and recorded in Movement History as <strong>STOCK OUT</strong> after saving.</span>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" onclick="$('#stockOutForm')[0].reset(); $('#soItemsBody').empty(); addStockOutRow();">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger px-4 py-2 fw-bold shadow-sm" id="btnSaveStockOut" style="background:#c2410c; color:#ffffff !important; border:none; font-size:1.05rem;">
                            <i class="fas fa-check me-1"></i> Save Stock Out
                        </button>
                    </div>
                </form>
            </div>

            <!-- ========== TAB 3: MOVEMENT HISTORY ========== -->
            <div class="tab-pane fade" id="historyPane" role="tabpanel">

                <!-- Filters -->
                <div class="filter-section">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size:0.8rem;">Date From</label>
                            <input type="date" id="filterDateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size:0.8rem;">Date To</label>
                            <input type="date" id="filterDateTo" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold" style="font-size:0.8rem;">Type</label>
                            <select id="filterType" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="IN">Stock In</option>
                                <option value="OUT">Stock Out</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="applyFilters()">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="movementHistoryTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Reference #</th>
                                <th scope="col">Type</th>
                                <th scope="col">Product(s)</th>
                                <th scope="col">Total Qty</th>
                                <th scope="col">Supplier / Reason</th>
                                <th scope="col">Total Cost</th>
                                <th scope="col">User</th>
                                <th scope="col">Created</th>
                            </tr>
                        </thead>
                    </table>
                </div>

            </div>

            <?php if ($isAdminForRecount): ?>
            <!-- ========== TAB 4: PHYSICAL COUNT (RECOUNT) ========== -->
            <div class="tab-pane fade" id="recountPane" role="tabpanel">
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-3 mb-4" style="border-radius:12px; background:rgba(13, 110, 253, 0.08); color:var(--text);">
                    <i class="fas fa-info-circle text-primary fs-4 flex-shrink-0"></i>
                    <div style="font-size:0.88rem; line-height:1.45;">
                        Reconcile the system-tracked quantity against a physical stock count. Only available for expiry/batch-tracked products (medicines, vaccines, vitamins, feeds). The adjustment is recorded as a <strong>RECOUNT</strong> movement in the ledger.
                    </div>
                </div>

                <form id="recountForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <div class="row g-4">
                        <!-- Left Panel (Form Inputs) -->
                        <div class="col-lg-7">
                            <!-- Product Selector -->
                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size:0.88rem;">Product <span class="text-danger">*</span></label>
                                <select id="recountProductId" name="productId" class="form-select fw-semibold" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($recountProductList as $rp): ?>
                                        <option value="<?php echo intval($rp['product_id']); ?>"><?php echo htmlspecialchars($rp['product_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($recountProductList)): ?>
                                    <small class="text-muted d-block mt-1">No expiry/batch-tracked products found.</small>
                                <?php endif; ?>
                            </div>

                            <!-- Current System Stock & Batch Info Card -->
                            <div id="recountInfoBox" class="mb-3" style="display:none;">
                                <div class="p-3 border rounded-3" style="background:var(--border-lightest, rgba(0,0,0,0.02)); border-radius:12px;">
                                    <div class="row g-3 text-center text-md-start">
                                        <div class="col-6 col-md-3">
                                            <div class="text-muted" style="font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:.03em;">Current System Stock</div>
                                            <div class="fw-extrabold text-success" style="font-size:1.35rem; line-height:1.2;" id="recountSystemQty">--</div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-muted" style="font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:.03em;">Unit</div>
                                            <div class="fw-bold" style="font-size:1.05rem; color:var(--text);" id="recountUnitLabel">--</div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-muted" style="font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:.03em;">Batch / Lot No.</div>
                                            <div class="fw-bold" style="font-size:0.98rem; color:var(--text);" id="recountBatchNo">--</div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-muted" style="font-size:0.75rem; text-transform:uppercase; font-weight:700; letter-spacing:.03em;">Expiry Date</div>
                                            <div class="fw-bold" style="font-size:0.98rem; color:var(--text);" id="recountExpiryDate">--</div>
                                        </div>
                                    </div>
                                </div>
                                <div id="recountNoBatchWarning" class="alert alert-warning py-2 mt-2" style="font-size:0.82rem; display:none;">
                                    <i class="fas fa-exclamation-triangle me-1"></i> This product has no active batch to adjust — a physical count cannot be recorded until stock is received.
                                </div>
                            </div>

                            <!-- Physical Count Input -->
                            <div class="mb-3">
                                <label for="recountPhysicalCount" class="form-label fw-bold" style="font-size:0.88rem;">Physical Count <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control fw-bold" id="recountPhysicalCount" name="physicalCount" placeholder="0" required disabled style="font-size:1.1rem;">
                                    <span class="input-group-text text-muted" id="recountInputUnitAddon">units</span>
                                </div>
                                <div class="form-text" style="font-size:0.78rem;">Enter the actual quantity counted on the shelf/stockroom.</div>
                            </div>

                            <!-- Visual Comparison Cards Box -->
                            <div id="recountDeltaPreview" class="mb-3 p-3 border rounded-3" style="display:none; background:rgba(13, 110, 253, 0.03); border-radius:12px;">
                                <div class="row text-center align-items-center">
                                    <div class="col-4 border-end">
                                        <div class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">System Stock</div>
                                        <div class="fw-extrabold text-primary" style="font-size:1.35rem;" id="compSystemStock">--</div>
                                    </div>
                                    <div class="col-4 border-end">
                                        <div class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">Physical Count</div>
                                        <div class="fw-extrabold text-success" style="font-size:1.35rem;" id="compPhysicalCount">--</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">Difference</div>
                                        <div class="fw-extrabold" style="font-size:1.35rem;" id="recountDeltaValue">--</div>
                                        <div class="text-muted" style="font-size:0.68rem;">(Physical - System)</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Purpose Select -->
                            <div class="mb-3">
                                <label for="recountPurpose" class="form-label fw-bold" style="font-size:0.88rem;">Purpose <span class="text-danger">*</span></label>
                                <select id="recountPurpose" name="purpose" class="form-select fw-semibold" required disabled>
                                    <option value="Missing Stock">Missing Stock</option>
                                    <option value="Damage / Spoilage">Damage / Spoilage</option>
                                    <option value="Physical Count Audit">Physical Count Audit</option>
                                    <option value="Surplus / Found Stock">Surplus / Found Stock</option>
                                    <option value="Expired Stock">Expired Stock</option>
                                    <option value="Other Discrepancy">Other Discrepancy</option>
                                </select>
                                <div class="form-text" style="font-size:0.78rem;">Select the reason/purpose of this physical count.</div>
                            </div>

                            <!-- Notes Textarea -->
                            <div class="mb-3">
                                <label for="recountNotes" class="form-label fw-bold" style="font-size:0.88rem;">Notes</label>
                                <textarea id="recountNotes" name="notes" class="form-control" rows="3" placeholder="Optional remarks, reason for discrepancy, or other details..."></textarea>
                                <div class="form-text" style="font-size:0.78rem;">Optional remarks, reason for discrepancy, or other details...</div>
                            </div>
                        </div>

                        <!-- Right Panel (Recount Summary & Confirmation) -->
                        <div class="col-lg-5">
                            <!-- Recount Summary Card -->
                            <div class="card border shadow-sm mb-3" style="border-radius:14px; overflow:hidden;">
                                <div class="card-header bg-light py-3 px-3 border-bottom d-flex align-items-center gap-2">
                                    <i class="fas fa-clipboard-list text-warning fs-5"></i>
                                    <h6 class="mb-0 fw-bold" style="font-size:0.95rem; color:var(--text);">Recount Summary</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-borderless mb-0" style="font-size:0.88rem;">
                                        <tbody>
                                            <tr class="border-bottom">
                                                <td class="ps-3 py-2 text-muted fw-semibold" style="width:40%;">Product</td>
                                                <td class="pe-3 py-2 fw-bold text-end" id="sumProduct">--</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="ps-3 py-2 text-muted fw-semibold">Batch / Lot No.</td>
                                                <td class="pe-3 py-2 fw-bold text-end" id="sumBatch">--</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="ps-3 py-2 text-muted fw-semibold">Expiry Date</td>
                                                <td class="pe-3 py-2 fw-bold text-end" id="sumExpiry">--</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="ps-3 py-2 text-muted fw-semibold">System Stock</td>
                                                <td class="pe-3 py-2 fw-bold text-end" id="sumSystemQty">--</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="ps-3 py-2 text-muted fw-semibold">Physical Count</td>
                                                <td class="pe-3 py-2 fw-bold text-end" id="sumPhysicalQty">--</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="ps-3 py-2 text-muted fw-semibold">Difference</td>
                                                <td class="pe-3 py-2 fw-bold text-end" id="sumDelta">--</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="ps-3 py-2 text-muted fw-semibold">Purpose</td>
                                                <td class="pe-3 py-2 fw-bold text-end" id="sumPurpose">Missing Stock</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="ps-3 py-2 text-muted fw-semibold">Notes</td>
                                                <td class="pe-3 py-2 fw-semibold text-end text-break" style="max-width:180px;" id="sumNotes">--</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="ps-3 py-2 text-muted fw-semibold">Counted By</td>
                                                <td class="pe-3 py-2 fw-bold text-end" id="sumCountedBy"><?php echo htmlspecialchars($_SESSION['username'] ?? 'admin'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-3 py-2 text-muted fw-semibold">Date & Time</td>
                                                <td class="pe-3 py-2 fw-bold text-end" id="sumDateTime"><?php echo date('M j, Y h:i A'); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Info Notice Box -->
                            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start gap-2 mb-3 p-3" style="border-radius:12px; background:rgba(245, 158, 11, 0.12); color:var(--text); font-size:0.83rem; line-height:1.45;">
                                <i class="fas fa-lightbulb text-warning fs-5 flex-shrink-0 mt-1"></i>
                                <div><strong>Note:</strong> After confirmation, the system will adjust the stock quantity and record a <strong>RECOUNT</strong> movement in the ledger.</div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" onclick="resetRecountForm()">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-warning px-4 fw-bold shadow-sm" id="btnSaveRecount" disabled>
                                    <i class="fas fa-check me-1"></i> Confirm & Submit Recount
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

        </div><!-- /tab-content -->

    </div><!-- /card-body -->
</div><!-- /card -->

<!-- Pass product data to JS -->
<?php require_once 'includes/footer_sidebar.php'; ?>

<script>
var productList = <?php echo json_encode($productList); ?>;
window.sackSizeKg = <?php echo json_encode(getSackSizeKg($connect)); ?>;
</script>
<script src="custom/js/stock_movement.js?v=<?= time() ?>"></script>
