<?php
require_once 'security.php';
secure_session_start();
check_user_type(3); // 3 for guest type
require_once 'db_connection.php';

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Prevent Clickjacking attacks
header('X-Frame-Options: SAMEORIGIN');
// Additional security headers
header('Content-Security-Policy: frame-ancestors \'self\'');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Guest Dashboard - Roti Sri Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net 'nonce-<?php echo $_SESSION['csrf_token']; ?>'; style-src 'self' https://cdn.jsdelivr.net;">
    <style>
        .card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand">Roti Sri Bakery</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="guest_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_orders.php">My Orders</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo sanitize_output($_SESSION['user']['fullname']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="edit_profile.php">Edit Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="logout.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo sanitize_output($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Profile Information</h5>
                        <div class="mb-3">
                            <strong>Name:</strong> <?php echo sanitize_output($_SESSION['user']['fullname']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Email:</strong> <?php echo sanitize_output($_SESSION['user']['email']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Contact:</strong> <?php echo sanitize_output($_SESSION['user']['contact']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Address:</strong><br>
                            <?php echo nl2br(sanitize_output($_SESSION['user']['address'])); ?>
                        </div>
                        <a href="edit_profile.php" class="btn btn-primary w-100">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Recent Orders</h5>
                        <div class="list-group">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT o.*, COUNT(oi.id) as item_count 
                                FROM orders o 
                                LEFT JOIN order_items oi ON o.id = oi.order_id 
                                WHERE o.guest_id = ? 
                                GROUP BY o.id 
                                ORDER BY o.order_date DESC 
                                LIMIT 5
                            ");
                            $stmt->bind_param("i", $_SESSION['user']['id']);
                            $stmt->execute();
                            $recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            if (empty($recent_orders)): ?>
                                <div class="text-center text-muted py-3">No orders yet</div>
                            <?php else:
                                foreach ($recent_orders as $order): ?>
                                    <a href="order_confirmation.php?order_id=<?php echo $order['id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Order #<?php echo htmlspecialchars($order['order_number']); ?></h6>
                                            <span class="badge bg-<?php echo $order['status'] == 'delivered' ? 'success' : 'primary'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                            </span>
                                        </div>
                                        <p class="mb-1"><?php echo $order['item_count']; ?> items - RM<?php echo number_format($order['total_amount'], 2); ?></p>
                                        <small class="text-muted"><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></small>
                                    </a>
                            <?php endforeach;
                            endif; ?>
                        </div>
                        <div class="card-footer text-center">
                            <a href="view_orders.php" class="btn btn-primary btn-sm">View All Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title">Quick Actions</h5>
                            <a href="place_order.php" class="btn btn-primary">
                                <i class="bi bi-cart-plus"></i> Place New Order
                            </a>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <a href="menu.php" class="btn btn-outline-secondary w-100 mb-2">
                                    <i class="bi bi-book"></i> Browse Menu
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="view_orders.php" class="btn btn-outline-secondary w-100 mb-2">
                                    <i class="bi bi-clock-history"></i> Order History
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="track_order.php" class="btn btn-outline-secondary w-100 mb-2">
                                    <i class="bi bi-truck"></i> Track Order
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="edit_profile.php" class="btn btn-outline-secondary w-100 mb-2">
                                    <i class="bi bi-person"></i> Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Featured Products</h5>
                        <div class="row">
                            <?php
                            // Fetch featured products from inventory
                            $stmt = $conn->prepare("SELECT * FROM inventory WHERE status = 'active' AND stock_level > 0 LIMIT 4");
                            $stmt->execute();
                            $featured_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            foreach ($featured_products as $product): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo sanitize_output($product['product_name']); ?></h6>
                                            <p class="card-text">
                                                RM<?php echo number_format($product['unit_price'], 2); ?>
                                            </p>
                                            <a href="place_order.php?product=<?php echo $product['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                Order Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Special Offers</h5>
                        <?php
                        // Fetch active promotions
                        $stmt = $conn->prepare("SELECT * FROM promotions WHERE status = 'active' AND NOW() BETWEEN start_date AND end_date LIMIT 3");
                        $stmt->execute();
                        $promotions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        
                        foreach ($promotions as $promo): ?>
                            <div class="alert alert-info">
                                <h6><?php echo sanitize_output($promo['code']); ?></h6>
                                <p class="mb-0"><?php echo sanitize_output($promo['description']); ?></p>
                            </div>
                        <?php endforeach; ?>
                        
                        <a href="promotions.php" class="btn btn-outline-primary w-100">View All Offers</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light mt-5 py-3">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> Roti Sri Bakery. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="privacy.php" class="text-light me-3">Privacy Policy</a>
                    <a href="terms.php" class="text-light">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $_SESSION['csrf_token']; ?>">
        // Add any necessary JavaScript here
    </script>
</body>
</html> 