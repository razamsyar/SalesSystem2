<?php
function fetch_inventory_data($conn) {
    $inventory_query = $conn->query("SELECT id, product_name, stock_level, unit_price FROM inventory WHERE status = 'active'");
    return $inventory_query->fetch_all(MYSQLI_ASSOC);
}
?> 