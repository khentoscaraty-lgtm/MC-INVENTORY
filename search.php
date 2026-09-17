<?php require_once 'includes/header_sidebar.php'; ?>

<?php 
global $connect;
$query = isset($_GET['query']) ? $_GET['query'] : '';
$results = [];

if (!empty($query)) {
    // Search products
    $stmt = $connect->prepare("SELECT p.*, b.brand_name, c.categories_name AS category_name
                             FROM product p
                             LEFT JOIN brands b ON p.brand_id = b.brand_id
                             LEFT JOIN categories c ON p.categories_id = c.categories_id
                             WHERE (p.product_name LIKE ? OR CONCAT('PRD-', LPAD(p.product_id, 6, '0')) LIKE ?)
                             AND p.status = 1
                             ORDER BY p.product_name
                             LIMIT 20");
    $searchTerm = "%{$query}%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $products = $stmt->get_result();
    
    // Search orders
    $stmt = $connect->prepare("SELECT o.*, u.username 
                             FROM orders o 
                             JOIN users u ON o.user_id = u.user_id 
                             WHERE o.order_id LIKE ? OR o.grand_total LIKE ? 
                             ORDER BY o.order_date DESC 
                             LIMIT 20");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $orders = $stmt->get_result();
}
?>

<style>
.search-container {
    max-width: 1200px;
    margin: 0 auto;
}

.search-header {
    background: var(--card-bg);
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    padding: 2rem;
    margin-bottom: 2rem;
}

.search-results {
    background: var(--card-bg);
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    padding: 2rem;
}

.result-section {
    margin-bottom: 2rem;
}

.result-section h5 {
    color: var(--text);
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--primary);
}

.result-item {
    padding: 1rem;
    border: 1px solid var(--border-lightest);
    border-radius: 10px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.result-item:hover {
    background: var(--main-bg);
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.result-item h6 {
    color: var(--text);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.result-item .meta {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.no-results {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
}

.no-results i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--border);
}

.search-stats {
    background: var(--primary-light);
    border: 1px solid rgba(232,163,23,0.2);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 2rem;
    color: var(--primary-dark);
}

.search-stats strong {
    color: var(--primary);
}
</style>

<div class="search-container">
    <!-- Search Header -->
    <div class="search-header">
        <h3 class="mb-3">Search Results</h3>
        
        <form method="GET" action="search.php" class="mb-3">
            <div class="input-group">
                <input type="text" name="query" class="form-control form-control-lg" 
                       placeholder="Search products, orders..." 
                       value="<?php echo htmlspecialchars($query); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search me-2"></i>Search
                </button>
            </div>
        </form>

        <?php if (!empty($query)): ?>
            <div class="search-stats">
                <i class="fas fa-info-circle me-2"></i>
                Showing results for: <strong>"<?php echo htmlspecialchars($query); ?>"</strong>
            </div>
        <?php endif; ?>
    </div>

    <!-- Search Results -->
    <div class="search-results">
        <?php if (empty($query)): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h4>Enter a search term</h4>
                <p>Search for products, orders, or anything else in your inventory system.</p>
            </div>
        <?php else: ?>
            <!-- Products Results -->
            <div class="result-section">
                <h5><i class="fas fa-box me-2"></i>Products (<?php echo $products->num_rows; ?>)</h5>
                
                <?php if ($products->num_rows > 0): ?>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="result-item" onclick="window.location.href='product.php?view=<?php echo $product['product_id']; ?>'">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                    <div class="meta">
                                        <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($product['brand_name'] ?? 'No Brand'); ?></span>
                                        <span class="badge bg-info me-2"><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></span>
                                        <span class="badge bg-success">Stock: <?php echo $product['quantity']; ?></span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <strong class="text-primary">₱<?php echo number_format($product['rate'], 2); ?></strong>
                                    <br>
                                    <small class="text-muted">Barcode: <?php echo htmlspecialchars(sprintf('PRD-%06d', $product['product_id'])); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-box"></i>
                        <h5>No products found</h5>
                        <p>Try searching with different keywords.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Orders Results -->
            <div class="result-section">
                <h5><i class="fas fa-shopping-cart me-2"></i>Orders (<?php echo $orders->num_rows; ?>)</h5>
                
                <?php if ($orders->num_rows > 0): ?>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <div class="result-item" onclick="window.location.href='orders.php?view=<?php echo $order['order_id']; ?>'">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6>Order #<?php echo $order['order_id']; ?></h6>
                                    <div class="meta">
                                        <span class="badge bg-primary me-2"><?php echo htmlspecialchars($order['username']); ?></span>
                                        <span class="badge bg-info"><?php echo date('M j, Y h:i A', strtotime($order['order_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <strong class="text-success">₱<?php echo number_format($order['grand_total'], 2); ?></strong>
                                    <br>
                                    <small class="text-muted">Paid: ₱<?php echo number_format($order['paid'], 2); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-shopping-cart"></i>
                        <h5>No orders found</h5>
                        <p>Try searching with different order numbers or amounts.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($products->num_rows == 0 && $orders->num_rows == 0): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h4>No results found</h4>
                    <p>We couldn't find anything matching "<strong><?php echo htmlspecialchars($query); ?></strong>"</p>
                    <p>Try:</p>
                    <ul class="list-unstyled">
                        <li>• Checking your spelling</li>
                        <li>• Using more general terms</li>
                        <li>• Searching for different keywords</li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>
