<?php
require_once 'bootstrap_simple.php';

// Check authentication
if (!SimpleSecurity::isAuthenticated()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

global $connect;
$notifications = [];

// Get low stock alerts FIRST (highest priority)
// (13.6) quantity is DECIMAL(10,2); the CAST(...AS UNSIGNED) wrapper made
// this predicate non-sargable (can't use an index) and, for fractional
// quantities, isn't even equivalent to a direct comparison (CAST rounds
// half-up, e.g. 5.4 -> 5, which would incorrectly pass "<= 5"). Comparing
// quantity directly is both sargable and correct.
$stmt = $connect->prepare("SELECT product_name, quantity FROM product WHERE quantity <= 5 AND status = 1 ORDER BY quantity ASC LIMIT 5");
$stmt->execute();
$lowStockItems = $stmt->get_result();

while ($product = $lowStockItems->fetch_assoc()) {
    $qty = intval($product['quantity']);
    if ($qty == 0) {
        $urgency = 'text-danger';
        $icon = 'fa-exclamation-circle';
        $msg = htmlspecialchars($product['product_name']) . ' is OUT OF STOCK!';
    } elseif ($qty <= 3) {
        $urgency = 'text-warning';
        $icon = 'fa-exclamation-triangle';
        $msg = htmlspecialchars($product['product_name']) . ' — only ' . $qty . ' left';
    } else {
        $urgency = 'text-info';
        $icon = 'fa-info-circle';
        $msg = htmlspecialchars($product['product_name']) . ' — ' . $qty . ' remaining';
    }

    $notifications[] = [
        'title' => 'Low Stock Alert',
        'message' => $msg,
        'time' => 'Needs attention',
        'icon' => $icon,
        'color' => $urgency,
        'link' => 'product.php'
    ];
}
$stmt->close();

// Get expiring & expired feeds alerts
$expStmt = $connect->prepare("
    SELECT pb.batch_number, pb.expiry_date, pb.qty_remaining, p.product_name
    FROM product_batches pb
    JOIN product p ON pb.product_id = p.product_id
    WHERE pb.status = 'active' AND pb.qty_remaining > 0 AND pb.expiry_date IS NOT NULL
      AND pb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY pb.expiry_date ASC LIMIT 5
");
$expStmt->execute();
$expItems = $expStmt->get_result();
$today = date('Y-m-d');

while ($exp = $expItems->fetch_assoc()) {
    $expDate = $exp['expiry_date'];
    $pName = htmlspecialchars($exp['product_name']);
    $batch = htmlspecialchars($exp['batch_number'] ?: '');

    if ($expDate < $today) {
        $notifications[] = [
            'title' => 'Expired Feed Alert',
            'message' => $pName . ' expired on ' . date('M j, Y', strtotime($expDate)),
            'time' => 'Expired',
            'icon' => 'fa-clock',
            'color' => 'text-danger',
            'link' => 'stock_forecast.php?tab=expiry'
        ];
    } else {
        $daysLeft = (int)ceil((strtotime($expDate) - time()) / 86400);
        $notifications[] = [
            'title' => 'Expiring Feed Alert',
            'message' => $pName . ' expires in ' . $daysLeft . ' days (' . date('M j, Y', strtotime($expDate)) . ')',
            'time' => $daysLeft . 'd left',
            'icon' => 'fa-clock',
            'color' => 'text-warning',
            'link' => 'stock_forecast.php?tab=expiry'
        ];
    }
}
$expStmt->close();

// Get recent orders
$stmt = $connect->prepare("SELECT o.order_id, o.order_date, o.grand_total, o.client_name
                             FROM orders o
                             WHERE o.order_status = 1 AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                             ORDER BY o.order_id DESC
                             LIMIT 5");
$stmt->execute();
$recentOrders = $stmt->get_result();

while ($order = $recentOrders->fetch_assoc()) {
    $timeDiff = time() - strtotime($order['order_date']);

    if ($timeDiff < 3600) {
        $timeAgo = max(1, floor($timeDiff / 60)) . ' min ago';
    } elseif ($timeDiff < 86400) {
        $timeAgo = floor($timeDiff / 3600) . ' hrs ago';
    } else {
        $days = floor($timeDiff / 86400);
        $timeAgo = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }

    $notifications[] = [
        'title' => 'Order #' . intval($order['order_id']),
        'message' => htmlspecialchars($order['client_name']) . ' — $' . number_format(floatval($order['grand_total']), 2),
        'time' => $timeAgo,
        'icon' => 'fa-shopping-cart',
        'color' => 'text-success',
        'link' => 'orders.php?o=editOrd&i=' . intval($order['order_id'])
    ];
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'notifications' => array_slice($notifications, 0, 10)
]);
