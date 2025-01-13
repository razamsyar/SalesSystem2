<?php
require_once 'security.php';
secure_session_start();
require_once 'db_connection.php';

// Check if order_id is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die('Invalid order ID.');
}

$order_id = (int)$_GET['order_id'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT 
        o.order_number,
        o.order_date,
        o.total_amount,
        o.payment_method,
        g.fullname as customer_name,
        g.contact as customer_contact,
        GROUP_CONCAT(
            CONCAT(oi.quantity, 'x ', i.product_name, ' (RM', FORMAT(oi.unit_price, 2), ')')
            SEPARATOR ', '
        ) as items
    FROM orders o
    LEFT JOIN guest g ON o.guest_id = g.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN inventory i ON oi.product_id = i.id
    WHERE o.id = ?
    GROUP BY o.id
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die('Order not found.');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Receipt - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center">Receipt</h2>
        <h5 class="text-center">Order Number: <?php echo htmlspecialchars($order['order_number']); ?></h5>
        <p><strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></p>
        <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['customer_contact']); ?></p>
        
        <h5>Items:</h5>
        <ul>
            <?php foreach (explode(', ', $order['items']) as $item): ?>
                <li><?php echo htmlspecialchars($item); ?></li>
            <?php endforeach; ?>
        </ul>

        <h5>Total Amount: RM<?php echo number_format($order['total_amount'], 2); ?></h5>
        <p><strong>Payment Method:</strong> <?php echo ucfirst(htmlspecialchars($order['payment_method'])); ?></p>

        <div class="text-center mt-4">
            <button class="btn btn-primary no-print" onclick="window.print()">Print Receipt</button>
            <a href="clerk_dashboard.php" class="btn btn-secondary no-print">Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 