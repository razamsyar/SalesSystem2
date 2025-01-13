<?php
require_once 'security.php';
secure_session_start();
check_user_type(3); // Ensure user is a guest

require 'db_connection.php';

// Include the helper functions from above
function computeMaxAvailability(mysqli $conn, int $productID): int
{
    $stmt = $conn->prepare("
        SELECT si.Ingredient_kg, pi.Quantity_Needed
        FROM productingredients pi
        JOIN small_inventory si ON pi.Inventory_ID = si.Inventory_ID
        WHERE pi.Product_ID = ?
    ");
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return 0;
    }
    
    $maxUnits = PHP_INT_MAX;
    
    while ($row = $result->fetch_assoc()) {
        $ingredientKg    = (float)$row['Ingredient_kg'];
        $quantityNeeded  = (float)$row['Quantity_Needed'];
        
        if ($quantityNeeded <= 0) {
            continue;
        }
        
        $unitsPossible = floor($ingredientKg / $quantityNeeded);
        $maxUnits      = min($maxUnits, $unitsPossible);
    }
    
    return $maxUnits === PHP_INT_MAX ? 0 : $maxUnits;
}

function deductIngredients(mysqli $conn, int $productID, float $quantity): void
{
    $stmt = $conn->prepare("
        SELECT si.Inventory_ID, si.Ingredient_kg, pi.Quantity_Needed
        FROM productingredients pi
        JOIN small_inventory si ON pi.Inventory_ID = si.Inventory_ID
        WHERE pi.Product_ID = ?
    ");
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $currentKg      = (float)$row['Ingredient_kg'];
        $quantityNeeded = (float)$row['Quantity_Needed'];
        
        $newKg = $currentKg - ($quantity * $quantityNeeded);
        if ($newKg < 0) {
            throw new Exception("Not enough ingredients to fulfill the order for product #$productID");
        }
        
        $updateStmt = $conn->prepare("UPDATE small_inventory SET Ingredient_kg = ? WHERE Inventory_ID = ?");
        $updateStmt->bind_param("di", $newKg, $row['Inventory_ID']);
        $updateStmt->execute();
    }
}

// Add CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch products (ignoring stock_level, but still ignoring inactive)
$stmt = $conn->prepare("
    SELECT * 
    FROM inventory 
    WHERE status = 'active'
    ORDER BY category, product_name
");
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
        
        $guest_id         = $_SESSION['user']['id'];
        $order_number     = 'ORD' . time();
        $total_amount     = 0;
        $delivery_address = htmlspecialchars($_POST['delivery_address']);
        $payment_method   = $_POST['payment_method'];
        
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
        
        // Calculate total amount & check availability from small_inventory
        foreach ($_POST['items'] as $item_id => $quantity) {
            if ($quantity > 0) {
                // Get the unit_price from 'inventory'
                $stmt = $conn->prepare("SELECT unit_price, product_name FROM inventory WHERE id = ?");
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                // Check if we can make 'quantity' units based on small_inventory
                $maxUnits = computeMaxAvailability($conn, $item_id);
                if ($quantity > $maxUnits) {
                    throw new Exception(
                        "Not enough ingredients to produce '$product[product_name]'. 
                         Maximum available is $maxUnits."
                    );
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
        $stmt->bind_param("isdss", 
            $guest_id, 
            $order_number, 
            $total_amount, 
            $payment_method, 
            $delivery_address
        );
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        // Add order items and update small_inventory
        foreach ($_POST['items'] as $item_id => $quantity) {
            if ($quantity > 0) {
                // Grab product info again
                $stmt = $conn->prepare("SELECT unit_price, product_name FROM inventory WHERE id = ?");
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                // Insert into order_items
                $subtotal = $product['unit_price'] * $quantity;
                
                $stmt = $conn->prepare("
                    INSERT INTO order_items 
                    (order_id, product_id, quantity, unit_price, subtotal) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iiids", 
                    $order_id, 
                    $item_id, 
                    $quantity, 
                    $product['unit_price'], 
                    $subtotal
                );
                $stmt->execute();
                
                // Deduct from small_inventory
                deductIngredients($conn, $item_id, $quantity);
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
            <span class="text-light me-3">
                Welcome, <?php echo sanitize_output($_SESSION['user']['fullname']); ?>
            </span>
            <a href="guest_dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Place Your Order</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo sanitize_output($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="row">
            <div class="col-md-8">
                <?php foreach ($categorized_products as $category => $items): ?>
                    <div class="category-section">
                        <h3><?php echo htmlspecialchars($category ?: 'General'); ?></h3>
                        <div class="row">
                            <?php foreach ($items as $product): ?>
                                <?php 
                                    // Compute maximum availability based on ingredients
                                    $availableQty = computeMaxAvailability($conn, $product['id']); 
                                ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card product-card">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($product['product_name']); ?>
                                            </h5>
                                            <p class="card-text">
                                                <?php echo htmlspecialchars($product['description']); ?>
                                            </p>
                                            <p class="card-text">
                                                Price: RM<?php echo number_format($product['unit_price'], 2); ?>
                                            </p>
                                            <div class="input-group">
                                                <span class="input-group-text">Quantity</span>
                                                <input type="number" 
                                                       class="form-control item-quantity" 
                                                       name="items[<?php echo $product['id']; ?>]" 
                                                       data-price="<?php echo $product['unit_price']; ?>"
                                                       min="0" 
                                                       max="<?php echo $availableQty; ?>" 
                                                       value="0">
                                            </div>
                                            <small class="text-muted">
                                                Available: <?php echo $availableQty; ?>
                                            </small>
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
                            <textarea class="form-control" name="delivery_address" required>
                                <?php echo htmlspecialchars($_SESSION['user']['address']); ?>
                            </textarea>
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
<script nonce="<?php echo $_SESSION['csrf_token']; ?>">
    // Calculate totals in real time
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