<?php
require_once 'security.php';
secure_session_start();
check_user_type(1); // 1 for supervisor
require_once 'db_connection.php';
require_once 'fetch_sales.php'; // Ensure this file exists and is in the correct path
require_once 'fetch_inventory.php'; // Ensure this file exists and is in the correct path
require_once 'fetch_small_inventory.php';
require_once 'fetch_hot_items.php';

// Initialize filter variables
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-1 month'));
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');
$sales_type = isset($_POST['sales_type']) ? $_POST['sales_type'] : 'all';

// Fetch sales data based on filters
list($daily_sales, $weekly_sales, $monthly_sales) = fetch_sales_data($conn, $start_date, $end_date, $sales_type);

// Fetch inventory data
$inventory_data = fetch_inventory_data($conn);

// Fetch small inventory data
$small_inventory_data = fetch_small_inventory_data($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Supervisor Dashboard - Roti Sri Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Roti Sri Bakery</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="supervisor_management.php">Supervisor Management</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        Welcome, <?php echo htmlspecialchars($_SESSION['user']['fullname']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Supervisor Dashboard</h1>
        
        <!-- Navigation for Reports -->
        <div class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <a href="?view=sales" class="btn btn-primary btn-lg btn-block">Sales Report</a>
                </div>
                <div class="col-md-3">
                    <a href="?view=inventory" class="btn btn-secondary btn-lg btn-block">Inventory Report</a>
                </div>
                <div class="col-md-3">
                    <a href="?view=small_inventory" class="btn btn-info btn-lg btn-block">Raw Materials Inventory</a>
                </div>
                <div class="col-md-3">
                    <a href="?view=hot_items" class="btn btn-danger btn-lg btn-block">Hot Selling Items</a>
                </div>
            </div>
        </div>

        <?php
        // Determine which report to display
        $view = isset($_GET['view']) ? $_GET['view'] : 'sales';

        if ($view === 'sales') {
        ?>
            <h2>Sales Reports</h2>

            <!-- Filter Form -->
            <form method="POST" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="sales_type" class="form-label">Sales Type</label>
                        <select class="form-select" name="sales_type">
                            <option value="all" <?php echo $sales_type === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="online" <?php echo $sales_type === 'online' ? 'selected' : ''; ?>>Online</option>
                            <option value="in-store" <?php echo $sales_type === 'in-store' ? 'selected' : ''; ?>>In-Store</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Filter</button>
            </form>

            <?php if (empty($daily_sales) && empty($weekly_sales) && empty($monthly_sales)): ?>
                <div class="alert alert-warning" role="alert">
                    No sales data found for the selected date range.
                </div>
            <?php else: ?>
                <h3>Daily Sales</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_sales as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                <td>RM<?php echo number_format($sale['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3>Weekly Sales</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Week</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weekly_sales as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['sale_week']); ?></td>
                                <td>RM<?php echo number_format($sale['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3>Monthly Sales</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_sales as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['sale_month']); ?></td>
                                <td>RM<?php echo number_format($sale['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php
        } elseif ($view === 'inventory') {
        ?>
            <h2>Inventory Management</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Stock Level</th>
                        <th>Unit Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory_data as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['stock_level']); ?></td>
                            <td>RM<?php echo number_format($item['unit_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php
        } elseif ($view === 'small_inventory') {
        ?>
            <h2>Raw Materials Inventory</h2>
            <div class="mb-3">
                <a href="?view=small_inventory&sort=id" class="btn btn-primary">Sort by ID</a>
                <a href="?view=small_inventory&sort=name" class="btn btn-secondary">Sort by Name</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ingredient Name</th>
                            <th>Quantity (kg)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($small_inventory_data as $item): ?>
                            <tr <?php echo $item['Ingredient_kg'] < 20 ? 'class="table-warning"' : ''; ?>>
                                <td><?php echo htmlspecialchars($item['Inventory_ID']); ?></td>
                                <td><?php echo htmlspecialchars($item['Ingredient_Name']); ?></td>
                                <td><?php echo number_format($item['Ingredient_kg'], 2); ?></td>
                                <td>
                                    <?php if ($item['Ingredient_kg'] < 20): ?>
                                        <span class="badge bg-warning">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Sufficient</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php
        } elseif ($view === 'hot_items') {
            $hot_items = fetch_hot_items($conn, $start_date, $end_date);
        ?>
            <h2>Hot Selling Items</h2>
            
            <!-- Filter Form -->
            <form method="POST" class="mb-4">
                <div class="row">
                    <div class="col-md-5">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control">Filter</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Product Name</th>
                            <th>Total Quantity Sold</th>
                            <th>Total Revenue</th>
                            <th>Percentage of Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($hot_items as $item): 
                        ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo number_format($item['total_quantity']); ?></td>
                                <td>RM<?php echo number_format($item['total_revenue'], 2); ?></td>
                                <td><?php echo number_format($item['sales_percentage'], 1); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>