<?php
// ============================================================
// RESET DATABASE FILE
// Created: 2026-09-08
// Purpose: Duplicate data delete karke fresh data dalta hai
// How to run: http://localhost/pending/reset_database.php
// WARNING: Yeh sab data DELETE karke naya sample data daalega!
// ============================================================

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pendingwithsarim';

// Step 1: Connect karo
$conn = new mysqli($db_host, $db_user, $db_pass);
if ($conn->connect_error) { die('Connection failed: ' . $conn->connect_error); }

// Step 2: Database select karo
$conn->select_db($db_name);

echo "<h2>Resetting Database...</h2>";

// Step 3: Purani tables DELETE karo (fresh start ke liye)
// UPDATED (2026-09-09): stock_in/stock_out remove, stock_ledger add
$dropTables = ['stock_ledger', 'delivery_log', 'orders', 'items', 'parties'];
foreach ($dropTables as $table) {
    $conn->query("DROP TABLE IF EXISTS `$table`");
    echo "Dropped table: $table<br>";
}

// Step 4: Foreign key checks off karo (temporary)
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Step 5: Tables dobara create karo
$tables = [];

$tables[] = "CREATE TABLE `parties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

// NEW (2026-09-09): unit + reorder_level columns
$tables[] = "CREATE TABLE `items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `unit` VARCHAR(50) DEFAULT 'pcs',
    `reorder_level` INT DEFAULT 10,
    `unit_price` DECIMAL(10,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

$tables[] = "CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `party_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `booked_qty` INT NOT NULL DEFAULT 0,
    `dispatched_qty` INT NOT NULL DEFAULT 0,
    `status` ENUM('PENDING','PARTIAL','COMPLETED','CANCELLED') DEFAULT 'PENDING',
    `ref_no` VARCHAR(100) DEFAULT NULL,
    `order_date` DATE DEFAULT NULL,
    `item_condition` VARCHAR(50) DEFAULT 'Fresh',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`party_id`) REFERENCES `parties`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB";

