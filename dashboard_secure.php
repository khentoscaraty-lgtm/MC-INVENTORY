<?php require_once 'includes/header_sidebar.php'; ?>

<?php
global $connect;

// ── Stats ──────────────────────────────────────────────────────────
$stmt = $connect->prepare("
    SELECT
        COUNT(*) AS c,
        COALESCE(SUM(paid),0) AS order_revenue,
        COALESCE(SUM(CASE WHEN payment_status = 2 THEN grand_total ELSE 0 END),0) AS total_due,
        COUNT(CASE WHEN DATE(order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) AS week_c,
        COALESCE(SUM(CASE WHEN DATE(order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN paid ELSE 0 END),0) AS week_rev,
        COUNT(CASE WHEN DATE(order_date) = CURDATE() THEN 1 END) AS today_c,
        COALESCE(SUM(CASE WHEN DATE(order_date) = CURDATE() THEN paid ELSE 0 END),0) AS today_rev,
        COALESCE(SUM(CASE WHEN DATE(order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN paid ELSE 0 END),0) AS yesterday_rev
    FROM orders WHERE order_status = 1
");
$stmt->execute();
$ordersAgg = $stmt->get_result()->fetch_assoc();
$stmt->close();
$countOrder = (int)$ordersAgg['c'];
$orderRevenue = (float)$ordersAgg['order_revenue'];
$totalDue = (float)$ordersAgg['total_due'];
$weekOrders = (int)$ordersAgg['week_c'];
$weekOrderRevenue = (float)$ordersAgg['week_rev'];
$todayOrders = (int)$ordersAgg['today_c'];
$todayOrderRev = (float)$ordersAgg['today_rev'];
$yesterdayOrderRev = (float)$ordersAgg['yesterday_rev'];

// ── Product Stats ───────────────────────────────────────────────────
$stmt = $connect->prepare("
    SELECT
        COUNT(*) AS c,
        SUM(CASE WHEN quantity <= 3 THEN 1 ELSE 0 END) AS low_stock,
        SUM(CASE WHEN active = 1 AND quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock,
        SUM(CASE WHEN active = 1 AND quantity <= reorder_level THEN 1 ELSE 0 END) AS reorder
    FROM product WHERE status = 1
");
$stmt->execute();
$productAgg = $stmt->get_result()->fetch_assoc();
$stmt->close();
$countProduct = (int)$productAgg['c'];
$countLowStock = (int)$productAgg['low_stock'];
$countOutOfStock = (int)$productAgg['out_of_stock'];
$reorderCount = (int)$productAgg['reorder'];

// ── Transaction Stats (POS Sales - Refunds) ─────────────────────────
$stmt = $connect->prepare("
    SELECT
        COUNT(*) AS c,
        COALESCE(SUM(t.total_payable - COALESCE(ret.refunded, 0)), 0) AS tx_revenue,
        COUNT(CASE WHEN DATE(t.transaction_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) AS week_c,
        COALESCE(SUM(CASE WHEN DATE(t.transaction_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN (t.total_payable - COALESCE(ret.refunded, 0)) ELSE 0 END), 0) AS week_rev,
        COUNT(CASE WHEN DATE(t.transaction_date) = CURDATE() THEN 1 END) AS today_c,
        COALESCE(SUM(CASE WHEN DATE(t.transaction_date) = CURDATE() THEN (t.total_payable - COALESCE(ret.refunded, 0)) ELSE 0 END), 0) AS today_rev,
        COALESCE(SUM(CASE WHEN DATE(t.transaction_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN (t.total_payable - COALESCE(ret.refunded, 0)) ELSE 0 END), 0) AS yesterday_rev
    FROM transactions t
    LEFT JOIN (
        SELECT transaction_id, SUM(refund_amount) AS refunded
        FROM sales_returns WHERE status = 'completed' GROUP BY transaction_id
    ) ret ON ret.transaction_id = t.transaction_id
    WHERE t.transaction_status = 1
");
$stmt->execute();
$txAgg = $stmt->get_result()->fetch_assoc();
$stmt->close();

$countTransactions = (int)$txAgg['c'];
$txRevenue = (float)$txAgg['tx_revenue'];
$weekTransactions = (int)$txAgg['week_c'];
$weekTxRevenue = (float)$txAgg['week_rev'];
$todayTransactions = (int)$txAgg['today_c'];
$todayTxRevenue = (float)$txAgg['today_rev'];
$yesterdayTxRev = (float)$txAgg['yesterday_rev'];

// Combined revenue & counts
$totalRevenue = $orderRevenue + $txRevenue;
$weekRevenue = $weekOrderRevenue + $weekTxRevenue;
$todayRevenue = $todayOrderRev + $todayTxRevenue;
$totalSalesCount = $countTransactions + $countOrder;
$weekTotalSalesCount = $weekTransactions + $weekOrders;
$todayTotalCount = $todayOrders + $todayTransactions;
$yesterdayRevenue = $yesterdayOrderRev + $yesterdayTxRev;

// ── Daily data (7 days) — orders + transactions combined ─────────
$stmt = $connect->prepare("
    SELECT DATE(order_date) AS d, COUNT(*) AS c, COALESCE(SUM(paid),0) AS rev
    FROM orders WHERE order_status = 1 AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(order_date) ORDER BY d ASC
");
$stmt->execute();
$dailyRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$dailyMap = [];
foreach ($dailyRows as $r) $dailyMap[$r['d']] = ['c'=>(int)$r['c'],'rev'=>(float)$r['rev']];

$stmt = $connect->prepare("
    SELECT DATE(t.transaction_date) AS d, COUNT(*) AS c, COALESCE(SUM(t.total_payable - COALESCE(ret.refunded, 0)),0) AS rev
    FROM transactions t
    LEFT JOIN (
        SELECT transaction_id, SUM(refund_amount) AS refunded
        FROM sales_returns WHERE status = 'completed' GROUP BY transaction_id
    ) ret ON ret.transaction_id = t.transaction_id
    WHERE t.transaction_status = 1 AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(t.transaction_date) ORDER BY d ASC
");
$stmt->execute();
$dailyTxRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$dailyTxMap = [];
foreach ($dailyTxRows as $r) $dailyTxMap[$r['d']] = ['c'=>(int)$r['c'],'rev'=>(float)$r['rev']];

$chartLabels=$chartOrders=$chartRevenue=$chartTxCount=$chartTxRevenue=[];
for ($i=6;$i>=0;$i--) {
    $d=date('Y-m-d',strtotime("-$i days"));
    $chartLabels[]=date('D',strtotime($d));
    $chartOrders[]=$dailyMap[$d]['c']??0;
    $netDailyTxRev = $dailyTxMap[$d]['rev'] ?? 0;
    $chartRevenue[]=($dailyMap[$d]['rev']??0) + $netDailyTxRev;
    $chartTxCount[]=$dailyTxMap[$d]['c']??0;
    $chartTxRevenue[]=$netDailyTxRev;
}

// ── Monthly data (6 months) — orders + transactions combined ─────
$stmt = $connect->prepare("
    SELECT DATE_FORMAT(order_date,'%Y-%m') AS m, COALESCE(SUM(paid),0) AS rev, COUNT(*) AS c
    FROM orders WHERE order_status = 1 AND order_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY DATE_FORMAT(order_date,'%Y-%m') ORDER BY m ASC
");
$stmt->execute();
$monthlyRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$monthMap=[];
foreach ($monthlyRows as $r) $monthMap[$r['m']]=['rev'=>(float)$r['rev'],'c'=>(int)$r['c']];

$stmt = $connect->prepare("
    SELECT DATE_FORMAT(t.transaction_date,'%Y-%m') AS m, COALESCE(SUM(t.total_payable - COALESCE(ret.refunded, 0)),0) AS rev, COUNT(*) AS c
    FROM transactions t
    LEFT JOIN (
        SELECT transaction_id, SUM(refund_amount) AS refunded
        FROM sales_returns WHERE status = 'completed' GROUP BY transaction_id
    ) ret ON ret.transaction_id = t.transaction_id
    WHERE t.transaction_status = 1 AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY DATE_FORMAT(t.transaction_date,'%Y-%m') ORDER BY m ASC
");
$stmt->execute();
$monthlyTxRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$monthTxMap=[];
foreach ($monthlyTxRows as $r) $monthTxMap[$r['m']]=['rev'=>(float)$r['rev'],'c'=>(int)$r['c']];

$monthLabels=$monthRevData=$monthOrdData=[];
for ($i=5;$i>=0;$i--) {
    $m=date('Y-m',strtotime("-$i months"));
    $monthLabels[]=date('M',strtotime("-$i months"));
    $netMonthTxRev = $monthTxMap[$m]['rev'] ?? 0;
    $monthRevData[]=($monthMap[$m]['rev']??0) + $netMonthTxRev;
    $monthOrdData[]=($monthMap[$m]['c']??0)+($monthTxMap[$m]['c']??0);
}

// ── Yearly data ──────────────────────────────────────────────────
$stmt = $connect->prepare("SELECT MIN(YEAR(order_date)) AS y FROM orders WHERE order_status = 1");
$stmt->execute();
$minOrderYear = $stmt->get_result()->fetch_assoc()['y'];
$stmt->close();

$stmt = $connect->prepare("SELECT MIN(YEAR(transaction_date)) AS y FROM transactions WHERE transaction_status = 1");
$stmt->execute();
$minTxYear = $stmt->get_result()->fetch_assoc()['y'];
$stmt->close();

$currentYear = (int)date('Y');
$earliestDataYear = min(
    $minOrderYear !== null ? (int)$minOrderYear : $currentYear,
    $minTxYear !== null ? (int)$minTxYear : $currentYear
);
$yearSpan = $currentYear - $earliestDataYear + 1;
$yearCount = max(2, min(5, $yearSpan));
$yearInterval = $yearCount - 1;

$stmt = $connect->prepare("
    SELECT YEAR(order_date) AS y, COALESCE(SUM(paid),0) AS rev, COUNT(*) AS c
    FROM orders WHERE order_status = 1 AND order_date >= DATE_SUB(CURDATE(), INTERVAL $yearInterval YEAR)
    GROUP BY YEAR(order_date) ORDER BY y ASC
");
$stmt->execute();
$yearlyRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$yearMap=[];
foreach ($yearlyRows as $r) $yearMap[$r['y']]=['rev'=>(float)$r['rev'],'c'=>(int)$r['c']];

$stmt = $connect->prepare("
    SELECT YEAR(t.transaction_date) AS y, COALESCE(SUM(t.total_payable - COALESCE(ret.refunded, 0)),0) AS rev, COUNT(*) AS c
    FROM transactions t
    LEFT JOIN (
        SELECT transaction_id, SUM(refund_amount) AS refunded
        FROM sales_returns WHERE status = 'completed' GROUP BY transaction_id
    ) ret ON ret.transaction_id = t.transaction_id
    WHERE t.transaction_status = 1 AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL $yearInterval YEAR)
    GROUP BY YEAR(t.transaction_date) ORDER BY y ASC
");
$stmt->execute();
$yearlyTxRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$yearTxMap=[];
foreach ($yearlyTxRows as $r) $yearTxMap[$r['y']]=['rev'=>(float)$r['rev'],'c'=>(int)$r['c']];

$yearLabels=$yearRevData=$yearOrdData=[];
for ($i=$yearCount-1;$i>=0;$i--) {
    $y=(int)date('Y',strtotime("-$i years"));
    $yearLabels[]=(string)$y;
    $yearRevData[]=($yearMap[$y]['rev']??0)+($yearTxMap[$y]['rev']??0);
    $yearOrdData[]=($yearMap[$y]['c']??0)+($yearTxMap[$y]['c']??0);
}

// ── Payment breakdown ────────────────────────────────────────────
$stmt = $connect->prepare("SELECT payment_status, COUNT(*) AS c FROM orders WHERE order_status = 1 GROUP BY payment_status");
$stmt->execute();
$payRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$paidCount=$unpaidCount=0;
foreach ($payRows as $pr) { if ($pr['payment_status']==1) $paidCount=(int)$pr['c']; else $unpaidCount+=(int)$pr['c']; }

// ── Recent Orders ────────────────────────────────────────────────
$stmt = $connect->prepare("
    SELECT o.order_id, o.order_date, o.grand_total, o.payment_status, u.username
    FROM orders o JOIN users u ON o.user_id = u.user_id
    WHERE o.order_status = 1 ORDER BY o.order_id DESC LIMIT 8
");
$stmt->execute();
$recentOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Top Performers ───────────────────────────────────────────────
$stmt = $connect->prepare("
    SELECT u.username, SUM(o.grand_total) AS total
    FROM orders o JOIN users u ON o.user_id = u.user_id
    WHERE o.order_status = 1 GROUP BY o.user_id ORDER BY total DESC LIMIT 5
");
$stmt->execute();
$topPerformers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// ── Low-stock ────────────────────────────────────────────────────
$isAdmin = ($currentUser == 1 || ($_SESSION['user_role'] ?? '') === 'admin');
if ($isAdmin || SimpleSecurity::hasPermission('products') || SimpleSecurity::hasPermission('stock_movement')) {
    $stmt = $connect->prepare("SELECT product_name, quantity FROM product WHERE quantity <= 3 AND status = 1 ORDER BY CAST(quantity AS UNSIGNED) ASC LIMIT 5");
    $stmt->execute();
    $lowStockItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt = $connect->prepare("
    SELECT product_name, SUM(sold) AS sold, SUM(revenue) AS revenue FROM (
        SELECT p.product_name, COALESCE(SUM(CASE WHEN p.sold_by_weight = 1 THEN oi.quantity / 1000 ELSE oi.quantity END),0) AS sold, COALESCE(SUM(oi.total),0) AS revenue
        FROM order_item oi
        JOIN product p ON oi.product_id = p.product_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.order_status = 1 AND oi.order_item_status = 1
        GROUP BY oi.product_id, p.product_name
        UNION ALL
        SELECT p.product_name,
               COALESCE(SUM(CASE WHEN p.sold_by_weight = 1 THEN (ti.quantity - COALESCE(ret.returned_qty, 0)) / 1000 ELSE (ti.quantity - COALESCE(ret.returned_qty, 0)) END),0) AS sold,
               COALESCE(SUM(ti.total - COALESCE(ret.returned_amt, 0)),0) AS revenue
        FROM transaction_items ti
        JOIN product p ON ti.product_id = p.product_id
        JOIN transactions t ON ti.transaction_id = t.transaction_id
        LEFT JOIN (
            SELECT sri.transaction_item_id, SUM(sri.quantity) AS returned_qty, SUM(sri.refund_amount) AS returned_amt
            FROM sales_return_items sri
            JOIN sales_returns sr ON sr.return_id = sri.return_id
            WHERE sr.status = 'completed'
            GROUP BY sri.transaction_item_id
        ) ret ON ret.transaction_item_id = ti.item_id
        WHERE t.transaction_status = 1 AND ti.item_status = 1
        GROUP BY ti.product_id, p.product_name
    ) combined GROUP BY product_name HAVING sold > 0 ORDER BY sold DESC LIMIT 5
");
$stmt->execute();
$topProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Recent Transactions ─────────────────────────────────────────
$stmt = $connect->prepare("
    SELECT t.transaction_id, t.or_number, t.transaction_date, t.customer_name, t.total_payable, t.transaction_status, u.username,
           (SELECT COALESCE(SUM(refund_amount), 0) FROM sales_returns sr WHERE sr.transaction_id = t.transaction_id AND sr.status = 'completed') as total_refunded
    FROM transactions t LEFT JOIN users u ON t.user_id = u.user_id
    WHERE t.transaction_status = 1 ORDER BY t.transaction_id DESC LIMIT 8
");
$stmt->execute();
$recentTransactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Expiring Soon (batches expiring within 30 days) ─────────────
$expiringSoonCount = 0;
$expiringSoonResult = $connect->query("SELECT COUNT(DISTINCT b.product_id) AS cnt FROM product_batches b WHERE b.status='active' AND b.qty_remaining>0 AND b.expiry_date IS NOT NULL AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
if ($expiringSoonResult) $expiringSoonCount = (int)$expiringSoonResult->fetch_assoc()['cnt'];

// ── Recent Stock Activity (last 5) ─────────────────────────────
$stockActivity = $connect->query("
    SELECT m.reference_no, m.movement_type, m.movement_date, m.reason, m.total_cost,
           GROUP_CONCAT(CONCAT(p.product_name, ' (', mi.quantity, ')') SEPARATOR ', ') as products,
           s.supplier_name
    FROM stock_movements m
    LEFT JOIN stock_movement_items mi ON mi.movement_id = m.movement_id
    LEFT JOIN product p ON p.product_id = mi.product_id
    LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id
    GROUP BY m.movement_id
    ORDER BY m.created_at DESC LIMIT 5
");

// ── Expiring Products (batches expiring within 30 days) ─────────
$expiringProducts = $connect->query("
    SELECT p.product_name, b.batch_number, b.qty_remaining, b.expiry_date,
           DATEDIFF(b.expiry_date, CURDATE()) as days_left
    FROM product_batches b
    JOIN product p ON p.product_id = b.product_id
    WHERE b.status = 'active' AND b.qty_remaining > 0 AND b.expiry_date IS NOT NULL
    AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY b.expiry_date ASC LIMIT 5
");
$expiringCount = 0;
$expiringList = [];
if ($expiringProducts) {
    $expiringCount = $expiringProducts->num_rows;
    while ($epRow = $expiringProducts->fetch_assoc()) {
        $expiringList[] = $epRow;
    }
}

// ── AI Insights ─────────────────────────────────────────────────
// (13.2) $reorderCount is now computed once, above, in the merged product
// aggregate query — no longer queried separately here.
$insights = [];
if ($reorderCount > 0) $insights[] = ['icon'=>'fa-exclamation-triangle','color'=>'var(--warning)','text'=>"$reorderCount products need restocking. Consider creating a purchase order."];

if ($expiringCount > 0) $insights[] = ['icon'=>'fa-clock','color'=>'var(--danger)','text'=>"$expiringCount batches expiring within 30 days. Review the expiry tracker."];

$pendingPO = 0;
$pendingPOResult = $connect->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE status IN ('sent','partial')");
if ($pendingPOResult) $pendingPO = (int)$pendingPOResult->fetch_assoc()['cnt'];
if ($pendingPO > 0) $insights[] = ['icon'=>'fa-file-invoice','color'=>'var(--info)','text'=>"$pendingPO purchase orders awaiting delivery."];

$todaySalesCount = 0;
$todaySalesResult = $connect->query("SELECT COUNT(*) as cnt FROM transactions WHERE transaction_date='".date('Y-m-d')."' AND transaction_status=1");
if ($todaySalesResult) $todaySalesCount = (int)$todaySalesResult->fetch_assoc()['cnt'];
if ($todaySalesCount == 0) $insights[] = ['icon'=>'fa-cash-register','color'=>'var(--text-muted)','text'=>"No sales recorded today yet."];

if (empty($insights)) $insights[] = ['icon'=>'fa-check-circle','color'=>'var(--success)','text'=>"Everything looks good! Inventory is healthy."];

// ── Greeting ─────────────────────────────────────────────────────
$hour = (int)date('G');
if ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 18) $greeting = 'Good afternoon';
else $greeting = 'Good evening';

// ── Currency formatter (no dollar sign, Philippine peso) ─────────
function fmt($n, $dec = 0) { return number_format((float)$n, $dec, '.', ','); }
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* ═══════════════════════════════════════════════════════════
   DASHBOARD — Clean Unified Design
   Uses global CSS variables from header_sidebar.php
═══════════════════════════════════════════════════════════ */
:root {
    --db-radius: 14px;
    --db-shadow: 0 1px 4px var(--shadow), 0 4px 16px rgba(0,0,0,.03);
}

/* ── Header ───────────────────────────────────────────────── */
.db-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 10px; margin-bottom: 2rem;
}
.db-hello { font-family: var(--font-display); font-size: 1.85rem; font-weight: 600; color: var(--text); margin: 0; line-height: 1.25; }
.db-desc { color: var(--text-muted); font-size: .9rem; margin: .25rem 0 0; }
.db-date {
    font-size: .75rem; font-weight: 600; color: var(--primary-dark);
    background: var(--primary-light); padding: .35rem .9rem; border-radius: 99px;
    white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;
}

/* ── Cards (unified) ──────────────────────────────────────── */
.db-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--db-radius);
    box-shadow: var(--db-shadow);
    overflow: hidden;
}
.db-card-head {
    padding: .9rem 1.15rem;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px;
}
.db-card-body { padding: 1.1rem 1.15rem; }

.db-title { font-family: var(--font-display); font-size: 1rem; font-weight: 600; color: var(--text); margin: 0; display: flex; align-items: center; gap: 7px; }
.db-title i { color: var(--primary); font-size: .8rem; }
.db-subtitle { font-size: .74rem; color: var(--text-muted); margin: .1rem 0 0; }

/* ── Stat cards ───────────────────────────────────────────── */
.st {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--db-radius);
    box-shadow: var(--db-shadow);
    padding: 1.15rem 1.2rem;
    transition: transform .2s, box-shadow .2s;
}
.st:hover { transform: translateY(-2px); box-shadow: 0 8px 24px var(--shadow); }
a .st { cursor: pointer; }

.st-icon {
    width: 42px; height: 42px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: var(--primary);
    background: var(--primary-light);
    flex-shrink: 0;
}
.st-icon.alert-icon { color: var(--danger); background: rgba(193,64,31,0.1); }
.st-icon.ok-icon { color: var(--success); background: rgba(59,122,60,0.1); }

.st-num { font-family: var(--font-display); font-size: 1.65rem; font-weight: 600; color: var(--text); line-height: 1; margin: .4rem 0 .15rem; }
.st-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--text-muted); margin: 0; }
.st-note { font-size: .72rem; font-weight: 600; color: var(--text-muted); }
.st-note.good { color: var(--success); }
.st-note.bad { color: var(--danger); }

/* Hero stat — the one number that matters most on the page, given real
   visual weight instead of sitting identically-sized among 11 others */
.st-hero {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border: none;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.st-hero::after {
    content: '';
    position: absolute; top: -40%; right: -15%;
    width: 160px; height: 160px; border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.14), transparent 70%);
}
.st-hero .st-label { color: rgba(255,255,255,0.78); }
.st-hero .st-num { color: #fff; font-size: 2rem; }
.st-hero .st-note { color: rgba(255,255,255,0.85); }
.st-hero .st-icon { background: rgba(255,255,255,0.16); color: #fff; }
.st-hero:hover { box-shadow: 0 10px 30px rgba(201,84,12,0.35); }

/* ── Charts ───────────────────────────────────────────────── */
.chart-box { position: relative; width: 100%; }
.chart-box canvas { width: 100% !important; }

/* ── Table ────────────────────────────────────────────────── */
.db-tbl td, .db-tbl th {
    padding: .6rem 1rem; vertical-align: middle;
    font-size: .82rem; border-bottom: 1px solid var(--border-light);
}
.db-tbl th {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--text-muted); background: var(--border-lightest);
    border-bottom-color: var(--border);
}
.db-tbl tbody tr:hover td { background: var(--border-lightest); }
.db-tbl tbody tr:last-child td { border-bottom: none; }

.o-badge {
    display: inline-block;
    background: rgba(232,163,23,0.08); color: var(--primary);
    font-size: .72rem; font-weight: 700; padding: .15rem .5rem; border-radius: 5px;
}

.status-pill {
    display: inline-block; padding: .15rem .55rem;
    border-radius: 6px; font-size: .68rem; font-weight: 700;
}
.status-pill.paid { background: rgba(46,125,50,0.1); color: var(--success); }
.status-pill.unpaid { background: rgba(198,40,40,0.1); color: var(--danger); }

/* ── Avatar ───────────────────────────────────────────────── */
.av {
    width: 30px; height: 30px; border-radius: 8px;
    background: var(--primary-light); color: var(--primary);
    font-size: .65rem; font-weight: 800;
    display: inline-flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

/* ── Progress bar ─────────────────────────────────────────── */
.prog-bg { background: var(--border-light); border-radius: 99px; height: 5px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 99px; background: var(--primary); transition: width .5s; }

/* ── Quick actions ────────────────────────────────────────── */
.qk {
    background: var(--card-bg); border: 1px solid var(--border);
    border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.03);
    padding: 1rem .6rem; text-align: center;
    text-decoration: none; color: inherit;
    transition: transform .2s, box-shadow .2s, border-color .2s;
    display: block;
}
.qk:hover { transform: translateY(-2px); box-shadow: 0 4px 14px var(--shadow); border-color: var(--primary); text-decoration: none; color: inherit; }

.qk-icon {
    width: 40px; height: 40px; border-radius: 10px; margin: 0 auto .5rem;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; color: var(--primary);
    background: var(--primary-light);
}
.qk h6 { font-size: .74rem; font-weight: 700; color: var(--text-muted); margin: 0; }

/* ── Low stock ────────────────────────────────────────────── */
.lsi { display: flex; align-items: center; justify-content: space-between; padding: .5rem 0; border-bottom: 1px solid var(--border-light); }
.lsi:last-child { border-bottom: none; }
.lsi-name { font-size: .82rem; font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 65%; }
.lsi-qty { font-size: .7rem; font-weight: 700; padding: .15rem .45rem; border-radius: 5px; }
.lsi-qty.out { background: rgba(198,40,40,0.1); color: var(--danger); }
.lsi-qty.low { background: rgba(232,163,23,0.1); color: var(--primary); }

/* ── Empty state ──────────────────────────────────────────── */
.db-empty { text-align: center; padding: 2rem 1rem; color: var(--text-muted); }
.db-empty i { font-size: 1.8rem; margin-bottom: .4rem; display: block; }
.db-empty p { font-size: .85rem; margin: 0; }

/* ── Buttons ──────────────────────────────────────────────── */
.btn-acc {
    background: var(--primary); color: #fff;
    border: none; font-size: .74rem; font-weight: 600;
    padding: .3rem .75rem; border-radius: 7px; transition: all .2s;
}
.btn-acc:hover { background: var(--primary-dark); color: #fff; }

.btn-acc-o {
    border: 1.5px solid var(--primary); color: var(--primary);
    font-size: .74rem; font-weight: 600;
    padding: .25rem .7rem; border-radius: 7px; background: transparent; transition: all .2s;
}
.btn-acc-o:hover { background: var(--primary); color: #fff; }

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 575.98px) {
    .db-hello { font-size: 1.2rem; }
    .st-num { font-size: 1.4rem; }
    .st { padding: .9rem 1rem; }
    .db-tbl td, .db-tbl th { padding: .45rem .55rem; font-size: .75rem; }
    .db-card-body { padding: .85rem .9rem; }
    .db-card-head { padding: .75rem .9rem; }
    .qk { padding: .75rem .4rem; }
    .qk-icon { width: 34px; height: 34px; font-size: .85rem; }
    .qk h6 { font-size: .68rem; }
}
</style>

<!-- ═══ HEADER ═══ -->
<div class="db-header">
    <div>
        <h1 class="db-hello"><?= $greeting ?>, <?= htmlspecialchars(ucfirst($currentUsername ?? 'Admin')) ?>!</h1>
        <p class="db-desc">Here's your inventory overview for today.</p>
    </div>
    <span class="db-date"><i class="fas fa-calendar-day"></i> <?= date('l, F j Y') ?></span>
</div>

<!-- ═══ STATS ROW 1 ═══ -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <a href="product.php" class="text-decoration-none">
        <div class="st h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="st-label">Products</p>
                    <p class="st-num"><span class="counter-value" data-target="<?= $countProduct ?>"><?= fmt($countProduct) ?></span></p>
                    <span class="st-note"><i class="fas fa-box fa-xs me-1"></i>active items</span>
                </div>
                <div class="st-icon"><i class="fas fa-boxes-stacked"></i></div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="transaction.php" class="text-decoration-none">
        <div class="st h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="st-label">Total Sales</p>
                    <p class="st-num"><span class="counter-value" data-target="<?= $totalSalesCount ?>"><?= fmt($totalSalesCount) ?></span></p>
                    <span class="st-note good"><i class="fas fa-arrow-trend-up fa-xs me-1"></i><?= $weekTotalSalesCount ?> this week</span>
                </div>
                <div class="st-icon"><i class="fas fa-cash-register"></i></div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="product.php?filter=low_stock" class="text-decoration-none">
        <div class="st h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="st-label">Low Stock</p>
                    <p class="st-num"><span class="counter-value" data-target="<?= $countLowStock ?>"><?= fmt($countLowStock) ?></span></p>
                    <span class="st-note <?= $countLowStock > 0 ? 'bad' : 'good' ?>">
                        <i class="fas fa-<?= $countLowStock > 0 ? 'exclamation-circle' : 'check-circle' ?> fa-xs me-1"></i>
                        <?= $countLowStock > 0 ? 'needs restock' : 'all stocked' ?>
                    </span>
                </div>
                <div class="st-icon <?= $countLowStock > 0 ? 'alert-icon' : 'ok-icon' ?>">
                    <i class="fas fa-<?= $countLowStock > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                </div>
            </div>
        </div>
        </a>
    </div>
    <?php if (SimpleSecurity::hasPermission('reports')): ?>
    <div class="col-6 col-lg-3">
        <a href="report.php" class="text-decoration-none">
        <div class="st st-hero h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="st-label">Total Revenue</p>
                    <p class="st-num"><span class="counter-value" data-target="<?= $totalRevenue ?>" data-decimals="2"><?= fmt($totalRevenue, 2) ?></span></p>
                    <span class="st-note"><i class="fas fa-arrow-trend-up fa-xs me-1"></i><?= fmt($weekRevenue, 2) ?> this week</span>
                </div>
                <div class="st-icon"><i class="fas fa-coins"></i></div>
            </div>
        </div>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- ═══ STATS ROW 2 ═══ -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="st h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="st-label">Today's Sales</p>
                    <p class="st-num"><span class="counter-value" data-target="<?= $todayRevenue ?>" data-decimals="2"><?= fmt($todayRevenue, 2) ?></span></p>
                    <span class="st-note good"><i class="fas fa-cash-register fa-xs me-1"></i><?= $todayTotalCount ?> sale<?= $todayTotalCount != 1 ? 's' : '' ?> today</span>
                </div>
                <div class="st-icon ok-icon"><i class="fas fa-peso-sign"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="st h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="st-label">Yesterday</p>
                    <p class="st-num"><span class="counter-value" data-target="<?= $yesterdayRevenue ?>" data-decimals="2"><?= fmt($yesterdayRevenue, 2) ?></span></p>
                    <span class="st-note"><i class="fas fa-peso-sign fa-xs me-1"></i>yesterday's revenue</span>
                </div>
                <div class="st-icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>
    </div>
    <?php if ($isAdmin || SimpleSecurity::hasPermission('products') || SimpleSecurity::hasPermission('stock_movement') || SimpleSecurity::hasPermission('stock_forecast')): ?>
    <div class="col-6 col-lg-3">
        <a href="stock_forecast.php?tab=expiry" class="text-decoration-none">
        <div class="st h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="st-label">Expiring Soon</p>
                    <p class="st-num"><span class="counter-value" data-target="<?= $expiringSoonCount ?>"><?= fmt($expiringSoonCount) ?></span></p>
                    <span class="st-note <?= $expiringSoonCount > 0 ? 'bad' : 'good' ?>">
                        <i class="fas fa-<?= $expiringSoonCount > 0 ? 'clock' : 'check-circle' ?> fa-xs me-1"></i>
                        <?= $expiringSoonCount > 0 ? 'within 30 days' : 'none expiring' ?>
                    </span>
                </div>
                <div class="st-icon <?= $expiringSoonCount > 0 ? 'alert-icon' : 'ok-icon' ?>">
                    <i class="fas fa-<?= $expiringSoonCount > 0 ? 'calendar-xmark' : 'calendar-check' ?>"></i>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="product.php?filter=reorder" class="text-decoration-none">
        <div class="st h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="st-label">Reorder Needed</p>
                    <p class="st-num"><span class="counter-value" data-target="<?= $reorderCount ?>"><?= fmt($reorderCount) ?></span></p>
                    <span class="st-note <?= $reorderCount > 0 ? 'bad' : 'good' ?>">
                        <i class="fas fa-<?= $reorderCount > 0 ? 'exclamation-circle' : 'check-circle' ?> fa-xs me-1"></i>
                        <?= $reorderCount > 0 ? 'below reorder level' : 'all stocked' ?>
                    </span>
                </div>
                <div class="st-icon <?= $reorderCount > 0 ? 'alert-icon' : 'ok-icon' ?>">
                    <i class="fas fa-<?= $reorderCount > 0 ? 'rotate' : 'check' ?>"></i>
                </div>
            </div>
        </div>
        </a>
    </div>
    <?php else: ?>
    <div class="col-6 col-lg-3">
        <div class="st h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="st-label">Outstanding</p>
                    <p class="st-num"><span class="counter-value" data-target="<?= $totalDue ?>" data-decimals="2"><?= fmt($totalDue, 2) ?></span></p>
                    <span class="st-note <?= $totalDue > 0 ? 'bad' : 'good' ?>">
                        <i class="fas fa-<?= $totalDue > 0 ? 'exclamation-circle' : 'check-circle' ?> fa-xs me-1"></i>
                        <?= $totalDue > 0 ? 'unpaid balance' : 'all cleared' ?>
                    </span>
                </div>
                <div class="st-icon <?= $totalDue > 0 ? 'alert-icon' : 'ok-icon' ?>"><i class="fas fa-hand-holding-dollar"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="st h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="st-label">This Week</p>
                    <p class="st-num"><span class="counter-value" data-target="<?= $weekRevenue ?>" data-decimals="2"><?= fmt($weekRevenue, 2) ?></span></p>
                    <span class="st-note good"><i class="fas fa-chart-line fa-xs me-1"></i><?= $weekTransactions ?> transactions</span>
                </div>
                <div class="st-icon"><i class="fas fa-calendar-week"></i></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══ CHARTS ROW 1 ═══ -->
<?php $canSeeWeeklyOverview = SimpleSecurity::hasPermission('reports') || SimpleSecurity::hasPermission('orders') || SimpleSecurity::hasPermission('transaction'); ?>
<div class="row g-3 mb-4">
    <?php if ($canSeeWeeklyOverview): ?>
    <div class="col-12 col-lg-8">
        <div class="db-card h-100">
            <div class="db-card-head">
                <div>
                    <p class="db-title"><i class="fas fa-chart-bar"></i> Weekly Overview</p>
                    <p class="db-subtitle"><?= $weekTransactions ?> transactions &middot; <?= fmt($weekRevenue, 2) ?> revenue</p>
                </div>
                <?php if (SimpleSecurity::hasPermission('orders')): ?>
                <a href="transaction.php" class="btn-acc-o">View All <i class="fas fa-arrow-right ms-1"></i></a>
                <?php endif; ?>
            </div>
            <div class="db-card-body">
                <div class="chart-box" style="height:250px;"><canvas id="weeklyChart"></canvas></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php 
    $showExpiringCard = $isAdmin || SimpleSecurity::hasPermission('products') || SimpleSecurity::hasPermission('stock_movement') || SimpleSecurity::hasPermission('stock_forecast') || SimpleSecurity::hasPermission('reports');
    if ($showExpiringCard): 
    ?>
    <div class="col-12 <?= $canSeeWeeklyOverview ? 'col-lg-4' : '' ?>">
        <div class="db-card h-100">
            <div class="db-card-head">
                <div>
                    <p class="db-title"><i class="fas fa-clock" style="color: var(--warning)"></i> Expiring Products</p>
                    <p class="db-subtitle"><?= $expiringSoonCount ?> item<?= $expiringSoonCount != 1 ? 's' : '' ?> expiring within 30d</p>
                </div>
                <?php if (SimpleSecurity::hasPermission('stock_forecast') || $isAdmin): ?>
                <a href="stock_forecast.php?tab=expiry" class="btn-acc-o">Manage</a>
                <?php endif; ?>
            </div>
            <div class="db-card-body" style="padding-top:.4rem;padding-bottom:.4rem;">
                <?php if (!empty($expiringList)): ?>
                    <?php foreach ($expiringList as $ep): 
                        $daysLeft = (int)$ep['days_left'];
                        if ($daysLeft < 0) { $badgeClass = 'out'; $badgeText = 'EXPIRED'; }
                        elseif ($daysLeft <= 7) { $badgeClass = 'out'; $badgeText = $daysLeft . 'd left'; }
                        else { $badgeClass = 'low'; $badgeText = $daysLeft . 'd left'; }
                    ?>
                    <a href="stock_forecast.php?tab=expiry&search=<?= urlencode($ep['product_name']) ?>" class="lsi text-decoration-none" style="display:flex;">
                        <div style="flex:1;min-width:0;">
                            <span class="lsi-name" style="max-width:100%"><?= htmlspecialchars($ep['product_name']) ?></span>
                            <div style="font-size:0.7rem;color:var(--text-muted)">Batch: <?= htmlspecialchars($ep['batch_number']) ?> &bull; Qty: <?= (int)$ep['qty_remaining'] ?> &bull; Exp: <?= $ep['expiry_date'] ?></div>
                        </div>
                        <span class="lsi-qty <?= $badgeClass ?>"><?= $badgeText ?></span>
                    </a>
                    <?php endforeach; ?>
                    <?php if ($expiringSoonCount > 5): ?>
                    <a href="stock_forecast.php?tab=expiry" class="d-block text-center mt-2 mb-0 text-decoration-none" style="font-size:.72rem;color:var(--primary); font-weight:600;">+<?= $expiringSoonCount - 5 ?> more &rarr;</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="db-empty"><i class="fas fa-check-circle" style="color:var(--success);"></i><p>No products expiring soon</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php 
$showRevenue = $isAdmin || SimpleSecurity::hasPermission('reports') || SimpleSecurity::hasPermission('transaction') || SimpleSecurity::hasPermission('orders');
$showLowStock = $isAdmin || SimpleSecurity::hasPermission('products') || SimpleSecurity::hasPermission('stock_movement');
if ($showRevenue || $showLowStock): 
?>
<!-- ═══ CHARTS ROW 2 ═══ -->
<div class="row g-3 mb-4">
    <?php if ($showRevenue): ?>
    <div class="col-12 <?= $showLowStock ? 'col-lg-8' : '' ?>">
        <div class="db-card h-100">
            <div class="db-card-head">
                <div>
                    <p class="db-title"><i class="fas fa-chart-line"></i> Monthly Revenue</p>
                    <p class="db-subtitle">Last 6 months trend</p>
                </div>
            </div>
            <div class="db-card-body">
                <div class="chart-box" style="height:230px;"><canvas id="monthlyChart"></canvas></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($showLowStock): ?>
    <div class="col-12 <?= $showRevenue ? 'col-lg-4' : '' ?>">
        <div class="db-card h-100">
            <div class="db-card-head">
                <div>
                    <p class="db-title"><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</p>
                    <p class="db-subtitle"><?= $countLowStock ?> items need attention</p>
                </div>
                <a href="product.php?filter=out_of_stock" class="btn-acc-o">Manage</a>
            </div>
            <div class="db-card-body" style="padding-top:.4rem;padding-bottom:.4rem;">
                <?php if (!empty($lowStockItems)): ?>
                    <?php foreach ($lowStockItems as $item): ?>
                    <div class="lsi">
                        <span class="lsi-name"><?= htmlspecialchars($item['product_name']) ?></span>
                        <span class="lsi-qty <?= (int)$item['quantity'] === 0 ? 'out' : 'low' ?>">
                            <?= (int)$item['quantity'] === 0 ? 'Out of stock' : $item['quantity'] . ' left' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($countLowStock > 5): ?>
                    <p class="text-center mt-2 mb-0" style="font-size:.72rem;color:var(--text-muted);">+<?= $countLowStock - 5 ?> more</p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="db-empty"><i class="fas fa-check-circle" style="color:var(--success);"></i><p>All products are stocked</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($isAdmin || SimpleSecurity::hasPermission('reports') || SimpleSecurity::hasPermission('transaction') || SimpleSecurity::hasPermission('orders') || SimpleSecurity::hasPermission('products')): ?>
<!-- ═══ CHARTS ROW 3 (Yearly, Admin) ═══ -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="db-card h-100">
            <div class="db-card-head">
                <div>
                    <p class="db-title"><i class="fas fa-calendar-days"></i> Yearly Overview</p>
                    <p class="db-subtitle">Last <?= $yearCount ?> year<?= $yearCount != 1 ? 's' : '' ?></p>
                </div>
            </div>
            <div class="db-card-body">
                <div class="chart-box" style="height:230px;"><canvas id="yearlyChart"></canvas></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ ORDERS + PERFORMERS ═══ -->
<div class="row g-3 mb-4">
    <div class="<?= ($isAdmin) ? 'col-12 col-xl-7' : 'col-12' ?>">
        <div class="db-card h-100">
            <div class="db-card-head">
                <div>
                    <p class="db-title"><i class="fas fa-clock-rotate-left"></i> Recent Orders</p>
                    <p class="db-subtitle">Latest <?= count($recentOrders) ?> orders</p>
                </div>
                <?php if (SimpleSecurity::hasPermission('orders')): ?>
                <a href="transaction.php" class="btn-acc"><i class="fas fa-plus me-1"></i>New Transaction</a>
                <?php endif; ?>
            </div>
            <?php if (!SimpleSecurity::hasPermission('orders')): ?>
            <!-- Data block gated (S10): a staffer without 'orders' must not see order
                 amounts/customer/cashier data, only that the block exists. -->
            <div class="db-empty"><i class="fas fa-lock"></i><p>You don't have access to Orders.</p></div>
            <?php elseif (!empty($recentOrders)): ?>
            <div class="table-responsive">
                <table class="table db-tbl mb-0">
                    <thead><tr><th scope="col">#</th><th scope="col">By</th><th scope="col">Date</th><th scope="col" class="text-end">Amount</th><th scope="col" class="text-center">Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><span class="o-badge"><?= $o['order_id'] ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="av"><?= strtoupper(substr($o['username'],0,2)) ?></div>
                                    <span style="font-weight:600;color:var(--text);"><?= htmlspecialchars($o['username']) ?></span>
                                </div>
                            </td>
                            <td style="color:var(--text-muted);"><?= date('M j, Y', strtotime($o['order_date'])) ?></td>
                            <td class="text-end" style="font-weight:700;color:var(--text);"><?= fmt($o['grand_total'], 2) ?></td>
                            <td class="text-center">
                                <span class="status-pill <?= $o['payment_status']==1?'paid':'unpaid' ?>">
                                    <?= $o['payment_status']==1?'Paid':'Unpaid' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="db-empty"><i class="fas fa-receipt"></i><p>No orders yet</p></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <div class="col-12 col-xl-5">
        <div class="db-card h-100">
            <div class="db-card-head">
                <div>
                    <p class="db-title"><i class="fas fa-trophy"></i> Top Performers</p>
                    <p class="db-subtitle">By total sales</p>
                </div>
            </div>
            <div class="db-card-body">
                <?php if (!empty($topPerformers)): ?>
                    <?php foreach ($topPerformers as $idx => $p):
                        $pct = $totalRevenue > 0 ? round(($p['total'] / $totalRevenue) * 100, 1) : 0;
                    ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="av"><?= strtoupper(substr($p['username'],0,2)) ?></div>
                        <div style="flex:1;min-width:0;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="font-size:.82rem;font-weight:700;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <span style="font-size:.65rem;font-weight:800;color:var(--primary);background:var(--primary-light);padding:.08rem .35rem;border-radius:4px;margin-right:4px;">#<?= $idx+1 ?></span>
                                    <?= htmlspecialchars($p['username']) ?>
                                </span>
                                <span style="font-size:.75rem;font-weight:700;color:var(--text);flex-shrink:0;margin-left:.5rem;"><?= fmt($p['total'], 0) ?></span>
                            </div>
                            <div class="prog-bg"><div class="prog-fill" style="width:<?= $pct ?>%;"></div></div>
                            <span style="font-size:.65rem;color:var(--text-muted);"><?= $pct ?>% of revenue</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="db-empty"><i class="fas fa-users"></i><p>No sales data yet</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══ RECENT TRANSACTIONS ═══ -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="db-card h-100">
            <div class="db-card-head">
                <div>
                    <p class="db-title"><i class="fas fa-cash-register"></i> Recent Transactions (POS)</p>
                    <?php if (SimpleSecurity::hasPermission('transaction')): ?>
                    <p class="db-subtitle">Latest <?= count($recentTransactions) ?> transactions &middot; Today: <?= fmt($todayTxRevenue, 2) ?> from <?= $todayTransactions ?> sale<?= $todayTransactions != 1 ? 's' : '' ?></p>
                    <?php endif; ?>
                </div>
                <?php if (SimpleSecurity::hasPermission('transaction')): ?>
                <a href="transaction.php" class="btn-acc"><i class="fas fa-plus me-1"></i>New Transaction</a>
                <?php endif; ?>
            </div>
            <?php if (!SimpleSecurity::hasPermission('transaction')): ?>
            <!-- Data block gated (S10): a staffer without 'transaction' must not see
                 sale amounts/customer/cashier data, only that the block exists. -->
            <div class="db-empty"><i class="fas fa-lock"></i><p>You don't have access to Transactions.</p></div>
            <?php elseif (!empty($recentTransactions)): ?>
            <div class="table-responsive">
                <table class="table db-tbl mb-0">
                    <thead><tr><th scope="col">OR #</th><th scope="col">By</th><th scope="col">Customer</th><th scope="col">Date</th><th scope="col" class="text-end">Amount</th><th scope="col" class="text-center">Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentTransactions as $tx):
                        $totPay = (float)$tx['total_payable'];
                        $totRef = (float)($tx['total_refunded'] ?? 0);
                        $netPay = max(0, $totPay - $totRef);
                    ?>
                        <tr>
                            <td><span class="o-badge"><?= htmlspecialchars($tx['or_number']) ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="av"><?= strtoupper(substr($tx['username'] ?? 'NA',0,2)) ?></div>
                                    <span style="font-weight:600;color:var(--text);"><?= htmlspecialchars($tx['username'] ?? 'N/A') ?></span>
                                </div>
                            </td>
                            <td style="color:var(--text-muted);"><?= htmlspecialchars($tx['customer_name']) ?></td>
                            <td style="color:var(--text-muted);"><?= date('M j, Y', strtotime($tx['transaction_date'])) ?></td>
                            <td class="text-end" style="font-weight:700;color:var(--text);">
                                <?php if ($totRef >= $totPay && $totPay > 0): ?>
                                    <s><?= fmt($totPay, 2) ?></s><br><span class="text-danger" style="font-size:0.75rem;">Ref: <?= fmt($totRef, 2) ?></span>
                                <?php elseif ($totRef > 0): ?>
                                    <?= fmt($netPay, 2) ?><br><span class="text-danger" style="font-size:0.75rem;">Ref: -<?= fmt($totRef, 2) ?></span>
                                <?php else: ?>
                                    <?= fmt($totPay, 2) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($totRef >= $totPay && $totPay > 0): ?>
                                    <span class="status-pill cancelled">Refunded</span>
                                <?php elseif ($totRef > 0): ?>
                                    <span class="status-pill pending">Partial Refund</span>
                                <?php else: ?>
                                    <span class="status-pill paid">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="db-empty"><i class="fas fa-cash-register"></i><p>No transactions yet.<?php if (SimpleSecurity::hasPermission('transaction')): ?> <a href="transaction.php">Create your first transaction</a><?php endif; ?></p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══ QUICK ACTIONS ═══ -->
<div class="db-card mb-4">
    <div class="db-card-head">
        <p class="db-title"><i class="fas fa-bolt"></i> Quick Actions</p>
    </div>
    <div class="db-card-body">
        <div class="row g-2">
            <?php if (SimpleSecurity::hasPermission('transaction')): ?>
            <div class="col-4 col-sm-3 col-md-2"><a href="transaction.php" class="qk"><div class="qk-icon"><i class="fas fa-cash-register"></i></div><h6>New Sale</h6></a></div>
            <?php endif; ?>
            <?php if (SimpleSecurity::hasPermission('products')): ?>
            <div class="col-4 col-sm-3 col-md-2"><a href="product.php" class="qk"><div class="qk-icon"><i class="fas fa-box-open"></i></div><h6>Products</h6></a></div>
            <?php endif; ?>
            <?php if (SimpleSecurity::hasPermission('reports')): ?>
            <div class="col-4 col-sm-3 col-md-2"><a href="report.php" class="qk"><div class="qk-icon"><i class="fas fa-chart-bar"></i></div><h6>Reports</h6></a></div>
            <?php endif; ?>
            <?php if (SimpleSecurity::hasPermission('products')): ?>
            <div class="col-4 col-sm-3 col-md-2"><a href="product.php?tab=brands" class="qk"><div class="qk-icon"><i class="fas fa-tag"></i></div><h6>Brands</h6></a></div>
            <div class="col-4 col-sm-3 col-md-2"><a href="product.php?tab=categories" class="qk"><div class="qk-icon"><i class="fas fa-layer-group"></i></div><h6>Categories</h6></a></div>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
            <div class="col-4 col-sm-3 col-md-2"><a href="user.php" class="qk"><div class="qk-icon"><i class="fas fa-users-gear"></i></div><h6>Users</h6></a></div>
            <div class="col-4 col-sm-3 col-md-2"><a href="setting.php" class="qk"><div class="qk-icon"><i class="fas fa-gear"></i></div><h6>Settings</h6></a></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Row: Stock Activity & AI Insights -->
<div class="row g-3 mb-3">
    <div class="col-12 <?= ($currentUser == 1) ? 'col-lg-6' : '' ?>">
        <div class="db-card h-100">
            <div class="db-card-head">
                <div class="db-title"><i class="fas fa-exchange-alt" style="color: var(--primary)"></i> Recent Stock Activity</div>
                <a href="stock_movement.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="db-card-body">
                <?php if ($stockActivity && $stockActivity->num_rows > 0): ?>
                    <?php while ($sa = $stockActivity->fetch_assoc()): ?>
                    <div class="lsi">
                        <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-right:10px;background:<?php echo $sa['movement_type']=='IN' ? 'rgba(46,125,50,0.1)' : 'rgba(198,40,40,0.1)'; ?>">
                            <i class="fas <?php echo $sa['movement_type']=='IN' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>" style="color:<?php echo $sa['movement_type']=='IN' ? 'var(--success)' : 'var(--danger)'; ?>;font-size:0.75rem"></i>
                        </div>
                        <div style="flex:1">
                            <div style="font-size:0.82rem;font-weight:600;color:var(--text)"><?php echo $sa['movement_type']=='IN' ? 'Stock In' : 'Stock Out'; ?>: <?php echo htmlspecialchars(substr($sa['products'] ?? '', 0, 50)); ?></div>
                            <div style="font-size:0.72rem;color:var(--text-muted)"><?php echo htmlspecialchars($sa['reference_no']); ?> &bull; <?php echo $sa['movement_date']; ?> &bull; <?php echo ucfirst($sa['reason']); ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center;color:var(--text-muted);padding:20px;">
                        <i class="fas fa-boxes-stacked" style="font-size:1.5rem;opacity:0.3;margin-bottom:8px;display:block"></i>
                        <p style="font-size:0.82rem;margin:0">No stock movements yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if($currentUser == 1): ?>
    <div class="col-12 col-lg-6">
        <div class="db-card h-100">
            <div class="db-card-head">
                <div class="db-title"><i class="fas fa-robot" style="color: var(--primary)"></i> AI Insights</div>
                <span class="badge" style="background: var(--primary-light); color: var(--primary); font-size: 0.65rem;">MAIA</span>
            </div>
            <div class="db-card-body">
                <?php foreach ($insights as $insight): ?>
                <div class="lsi" style="gap:10px;">
                    <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?php
                        if ($insight['color'] == 'var(--warning)') echo 'rgba(232,163,23,0.1)';
                        elseif ($insight['color'] == 'var(--danger)') echo 'rgba(198,40,40,0.1)';
                        elseif ($insight['color'] == 'var(--info)') echo 'rgba(33,150,243,0.1)';
                        elseif ($insight['color'] == 'var(--success)') echo 'rgba(46,125,50,0.1)';
                        else echo 'rgba(0,0,0,0.05)';
                    ?>">
                        <i class="fas <?php echo $insight['icon']; ?>" style="color:<?php echo $insight['color']; ?>;font-size:0.75rem"></i>
                    </div>
                    <div style="flex:1;font-size:0.82rem;color:var(--text)"><?php echo htmlspecialchars($insight['text']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══ CHARTS JS ═══ -->
<script>
function getCSSVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

var weeklyChart, monthlyChart, yearlyChart;

<?php if ($canSeeWeeklyOverview): ?>
function initWeeklyChart() {
    var fontOpts = { family: "'Segoe UI', sans-serif", size: 11, weight: '600' };
    var primary = getCSSVar('--primary');
    var primaryDark = getCSSVar('--primary-dark');
    var borderColor = getCSSVar('--border');
    var mutedColor = getCSSVar('--text-muted');
    var w = document.getElementById('weeklyChart');
    if (w) weeklyChart = new Chart(w, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [
                { label: 'Orders', data: <?= json_encode($chartOrders) ?>, backgroundColor: 'rgba(232, 163, 23, 0.12)', borderColor: primary, borderWidth: 2, borderRadius: 6, borderSkipped: false, order: 2 },
                { label: 'Revenue', data: <?= json_encode($chartRevenue) ?>, type: 'line', borderColor: primaryDark, backgroundColor: 'rgba(232, 163, 23, 0.08)', borderWidth: 2, pointBackgroundColor: primaryDark, pointBorderColor: getCSSVar('--card-bg'), pointBorderWidth: 2, pointRadius: 3, pointHoverRadius: 5, fill: true, tension: 0.4, yAxisID: 'y1', order: 1 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 14, font: fontOpts, color: mutedColor } } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, font: fontOpts, color: mutedColor }, grid: { color: borderColor } },
                y1: { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { font: fontOpts, color: mutedColor } },
                x: { grid: { display: false }, ticks: { font: fontOpts, color: mutedColor } }
            }
        }
    });
}
<?php else: ?>
function initWeeklyChart() { /* no-op: caller lacks reports/orders/transaction permission */ }
<?php endif; ?>

<?php if ($isAdmin || SimpleSecurity::hasPermission('reports') || SimpleSecurity::hasPermission('transaction') || SimpleSecurity::hasPermission('orders') || SimpleSecurity::hasPermission('products')): ?>
function initMonthlyChart() {
    var fontOpts = { family: "'Segoe UI', sans-serif", size: 11, weight: '600' };
    var primary = getCSSVar('--primary');
    var primaryDark = getCSSVar('--primary-dark');
    var borderColor = getCSSVar('--border');
    var mutedColor = getCSSVar('--text-muted');
    var m = document.getElementById('monthlyChart');
    if (m) monthlyChart = new Chart(m, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthLabels) ?>,
            datasets: [
                { label: 'Revenue', data: <?= json_encode($monthRevData) ?>, borderColor: primary, backgroundColor: function(ctx) { var g=ctx.chart.ctx.createLinearGradient(0,0,0,230); g.addColorStop(0,'rgba(232, 163, 23, 0.15)'); g.addColorStop(1,'rgba(232, 163, 23, 0)'); return g; }, borderWidth: 2.5, pointBackgroundColor: primary, pointBorderColor: getCSSVar('--card-bg'), pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6, fill: true, tension: 0.4 },
                { label: 'Orders', data: <?= json_encode($monthOrdData) ?>, borderColor: primaryDark, borderWidth: 1.5, pointRadius: 3, borderDash: [5,3], fill: false, tension: 0.4, yAxisID: 'y1' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 14, font: fontOpts, color: mutedColor } } },
            scales: {
                y: { beginAtZero: true, ticks: { font: fontOpts, color: mutedColor }, grid: { color: borderColor } },
                y1: { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { stepSize: 1, font: fontOpts, color: mutedColor } },
                x: { grid: { display: false }, ticks: { font: fontOpts, color: mutedColor } }
            }
        }
    });
}

function initYearlyChart() {
    var fontOpts = { family: "'Segoe UI', sans-serif", size: 11, weight: '600' };
    var primary = getCSSVar('--primary');
    var primaryDark = getCSSVar('--primary-dark');
    var borderColor = getCSSVar('--border');
    var mutedColor = getCSSVar('--text-muted');
    var y = document.getElementById('yearlyChart');
    if (y) yearlyChart = new Chart(y, {
        type: 'bar',
        data: {
            labels: <?= json_encode($yearLabels) ?>,
            datasets: [
                { label: 'Revenue', data: <?= json_encode($yearRevData) ?>, backgroundColor: 'rgba(232, 163, 23, 0.12)', borderColor: primary, borderWidth: 2, borderRadius: 6, borderSkipped: false, order: 2 },
                { label: 'Orders', data: <?= json_encode($yearOrdData) ?>, type: 'line', borderColor: primaryDark, backgroundColor: 'rgba(232, 163, 23, 0.08)', borderWidth: 2, pointBackgroundColor: primaryDark, pointBorderColor: getCSSVar('--card-bg'), pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6, fill: true, tension: 0.4, yAxisID: 'y1', order: 1 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 14, font: fontOpts, color: mutedColor } } },
            scales: {
                y: { beginAtZero: true, ticks: { font: fontOpts, color: mutedColor }, grid: { color: borderColor } },
                y1: { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { stepSize: 1, font: fontOpts, color: mutedColor } },
                x: { grid: { display: false }, ticks: { font: fontOpts, color: mutedColor } }
            }
        }
    });
}
<?php endif; ?>

document.addEventListener('DOMContentLoaded', function() {
    initWeeklyChart();
    <?php if ($showRevenue): ?>
    initMonthlyChart();
    <?php endif; ?>
    <?php if ($isAdmin || SimpleSecurity::hasPermission('reports') || SimpleSecurity::hasPermission('orders') || SimpleSecurity::hasPermission('transaction') || SimpleSecurity::hasPermission('products')): ?>
    initYearlyChart();
    <?php endif; ?>
});

// Re-render charts when theme changes
var themeObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'data-theme') {
            if (typeof weeklyChart !== 'undefined' && weeklyChart) {
                weeklyChart.destroy();
                initWeeklyChart();
            }
            if (typeof monthlyChart !== 'undefined' && monthlyChart) {
                monthlyChart.destroy();
                initMonthlyChart();
            }
            if (typeof yearlyChart !== 'undefined' && yearlyChart) {
                yearlyChart.destroy();
                initYearlyChart();
            }
        }
    });
});
themeObserver.observe(document.documentElement, { attributes: true });
</script>

<?php require_once 'includes/footer_sidebar.php'; ?>

<!-- Daily Voice Briefing (after footer loads jQuery) -->
<script>
$(document).ready(function() {
    if (typeof MAIAVoice !== 'undefined') {
        setTimeout(function() {
            MAIAVoice.dailyBriefing({
                low_stock: <?= intval($countLowStock ?? 0) ?>,
                out_of_stock: <?= intval($countOutOfStock ?? 0) ?>,
                expiring: <?= intval($expiringSoonCount ?? 0) ?>,
                pending_po: <?= intval($pendingPO ?? 0) ?>,
                yesterday_revenue: '<?= number_format($yesterdayRevenue ?? 0, 0) ?>'
            });
        }, 2000);
    }
});
</script>
