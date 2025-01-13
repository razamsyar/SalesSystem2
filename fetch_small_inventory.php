<?php
function fetch_small_inventory_data($conn) {
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
    
    $order_by = match($sort) {
        'id' => 'Inventory_ID',
        'name' => 'Ingredient_Name',
        default => 'Ingredient_Name'
    };
    
    $query = "SELECT * FROM small_inventory ORDER BY {$order_by}";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return array();
    }
    
    $small_inventory_data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $small_inventory_data[] = $row;
    }
    
    return $small_inventory_data;
} 