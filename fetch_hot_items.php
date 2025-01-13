<?php
function fetch_hot_items($conn, $start_date, $end_date) {
    $query = "
        SELECT 
            p.product_name,
            SUM(od.quantity) as total_quantity,
            SUM(od.quantity * od.unit_price) as total_revenue,
            (SUM(od.quantity * od.unit_price) / (
                SELECT SUM(quantity * unit_price)
                FROM order_items od2
                JOIN orders o2 ON od2.order_id = o2.id
                WHERE o2.order_date BETWEEN ? AND ?
                AND o2.payment_status = 'paid'
                AND o2.payment_method IN ('cash', 'card', 'online')
            ) * 100) as sales_percentage
        FROM order_items od
        JOIN inventory p ON od.product_id = p.id
        JOIN orders o ON od.order_id = o.id
        WHERE o.order_date BETWEEN ? AND ?
        AND o.payment_status = 'paid'
        AND o.payment_method IN ('cash', 'card', 'online')
        GROUP BY p.id, p.product_name
        ORDER BY total_quantity DESC
        LIMIT 10
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssss', $start_date, $end_date, $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $hot_items = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $hot_items[] = $row;
        }
        
        return $hot_items;
    }
    
    return array(); // Return empty array if query preparation fails
} 