-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 28, 2024 at 07:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sales_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `daily_sales`
--

CREATE TABLE `daily_sales` (
  `id` int(11) NOT NULL,
  `clerk_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_orders` int(11) DEFAULT 0,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `cash_orders` int(11) DEFAULT 0,
  `cash_sales` decimal(10,2) DEFAULT 0.00,
  `card_orders` int(11) DEFAULT 0,
  `card_sales` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_sales`
--

INSERT INTO `daily_sales` (`id`, `clerk_id`, `date`, `total_orders`, `total_sales`, `cash_orders`, `cash_sales`, `card_orders`, `card_sales`, `created_at`) VALUES
(5, 16, '2024-12-28', 2, 16.00, 2, 16.00, 0, 0.00, '2024-12-28 03:45:51');

-- --------------------------------------------------------

--
-- Table structure for table `employee_ids`
--

CREATE TABLE `employee_ids` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `fullname` varchar(200) NOT NULL,
  `role` enum('Supervisor','Clerk') NOT NULL,
  `assigned` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_ids`
--

INSERT INTO `employee_ids` (`id`, `employee_id`, `fullname`, `role`, `assigned`, `created_at`) VALUES
(1, 'SV-1001', 'Alice Johnson', 'Supervisor', 0, '2024-12-28 02:43:55'),
(2, 'SV-1002', 'Bob Smith', 'Supervisor', 1, '2024-12-28 02:43:55'),
(3, 'CL-2001', 'Charlie Brown', 'Clerk', 0, '2024-12-28 02:43:55'),
(4, 'CL-2002', 'Diana Prince', 'Clerk', 1, '2024-12-28 02:43:55');

-- --------------------------------------------------------

--
-- Table structure for table `guest`
--

CREATE TABLE `guest` (
  `id` int(11) NOT NULL,
  `fullname` varchar(200) NOT NULL,
  `contact` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `salt` varchar(64) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 3 COMMENT '1=SalesSV, 2=Clerks, 3=Guest',
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `employee_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guest`
--

INSERT INTO `guest` (`id`, `fullname`, `contact`, `address`, `email`, `password`, `salt`, `type`, `date_created`, `employee_id`) VALUES
(15, 'Bob Smith', '0112223333', '', 'bob@gmail.com', '$2y$10$t4jxQs6rBbqH4x14B00lf.0Bt1/xJsfbEOwuqAtwRk5HgLzpRM6EG', '', 1, '2024-12-28 11:09:06', 'SV-1002'),
(16, 'Diana Prince', '01122233332', '', 'diana@gmail.com', '$2y$10$s1OvABlalF5X66aXXHscaOIV2BQD1ynvAqghtxAuuPqcUPv3LEcuO', '', 2, '2024-12-28 11:11:12', 'CL-2002'),
(19, 'Alif Ibrahim', '01234567890', '-', 'walk-in-1735397966', '', '', 3, '2024-12-28 22:59:26', NULL),
(20, 'Tuan Muhammad Farhan Bin Tuan Rashid', '0197909367', 'Jalan Surabaya', 'farhanrashid293@gmail.com', '$2y$10$8y/CVwWah74WMI0AQygoq.SfPHB2Eds/eCk3W3ItdilZYfSgGdag.', '', 3, '2024-12-28 23:19:20', ''),
(21, 'Abqari', '0197909311', 'NO S-48/50-A BATU 1  1/2\r\nJALAN PENGKALAN CHEPA', 'abqari@gmail.com', '$2y$10$EDVI7OCu9XxTZ3KCT/fad.bMw3zbZI.5boG6RJgvHAIUqGoOTHP3q', '', 3, '2024-12-29 01:43:48', '');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `stock_level` int(11) NOT NULL,
  `reorder_point` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `product_name`, `description`, `category`, `unit_price`, `stock_level`, `reorder_point`, `status`, `created_at`, `updated_at`) VALUES
(9, 'Classic Butter Bun', NULL, '', 1.50, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 07:04:55'),
(10, 'Chocolate Croissant', NULL, '', 2.00, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:36:37'),
(11, 'Almond Danish', NULL, '', 2.50, 98, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 18:19:59'),
(12, 'Cinnamon Roll Delight', NULL, '', 2.00, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 18:19:31'),
(13, 'Roti Canai Special', NULL, '', 1.20, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:36:46'),
(14, 'Pineapple Tart', NULL, '', 3.00, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:36:50'),
(15, 'Red Bean Bun', NULL, '', 1.80, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:36:53'),
(16, 'Mango Mousse Cake', NULL, '', 5.00, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:37:40'),
(17, 'Cheese Puff Pastry', NULL, '', 2.80, 96, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 18:20:16'),
(18, 'Matcha Green Tea Cake', NULL, '', 4.50, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:37:34'),
(19, 'Blueberry Muffin', NULL, '', 1.50, 96, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 18:19:59'),
(20, 'Honey Oat Bread', NULL, '', 2.20, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:37:26'),
(21, 'Spicy Tuna Puff', NULL, '', 2.50, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:37:21'),
(22, 'Egg Tart', NULL, '', 1.80, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:37:19'),
(23, 'Fruit Tartlet', NULL, '', 3.50, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:37:14'),
(24, 'Vanilla Cream Puff', NULL, '', 2.00, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:37:10'),
(25, 'Sesame Seed Bun', NULL, '', 1.50, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 07:04:55'),
(26, 'Lemon Drizzle Cake', NULL, '', 4.00, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:37:06'),
(27, 'Caramel Pecan Pie', NULL, '', 5.50, 96, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 18:20:16'),
(28, 'Roti Jala (Lace Pancake)', NULL, '', 1.00, 100, 0, 'active', '2024-12-28 07:04:55', '2024-12-28 17:36:59');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `clerk_id` int(11) DEFAULT NULL,
  `transaction_type` enum('sale','restock','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `product_id`, `order_id`, `clerk_id`, `transaction_type`, `quantity`, `previous_stock`, `new_stock`, `transaction_date`) VALUES
(31, 11, 46, NULL, 'sale', 1, 100, 99, '2024-12-28 18:19:59'),
(32, 19, 46, NULL, 'sale', 2, 100, 98, '2024-12-28 18:19:59'),
(33, 27, 47, NULL, 'sale', 2, 100, 98, '2024-12-28 18:20:16'),
(34, 17, 47, NULL, 'sale', 2, 100, 98, '2024-12-28 18:20:16');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `order_date` datetime NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','card','online') NOT NULL,
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `delivery_address` text DEFAULT NULL,
  `order_type` enum('in-store','online') NOT NULL,
  `clerk_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `guest_id`, `order_number`, `order_date`, `total_amount`, `status`, `payment_method`, `payment_status`, `delivery_address`, `order_type`, `clerk_id`, `notes`, `created_at`) VALUES
(46, 20, 'ORD1735409999', '2024-12-29 02:19:59', 5.50, 'pending', 'cash', 'pending', 'Jalan Surabaya', 'online', NULL, NULL, '2024-12-28 18:19:59'),
(47, 21, 'ORD1735410016', '2024-12-29 02:20:16', 16.60, 'pending', 'online', 'pending', 'NO S-48/50-A BATU 1  1/2\r\nJALAN PENGKALAN CHEPA', 'online', NULL, NULL, '2024-12-28 18:20:16');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `after_order_insert` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
    -- Only track in-store sales in daily_sales
    IF NEW.order_type = 'in-store' THEN
        INSERT INTO daily_sales (
            clerk_id, 
            date, 
            total_orders,
            total_sales,
            cash_orders,
            cash_sales,
            card_orders,
            card_sales
        )
        VALUES (
            NEW.clerk_id,
            DATE(NEW.order_date),
            1,
            NEW.total_amount,
            IF(NEW.payment_method = 'cash', 1, 0),
            IF(NEW.payment_method = 'cash', NEW.total_amount, 0),
            IF(NEW.payment_method = 'card', 1, 0),
            IF(NEW.payment_method = 'card', NEW.total_amount, 0)
        )
        ON DUPLICATE KEY UPDATE
            total_orders = total_orders + 1,
            total_sales = total_sales + VALUES(total_sales),
            cash_orders = cash_orders + VALUES(cash_orders),
            cash_sales = cash_sales + VALUES(cash_sales),
            card_orders = card_orders + VALUES(card_orders),
            card_sales = card_sales + VALUES(card_sales);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(38, 46, 11, 1, 2.50, 2.50),
(39, 46, 19, 2, 1.50, 3.00),
(40, 47, 27, 2, 5.50, 11.00),
(41, 47, 17, 2, 2.80, 5.60);

--
-- Triggers `order_items`
--
DELIMITER $$
CREATE TRIGGER `after_order_item_insert` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
    DECLARE current_stock INT;
    
    -- Get current stock level
    SELECT stock_level INTO current_stock
    FROM inventory 
    WHERE id = NEW.product_id;
    
    -- Record inventory transaction first
    INSERT INTO inventory_transactions (
        product_id, 
        order_id, 
        clerk_id,
        transaction_type,
        quantity,
        previous_stock,
        new_stock
    )
    SELECT 
        NEW.product_id,
        NEW.order_id,
        o.clerk_id,
        'sale',
        NEW.quantity,
        current_stock,
        current_stock - NEW.quantity
    FROM orders o
    WHERE o.id = NEW.order_id;
    
    -- Then update inventory stock level
    UPDATE inventory 
    SET stock_level = current_stock - NEW.quantity 
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `code`, `description`, `discount_type`, `discount_value`, `start_date`, `end_date`, `status`) VALUES
(1, 'WELCOME10', 'Welcome discount 10%', 'percentage', 10.00, '2024-01-01', '2024-12-31', 'active'),
(2, 'SAVE5', 'RM5 off for orders above RM50', 'fixed', 5.00, '2024-01-01', '2024-12-31', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_sales`
--
ALTER TABLE `daily_sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_clerk_date` (`clerk_id`,`date`);

--
-- Indexes for table `employee_ids`
--
ALTER TABLE `employee_ids`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `guest`
--
ALTER TABLE `guest`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_status` (`status`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `clerk_id` (`clerk_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_clerk_date` (`clerk_id`,`order_date`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `fk_orders_guest` (`guest_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orderitems_order` (`order_id`),
  ADD KEY `fk_orderitems_product` (`product_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_sales`
--
ALTER TABLE `daily_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employee_ids`
--
ALTER TABLE `employee_ids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `guest`
--
ALTER TABLE `guest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_sales`
--
ALTER TABLE `daily_sales`
  ADD CONSTRAINT `daily_sales_ibfk_1` FOREIGN KEY (`clerk_id`) REFERENCES `guest` (`id`);

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory` (`id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_3` FOREIGN KEY (`clerk_id`) REFERENCES `guest` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_clerk` FOREIGN KEY (`clerk_id`) REFERENCES `guest` (`id`),
  ADD CONSTRAINT `fk_orders_guest` FOREIGN KEY (`guest_id`) REFERENCES `guest` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitems_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `fk_orderitems_product` FOREIGN KEY (`product_id`) REFERENCES `inventory` (`id`),
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inventory` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
