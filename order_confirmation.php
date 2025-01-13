<?php
require_once 'security.php';
secure_session_start();
check_user_type(3); // Ensure user is a guest
require 'db_connection.php';

if (!isset($_GET['order_id'])) {
    header("Location: guest_dashboard.php");
    exit();
}

$order_id = (int)$_GET['order_id'];
$guest_id = $_SESSION['user']['id'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, oi.*, i.product_name 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN inventory i ON oi.product_id = i.id 
    WHERE o.id = ? AND o.guest_id = ?
");
$stmt->bind_param("ii", $order_id, $guest_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);

if (empty($order_items)) {
    header("Location: guest_dashboard.php");
    exit();
}

$order = $order_items[0]; // Get order details from first row
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation - Roti Sri Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="guest_dashboard.php">Roti Sri Bakery</a>
            <a href="guest_dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Order Confirmation</h2>
                
                <div class="alert alert-success">
                    Your order has been placed successfully!
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Order Details</h5>
                        <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                        <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($order['status'])); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Delivery Information</h5>
                        <p><strong>Delivery Address:</strong><br><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                    </div>
                </div>

                <h5>Order Items</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>RM<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td>RM<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                            <td><strong>RM<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>

                <div class="text-center mt-4">
                    <a href="guest_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    <a href="track_order.php?order_id=<?php echo $order_id; ?>" class="btn btn-info">Track Order</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 