<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function fetch_sales_data($conn, $start_date, $end_date, $sales_type) {
    // Build the WHERE clause based on filters
    $where_clause = "WHERE order_date BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    $param_types = "ss";

    if ($sales_type !== 'all') {
        $where_clause .= " AND order_type = ?";
        $params[] = $sales_type;
        $param_types .= "s";
    }

    // Fetch daily sales data
    $daily_sales = $conn->prepare("SELECT DATE(order_date) AS sale_date, SUM(total_amount) AS total_revenue 
                                    FROM orders $where_clause 
                                    GROUP BY sale_date");
    $daily_sales->bind_param($param_types, ...$params);
    $daily_sales->execute();
    $daily_sales = $daily_sales->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch weekly sales data
    $weekly_sales = $conn->prepare("SELECT YEARWEEK(order_date) AS sale_week, SUM(total_amount) AS total_revenue 
                                     FROM orders $where_clause 
                                     GROUP BY sale_week");
    $weekly_sales->bind_param($param_types, ...$params);
    $weekly_sales->execute();
    $weekly_sales = $weekly_sales->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch monthly sales data
    $monthly_sales = $conn->prepare("SELECT DATE_FORMAT(order_date, '%Y-%m') AS sale_month, SUM(total_amount) AS total_revenue 
                                      FROM orders $where_clause 
                                      GROUP BY sale_month");
    $monthly_sales->bind_param($param_types, ...$params);
    $monthly_sales->execute();
    $monthly_sales = $monthly_sales->get_result()->fetch_all(MYSQLI_ASSOC);

    return [$daily_sales, $weekly_sales, $monthly_sales];
}
?> 