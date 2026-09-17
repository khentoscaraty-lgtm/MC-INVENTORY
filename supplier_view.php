<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('purchase_order');
require_once 'includes/header_sidebar.php';
?>

<?php
$supplierId = intval($_GET['id'] ?? 0);
if (!$supplierId) { header('Location: supplier.php'); exit; }

$stmt = $connect->prepare("SELECT * FROM suppliers WHERE supplier_id = ? AND status = 1");
$stmt->bind_param("i", $supplierId);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$supplier) { header('Location: supplier.php'); exit; }

// Build star rating HTML
$ratingHtml = '';
$rating = intval($supplier['rating']);
for ($i = 1; $i <= 5; $i++) {
    $ratingHtml .= ($i <= $rating) ? '<i class="fas fa-star text-warning"></i>' : '<i class="fas fa-star text-muted"></i>';
}

// ── Purchase History (12.8): the PO module has since shipped — wire the
// previously-hardcoded "PO module not active yet" placeholder to real data
// instead of leaving a stale message. Mirrors the query pattern already
// used by php_action/fetchPurchaseOrders.php, scoped to this supplier.
$poStmt = $connect->prepare("
    SELECT po.po_id, po.po_number, po.order_date, po.expected_date, po.status, po.total_amount,
           (SELECT COUNT(*) FROM purchase_order_items WHERE po_id = po.po_id) AS item_count
    FROM purchase_orders po
    WHERE po.supplier_id = ?
    ORDER BY po.order_date DESC, po.po_id DESC
");
$poStmt->bind_param("i", $supplierId);
$poStmt->execute();
$purchaseOrders = $poStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$poStmt->close();

$poStatusBadges = [
    'draft'     => '<span class="badge bg-secondary">Draft</span>',
    'sent'      => '<span class="badge bg-info text-dark">Sent</span>',
    'partial'   => '<span class="badge bg-warning text-dark">Partial</span>',
    'received'  => '<span class="badge bg-success">Received</span>',
    'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
];

// ── Products Supplied (12.8): sourced from product_batches, which records
// supplier_id per received batch — gives an honest "what has this supplier
// actually shipped us" view rather than a static empty state.
$productsStmt = $connect->prepare("
    SELECT p.product_id, p.product_name, COUNT(pb.batch_id) AS batch_count,
           SUM(pb.qty_received) AS total_received, MAX(pb.date_received) AS last_received
    FROM product_batches pb
    INNER JOIN product p ON p.product_id = pb.product_id
    WHERE pb.supplier_id = ?
    GROUP BY p.product_id, p.product_name
    ORDER BY last_received DESC
");
$productsStmt->bind_param("i", $supplierId);
$productsStmt->execute();
$productsSupplied = $productsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$productsStmt->close();

// ── Performance summary: total POs + total spend (excluding cancelled POs,
// which were never fulfilled/paid).
$perfStmt = $connect->prepare("
    SELECT COUNT(*) AS po_count, COALESCE(SUM(total_amount), 0) AS total_spent
    FROM purchase_orders
    WHERE supplier_id = ? AND status != 'cancelled'
");
$perfStmt->bind_param("i", $supplierId);
$perfStmt->execute();
$perf = $perfStmt->get_result()->fetch_assoc();
$perfStmt->close();
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
    .supplier-info-card { margin-bottom: 1.5rem; }
    .supplier-info-card .info-label {
        font-size: .78rem; color: var(--text-muted); text-transform: uppercase;
        letter-spacing: .5px; margin-bottom: 2px;
    }
    .supplier-info-card .info-value {
        font-size: .9rem; color: var(--text); font-weight: 500;
    }
    .nav-tabs .nav-link { color: var(--text-muted); font-size: .85rem; }
    .nav-tabs .nav-link.active { color: var(--primary); font-weight: 600; }
    .empty-state {
        text-align: center; padding: 3rem 1rem; color: var(--text-muted);
    }
    .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; display: block; opacity: .5; }
    .empty-state p { font-size: .9rem; margin: 0; }
    .stat-card {
        text-align: center; padding: 1.5rem;
        border: 1px solid var(--border); border-radius: .5rem;
    }
    .stat-card .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--primary); }
    .stat-card .stat-label { font-size: .78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent p-0 mb-2">
        <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
        <li class="breadcrumb-item"><a href="supplier.php">Suppliers</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($supplier['supplier_name']); ?></li>
    </ol>
</nav>

<div class="page-header">
    <h2><i class="fas fa-truck"></i> <?php echo htmlspecialchars($supplier['supplier_name']); ?></h2>
    <a href="supplier.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Suppliers</a>
</div>

<!-- Supplier Info Card -->
<div class="card supplier-info-card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="info-label">Supplier Name</div>
                <div class="info-value"><?php echo htmlspecialchars($supplier['supplier_name']); ?></div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="info-label">Contact Person</div>
                <div class="info-value"><?php echo htmlspecialchars($supplier['contact_person'] ?: 'N/A'); ?></div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="info-label">Phone</div>
                <div class="info-value"><?php echo htmlspecialchars($supplier['phone'] ?: 'N/A'); ?></div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($supplier['email'] ?: 'N/A'); ?></div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="info-label">Address</div>
                <div class="info-value"><?php echo htmlspecialchars($supplier['address'] ?: 'N/A'); ?></div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="info-label">Rating</div>
                <div class="info-value"><?php echo $ratingHtml; ?></div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="info-label">Lead Time</div>
                <div class="info-value"><?php echo intval($supplier['lead_time_days']); ?> days</div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="info-label">Status</div>
                <div class="info-value"><span class="badge bg-success">Active</span></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="card">
    <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="supplierTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="purchase-tab" data-bs-toggle="tab" data-bs-target="#purchaseHistory" type="button" role="tab" aria-controls="purchaseHistory" aria-selected="true">
                    <i class="fas fa-shopping-cart me-1"></i> Purchase History
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#productsSupplied" type="button" role="tab" aria-controls="productsSupplied" aria-selected="false">
                    <i class="fas fa-boxes me-1"></i> Products Supplied
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab" aria-controls="performance" aria-selected="false">
                    <i class="fas fa-chart-line me-1"></i> Performance
                </button>
            </li>
        </ul>

        <div class="tab-content" id="supplierTabsContent">
            <!-- Purchase History Tab -->
            <div class="tab-pane fade show active" id="purchaseHistory" role="tabpanel" aria-labelledby="purchase-tab">
                <?php if (empty($purchaseOrders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <p>No purchase orders yet</p>
                    <p style="font-size:.78rem;margin-top:.25rem;">Purchase orders placed with this supplier will appear here.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">PO #</th>
                                <th scope="col">Order Date</th>
                                <th scope="col">Expected</th>
                                <th scope="col" class="text-center">Items</th>
                                <th scope="col" class="text-end">Total</th>
                                <th scope="col" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($purchaseOrders as $po): ?>
                            <tr>
                                <td><span class="fw-semibold" style="font-family:monospace;"><?php echo htmlspecialchars($po['po_number']); ?></span></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($po['order_date']))); ?></td>
                                <td><?php echo $po['expected_date'] ? htmlspecialchars(date('M d, Y', strtotime($po['expected_date']))) : '<span class="text-muted">—</span>'; ?></td>
                                <td class="text-center"><?php echo intval($po['item_count']); ?></td>
                                <td class="text-end">₱<?php echo number_format((float)$po['total_amount'], 2); ?></td>
                                <td class="text-center"><?php echo $poStatusBadges[$po['status']] ?? '<span class="badge bg-secondary">' . htmlspecialchars($po['status']) . '</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Products Supplied Tab -->
            <div class="tab-pane fade" id="productsSupplied" role="tabpanel" aria-labelledby="products-tab">
                <?php if (empty($productsSupplied)): ?>
                <div class="empty-state">
                    <i class="fas fa-boxes"></i>
                    <p>No products supplied yet</p>
                    <p style="font-size:.78rem;margin-top:.25rem;">Product batches received from this supplier will appear here.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col" class="text-center">Batches</th>
                                <th scope="col" class="text-end">Total Received</th>
                                <th scope="col">Last Received</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($productsSupplied as $p): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($p['product_name']); ?></td>
                                <td class="text-center"><?php echo intval($p['batch_count']); ?></td>
                                <td class="text-end"><?php echo number_format((float)$p['total_received']); ?></td>
                                <td><?php echo $p['last_received'] ? htmlspecialchars(date('M d, Y', strtotime($p['last_received']))) : '<span class="text-muted">—</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Performance Tab -->
            <div class="tab-pane fade" id="performance" role="tabpanel" aria-labelledby="performance-tab">
                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo number_format(intval($perf['po_count'])); ?></div>
                            <div class="stat-label">Total Purchase Orders</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-value">₱<?php echo number_format((float)$perf['total_spent'], 2); ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo intval($supplier['lead_time_days']); ?> days</div>
                            <div class="stat-label">Avg Lead Time</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script>
$(document).ready(function() {
    $('#navSupplier').addClass('active');
});
</script>
