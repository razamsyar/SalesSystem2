<?php
require_once 'security.php';
secure_session_start();
check_user_type(3); // Ensure user is a guest

require 'db_connection.php';

// Add CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch available products with category grouping
$stmt = $conn->prepare("SELECT * FROM inventory WHERE status = 'active' AND stock_level > 0 ORDER BY category, product_name");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group products by category
$categorized_products = [];
foreach ($products as $product) {
    $categorized_products[$product['category']][] = $product;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }
    
    try {
        $conn->begin_transaction();
        
        $guest_id = $_SESSION['user']['id'];
        $order_number = 'ORD' . time();
        $total_amount = 0;
        $delivery_address = htmlspecialchars($_POST['delivery_address']);
        $payment_method = $_POST['payment_method'];
        
        // Validate if any items are selected
        $has_items = false;
        foreach ($_POST['items'] as $quantity) {
            if ($quantity > 0) {
                $has_items = true;
                break;
            }
        }
        
        if (!$has_items) {
            throw new Exception("Please select at least one item");
        }
        
        // Calculate total amount
        foreach ($_POST['items'] as $item_id => $quantity) {
            if ($quantity > 0) {
                $stmt = $conn->prepare("SELECT unit_price, stock_level FROM inventory WHERE id = ?");
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                if ($quantity > $product['stock_level']) {
                    throw new Exception("Insufficient stock for some items");
                }
                
                $total_amount += $product['unit_price'] * $quantity;
            }
        }
        
        // Create order
        $stmt = $conn->prepare("
            INSERT INTO orders (
                guest_id, 
                order_number, 
                order_date, 
                total_amount, 
                status,
                payment_method,
                payment_status,
                delivery_address, 
                order_type, 
                clerk_id
            ) VALUES (
                ?, ?, NOW(), ?, 'pending', ?, 'pending', ?, 'online', NULL
            )
        ");
        $stmt->bind_param("isdss", $guest_id, $order_number, $total_amount, $payment_method, $delivery_address);
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        // Add order items and update inventory
        foreach ($_POST['items'] as $item_id => $quantity) {
            if ($quantity > 0) {
                $stmt = $conn->prepare("SELECT unit_price FROM inventory WHERE id = ?");
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                $subtotal = $product['unit_price'] * $quantity;
                
                // Add order item
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiids", $order_id, $item_id, $quantity, $product['unit_price'], $subtotal);
                $stmt->execute();
                
                // Update inventory
                $stmt = $conn->prepare("UPDATE inventory SET stock_level = stock_level - ? WHERE id = ?");
                $stmt->bind_param("ii", intdiv($quantity, 2), $item_id);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = "Order placed successfully! Order number: " . $order_number;
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error placing order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Place Order - Roti Sri Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .category-section {
            margin-bottom: 2rem;
        }
        #cart-summary {
            position: sticky;
            top: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="guest_dashboard.php">Roti Sri Bakery</a>
            <div>
                <span class="text-light me-3">Welcome, <?php 
                    try {
                        if (!isset($_SESSION['user']['fullname'])) {
                            throw new Exception('User fullname not found');
                        }
                        if (!is_string($_SESSION['user']['fullname'])) {
                            throw new Exception('Invalid fullname type');
                        }
                        // Only show first name and limit length
                        $fullname = explode(' ', $_SESSION['user']['fullname'])[0];
                        $fullname = substr($fullname, 0, 15);
                        echo htmlspecialchars(
                            isset($fullname) && is_string($fullname) ? $fullname : 'Guest', 
                            ENT_QUOTES, 
                            'UTF-8'
                        );
                    } catch (Exception $e) {
                        // Log error securely without exposing details
                        error_log('User Display Error: ' . $e->getMessage());
                        // Show generic user label
                        echo sanitize_output('Guest');
                    }
                ?></span>
                <a href="guest_dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Place Your Order</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                    try {
                        if (!isset($_SESSION['error'])) {
                            throw new Exception('No error message found');
                        }
                        
                        // Define allowed error messages
                        $allowed_errors = [
                            'INVALID_QUANTITY' => 'Please enter a valid quantity',
                            'INSUFFICIENT_STOCK' => 'Insufficient stock available',
                            'INVALID_ADDRESS' => 'Please provide a valid delivery address',
                            'INVALID_PAYMENT' => 'Invalid payment method selected',
                            'ORDER_FAILED' => 'Failed to place order. Please try again'
                        ];
                        
                        // Map error code to safe message or use generic message
                        $error_code = $_SESSION['error'];
                        $safe_message = isset($allowed_errors[$error_code]) 
                            ? $allowed_errors[$error_code] 
                            : 'An error occurred. Please try again.';
                            
                        echo sanitize_output($safe_message);
                    } catch (Exception $e) {
                        // Log error securely without exposing details
                        error_log('Error Display Error: ' . $e->getMessage());
                        // Show generic error message
                        echo sanitize_output('An error occurred. Please try again.');
                    }
                    // Clear the error message
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php 
                if (!isset($_SESSION['csrf_token'])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }
                echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); 
            ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <?php foreach ($categorized_products as $category => $items): ?>
                        <div class="category-section">
                            <h3><?php echo htmlspecialchars($category); ?></h3>
                            <div class="row">
                                <?php foreach ($items as $product): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card product-card">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                                                <p class="card-text">Price: RM<?php echo number_format($product['unit_price'], 2); ?></p>
                                                <div class="input-group">
                                                    <span class="input-group-text">Quantity</span>
                                                    <input type="number" class="form-control item-quantity" 
                                                           name="items[<?php echo $product['id']; ?>]" 
                                                           data-price="<?php echo $product['unit_price']; ?>"
                                                           min="0" max="<?php echo $product['stock_level']; ?>" value="0">
                                                </div>
                                                <small class="text-muted">Available: <?php echo $product['stock_level']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card" id="cart-summary">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Total Items: <span id="total-items">0</span></label>
                                <br>
                                <label class="form-label">Total Amount: RM<span id="total-amount">0.00</span></label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Delivery Address</label>
                                <textarea class="form-control" name="delivery_address" required><?php 
                                    $address = isset($_SESSION['user']['address']) ? $_SESSION['user']['address'] : '';
                                    // Remove any potentially sensitive information
                                    $address = preg_replace('/[^a-zA-Z0-9\s\-\,\.\#\/]/', '', $address);
                                    // Limit length to prevent excessive data exposure
                                    $address = substr($address, 0, 200);
                                    echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); 
                                ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="cash">Cash on Delivery</option>
                                    <option value="card">Credit/Debit Card</option>
                                    <option value="online">Online Banking</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Place Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php 
        // Ensure nonce is a valid base64 value
        $nonce = isset($_SESSION['csrf_token']) ? 
            base64_encode(hash('sha256', $_SESSION['csrf_token'], true)) : 
            base64_encode(random_bytes(32));
        echo htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8'); 
    ?>">
        // Calculate totals
        document.querySelectorAll('.item-quantity').forEach(input => {
            input.addEventListener('change', calculateTotals);
        });

        function calculateTotals() {
            let totalItems = 0;
            let totalAmount = 0;
            
            document.querySelectorAll('.item-quantity').forEach(input => {
                const quantity = parseInt(input.value) || 0;
                const price = parseFloat(input.dataset.price);
                
                totalItems += quantity;
                totalAmount += quantity * price;
            });
            
            document.getElementById('total-items').textContent = totalItems;
            document.getElementById('total-amount').textContent = totalAmount.toFixed(2);
        }
    </script>
</body>
</html> 