// CHANGED (2026-09-08): delivery_time column REMOVE kar diya
$tables[] = "CREATE TABLE `delivery_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `qty_delivered` INT NOT NULL DEFAULT 0,
    `dc_no` VARCHAR(100) DEFAULT NULL,
    `vehicle_no` VARCHAR(50) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `delivery_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB";

// NEW (2026-09-09): stock_ledger — UNIFIED STOCK LEDGER (real structure)
// type='IN' → stock aaya (purchase ya cancel return)
// type='OUT' → stock gaya (sale/delivery)
// Available = SUM(IN) - SUM(OUT)
$tables[] = "CREATE TABLE `stock_ledger` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `type` ENUM('IN','OUT') NOT NULL,
    `item_condition` ENUM('FRESH','DAMAGED') NOT NULL,
    `quantity` INT NOT NULL DEFAULT 0,
    `party_id` INT DEFAULT NULL,
    `order_id` INT DEFAULT NULL,
    `ref_no` VARCHAR(100) DEFAULT NULL,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `entry_date` DATE NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`party_id`) REFERENCES `parties`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB";

foreach ($tables as $sql) {
    if ($conn->query($sql)) {
        preg_match('/CREATE TABLE `(\w+)`/', $sql, $matches);
        echo "Created table: {$matches[1]}<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

// Step 6: Foreign key checks wapas on karo
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Step 7: Fresh sample data insert karo
echo "<h2>Inserting Fresh Data...</h2>";

// Sample data — UPDATED (2026-09-09): stock_ledger model
$conn->query("INSERT INTO `parties` (`name`, `phone`, `address`) VALUES
    ('Ahmed Traders', '0300-1234567', 'Lahore'),
    ('Sarim Enterprises', '0321-9876543', 'Karachi'),
    ('Hassan & Sons', '0333-5551234', 'Islamabad'),
    ('Ali Brothers', '0345-7778888', 'Faisalabad'),
    ('hamza', '0311-2223333', 'Rawalpindi'),
    ('talal', '0344-5556666', 'Multan'),
    ('XYZ', '0301-9998888', 'Peshawar')
");
echo "7 Parties inserted<br>";

$conn->query("INSERT INTO `items` (`name`, `category`, `unit`, `reorder_level`, `unit_price`) VALUES
    ('Basmati Rice 5kg', 'Rice', 'bag', 10, 1500.00),
    ('Basmati Rice 20kg', 'Rice', 'bag', 5, 5500.00),
    ('IRRI Rice', 'Rice', 'bag', 10, 2000.00),
    ('Sugar 5kg', 'Sugar', 'bag', 10, 650.00),
    ('Cooking Oil 1L', 'Oil', 'bottle', 20, 450.00),
    ('Cooking Oil 5L', 'Oil', 'bottle', 10, 2100.00)
");
echo "6 Items inserted<br>";

$conn->query("INSERT INTO `orders` (`party_id`, `item_id`, `booked_qty`, `dispatched_qty`, `status`, `ref_no`, `order_date`, `item_condition`) VALUES
    (1, 1, 36, 0, 'PENDING', 'ORD-001', '2026-09-01', 'Fresh'),
    (1, 2, 50, 20, 'PARTIAL', 'ORD-002', '2026-09-02', 'Fresh'),
    (2, 3, 200, 0, 'PENDING', 'ORD-003', '2026-09-03', 'Fresh'),
    (4, 6, 100, 0, 'PENDING', 'ORD-004', '2026-09-06', 'Fresh'),
    (3, 5, 150, 50, 'PARTIAL', 'ORD-005', '2026-09-04', 'Fresh'),
    (3, 6, 300, 0, 'PENDING', 'ORD-006', '2026-09-05', 'Fresh'),
    (4, 1, 75, 25, 'PARTIAL', 'ORD-007', '2026-09-07', 'Fresh')
");
echo "7 Orders inserted<br>";

$conn->query("INSERT INTO `delivery_log` (`order_id`, `qty_delivered`, `dc_no`, `vehicle_no`, `notes`, `delivery_date`) VALUES
    (2, 20, 'DC-001', 'LEA-1234', 'First batch', '2026-09-05'),
    (5, 50, 'DC-002', 'LEB-5678', 'Partial', '2026-09-06'),
    (7, 25, 'DC-003', 'LEC-9012', 'Initial', '2026-09-08')
");
echo "3 Delivery Logs inserted<br>";

// NEW (2026-09-09): stock_ledger IN entries (initial warehouse stock)
$conn->query("INSERT INTO `stock_ledger` (`item_id`, `type`, `item_condition`, `quantity`, `party_id`, `order_id`, `ref_no`, `remarks`, `entry_date`) VALUES
    (1, 'IN', 'FRESH', 500, NULL, NULL, 'GRN-001', 'Initial stock', '2026-08-20'),
    (2, 'IN', 'FRESH', 300, NULL, NULL, 'GRN-001', 'Initial stock', '2026-08-20'),
    (3, 'IN', 'FRESH', 400, NULL, NULL, 'GRN-002', 'Initial stock', '2026-08-22'),
    (5, 'IN', 'FRESH', 350, NULL, NULL, 'GRN-004', 'Initial stock', '2026-08-24'),
    (6, 'IN', 'FRESH', 600, NULL, NULL, 'GRN-005', 'Initial stock', '2026-08-25')
");

// OUT entries for existing orders (stock sold = ownership transferred)
$conn->query("INSERT INTO `stock_ledger` (`item_id`, `type`, `item_condition`, `quantity`, `party_id`, `order_id`, `ref_no`, `remarks`, `entry_date`) VALUES
    (1, 'OUT', 'FRESH', 36, 1, 1, 'ORD-001', 'Sale', '2026-09-01'),
    (2, 'OUT', 'FRESH', 50, 1, 2, 'ORD-002', 'Sale', '2026-09-02'),
    (3, 'OUT', 'FRESH', 200, 2, 3, 'ORD-003', 'Sale', '2026-09-03'),
    (6, 'OUT', 'FRESH', 100, 4, 4, 'ORD-004', 'Sale', '2026-09-06'),
    (5, 'OUT', 'FRESH', 150, 3, 5, 'ORD-005', 'Sale', '2026-09-04'),
    (6, 'OUT', 'FRESH', 300, 3, 6, 'ORD-006', 'Sale', '2026-09-05'),
    (1, 'OUT', 'FRESH', 75, 4, 7, 'ORD-007', 'Sale', '2026-09-07')
");
echo "12 Stock ledger entries inserted (5 IN + 7 OUT)<br>";

echo "<hr><b style='color:green'>✅ Database reset complete! Ab duplicate data nahi hoga.</b><br>";
echo "<a href='pending.php'>Open Pending Orders</a>";

$conn->close();
?>