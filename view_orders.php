<?php
require_once 'security.php';
secure_session_start();
check_user_type(3); // Ensure user is a guest
require_once 'db_connection.php';

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Filter settings
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'order_date';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Build query
$where_clauses = ["o.guest_id = ?"];
$params = [$_SESSION['user']['id']];
$types = "i";

if ($status_filter) {
    $where_clauses[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_from) {
    $where_clauses[] = "DATE(o.order_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $where_clauses[] = "DATE(o.order_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_clauses);

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total FROM orders o WHERE " . $where_clause;
$stmt = $conn->prepare($count_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Get orders
$query = "
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(CONCAT(oi.quantity, 'x ', i.product_name) SEPARATOR ', ') as items
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    LEFT JOIN inventory i ON oi.product_id = i.id
    WHERE {$where_clause}
    GROUP BY o.id 
    ORDER BY {$sort_by} {$sort_order}
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders - Roti Sri Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="guest_dashboard.php">Roti Sri Bakery</a>
            <a href="guest_dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>My Orders</h2>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="ready" <?php echo $status_filter == 'ready' ? 'selected' : ''; ?>>Ready</option>
                            <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select">
                            <option value="order_date" <?php echo $sort_by == 'order_date' ? 'selected' : ''; ?>>Order Date</option>
                            <option value="total_amount" <?php echo $sort_by == 'total_amount' ? 'selected' : ''; ?>>Amount</option>
                            <option value="status" <?php echo $sort_by == 'status' ? 'selected' : ''; ?>>Status</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="view_orders.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">No orders found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                            <td>
                                <small><?php echo htmlspecialchars($order['items']); ?></small>
                            </td>
                            <td>RM<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($order['status']) {
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'processing' => 'primary',
                                        'ready' => 'success',
                                        'delivered' => 'secondary',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="order_confirmation.php?order_id=<?php echo htmlspecialchars($order['id'], ENT_QUOTES, 'UTF-8'); ?>" 
                                   class="btn btn-sm btn-info" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($order['status'] == 'pending'): ?>
                                <a href="cancel_order.php?order_id=<?php echo htmlspecialchars($order['id'], ENT_QUOTES, 'UTF-8'); ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to cancel this order?')"
                                   title="Cancel Order">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo htmlspecialchars($page-1, ENT_QUOTES, 'UTF-8'); ?>&status=<?php echo htmlspecialchars($status_filter, ENT_QUOTES, 'UTF-8'); ?>&date_from=<?php echo htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8'); ?>&date_to=<?php echo htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8'); ?>&sort=<?php echo htmlspecialchars($sort_by, ENT_QUOTES, 'UTF-8'); ?>&order=<?php echo htmlspecialchars($sort_order, ENT_QUOTES, 'UTF-8'); ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo htmlspecialchars($i, ENT_QUOTES, 'UTF-8'); ?>&status=<?php echo htmlspecialchars($status_filter, ENT_QUOTES, 'UTF-8'); ?>&date_from=<?php echo htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8'); ?>&date_to=<?php echo htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8'); ?>&sort=<?php echo htmlspecialchars($sort_by, ENT_QUOTES, 'UTF-8'); ?>&order=<?php echo htmlspecialchars($sort_order, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($i, ENT_QUOTES, 'UTF-8'); ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo htmlspecialchars($page+1, ENT_QUOTES, 'UTF-8'); ?>&status=<?php echo htmlspecialchars($status_filter, ENT_QUOTES, 'UTF-8'); ?>&date_from=<?php echo htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8'); ?>&date_to=<?php echo htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8'); ?>&sort=<?php echo htmlspecialchars($sort_by, ENT_QUOTES, 'UTF-8'); ?>&order=<?php echo htmlspecialchars($sort_order, ENT_QUOTES, 'UTF-8'); ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 