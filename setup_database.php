<?php
// ============================================================
// DATABASE SETUP FILE
// Created: 2026-09-08
// Purpose: Database + Tables + Sample Data create karta hai
// How to run: Browser mein http://localhost/pending/setup_database.php
// WARNING: Is file ko ek baar run karne ke baad DELETE karna!
// ============================================================

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';               // Agar password set hai toh yahan likho
$db_name = 'pendingwithsarim'; // Database name

// Step 1: MySQL se connect karo (bina database select kiye)
$conn = new mysqli($db_host, $db_user, $db_pass);
if ($conn->connect_error) { die('Connection failed: ' . $conn->connect_error); }

// Step 2: Database create karo agar exist nahi karta
$sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql)) { echo "Database '$db_name' created<br>"; } else { echo "Error: " . $conn->error; exit; }

// Step 3: Database select karo
$conn->select_db($db_name);

// Step 4: Tables create karo
$tables = [];

// parties table - Party/Customer ki information
$tables[] = "CREATE TABLE IF NOT EXISTS `parties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

// items table - Products ki list
// NEW (2026-09-09): unit + reorder_level columns add kiye (stockin.php ke liye)
$tables[] = "CREATE TABLE IF NOT EXISTS `items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `unit` VARCHAR(50) DEFAULT 'pcs',
    `reorder_level` INT DEFAULT 10,
    `unit_price` DECIMAL(10,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

// orders table - Orders ki details
// CHANGED (2026-09-08): status enum mein 'CANCELLED' add kiya
// Taaake Cancel button se order cancel ho sake
$tables[] = "CREATE TABLE IF NOT EXISTS `orders` (
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

// delivery_log table - Delivery history
// CHANGED (2026-09-08): delivery_time column REMOVE kar diya (user request)
// Ab sirf delivery_date (DATE) save hoti hai
$tables[] = "CREATE TABLE IF NOT EXISTS `delivery_log` (
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

// NEW (2026-09-09): stock_ledger table — UNIFIED STOCK LEDGER (real structure)
// type='IN' → stock aaya (purchase ya cancel return)
// type='OUT' → stock gaya (sale/delivery)
// Available = SUM(IN) - SUM(OUT)
$tables[] = "CREATE TABLE IF NOT EXISTS `stock_ledger` (
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

// Tables create karo ek ek karke
foreach ($tables as $sql) {
    if ($conn->query($sql)) { 
        preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $sql, $matches);
        echo "Table '{$matches[1]}' created<br>";
    } else { 
        echo "Error: " . $conn->error . "<br>"; 
    }
}

// NEW (2026-09-08): Purani database ko patch karo
// Agar orders table pehle se hai aur uske status enum mein CANCELLED nahi,
// toh ALTER TABLE se add kar do - DATA DELETE NAHI HOTA
$check = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'");
if ($check && $col = $check->fetch_assoc()) {
    if (strpos($col['Type'], 'CANCELLED') === false) {
        if ($conn->query("ALTER TABLE orders MODIFY status ENUM('PENDING','PARTIAL','COMPLETED','CANCELLED') DEFAULT 'PENDING'")) {
            echo "Orders table patched: 'CANCELLED' status add ho gaya<br>";
        } else {
            echo "Patch error: " . $conn->error . "<br>";
        }
    }
}

// CHANGED (2026-09-08): TIME columns REMOVE karo (agar purani DB mein hain)
// delivery_log.delivery_time + stock_out.out_time + stock_in.in_time
// DATA DELETE NAHI HOTA — sirf time columns drop hote hain
$time_cols = [
    ['delivery_log', 'delivery_time'],
    ['stock_out',    'out_time'],
    ['stock_in',     'in_time'],
];
foreach ($time_cols as $tc) {
    list($tname, $cname) = $tc;
    $chk = $conn->query("SHOW COLUMNS FROM `$tname` LIKE '$cname'");
    if ($chk && $chk->num_rows > 0) {
        if ($conn->query("ALTER TABLE `$tname` DROP COLUMN `$cname`")) {
            echo "$tname patched: '$cname' column remove ho gaya<br>";
        } else {
            echo "Patch error: " . $conn->error . "<br>";
        }
    }
}

// NEW (2026-09-08): stock_in table mein condition column add karo (agar nahi hai)
// Cancel flow mein fresh/damaged split ke liye zaroori
$chk_cond = $conn->query("SHOW COLUMNS FROM stock_in LIKE 'condition'");
if ($chk_cond && $chk_cond->num_rows === 0) {
    if ($conn->query("ALTER TABLE stock_in ADD COLUMN `condition` VARCHAR(50) DEFAULT 'fresh' AFTER in_date")) {
        echo "stock_in patched: 'condition' column add ho gaya<br>";
    } else {
        echo "Patch error: " . $conn->error . "<br>";
    }
}

// Step 5: Sample Data Insert karo (sirf tabhi jab orders table empty ho)
// FIXED: Pehle sirf parties check ho raha tha, ab orders bhi check hoga
// Taaake duplicate orders na banein agar file do baar run ho
$result = $conn->query("SELECT COUNT(*) as cnt FROM orders");
$row = $result->fetch_assoc();

if ($row['cnt'] == 0) {
    // UPDATED (2026-09-09): Real sample data from user's project
    $conn->query("INSERT INTO `parties` (`name`, `phone`, `address`) VALUES
        ('Ahmed Traders', '0300-1234567', 'Lahore'),
        ('Sarim Enterprises', '0321-9876543', 'Karachi'),
        ('Hassan & Sons', '0333-5551234', 'Islamabad'),
        ('Ali Brothers', '0345-7778888', 'Faisalabad'),
        ('hamza', '0311-2223333', 'Rawalpindi'),
        ('talal', '0344-5556666', 'Multan'),
        ('XYZ', '0301-9998888', 'Peshawar')
    ");

    $conn->query("INSERT INTO `items` (`name`, `category`, `unit`, `reorder_level`, `unit_price`) VALUES
        ('Basmati Rice 5kg', 'Rice', 'bag', 10, 1500.00),
        ('Basmati Rice 20kg', 'Rice', 'bag', 5, 5500.00),
        ('IRRI Rice', 'Rice', 'bag', 10, 2000.00),
        ('Sugar 5kg', 'Sugar', 'bag', 10, 650.00),
        ('Cooking Oil 1L', 'Oil', 'bottle', 20, 450.00),
        ('Cooking Oil 5L', 'Oil', 'bottle', 10, 2100.00)
    ");

    // Orders (booked_qty + dispatched_qty + status)
    $conn->query("INSERT INTO `orders` (`party_id`, `item_id`, `booked_qty`, `dispatched_qty`, `status`, `ref_no`, `order_date`, `item_condition`) VALUES
        (1, 1, 36, 0, 'PENDING', 'ORD-001', '2026-09-01', 'Fresh'),
        (1, 2, 50, 20, 'PARTIAL', 'ORD-002', '2026-09-02', 'Fresh'),
        (2, 3, 200, 0, 'PENDING', 'ORD-003', '2026-09-03', 'Fresh'),
        (4, 6, 100, 0, 'PENDING', 'ORD-004', '2026-09-06', 'Fresh'),
        (3, 5, 150, 50, 'PARTIAL', 'ORD-005', '2026-09-04', 'Fresh'),
        (3, 6, 300, 0, 'PENDING', 'ORD-006', '2026-09-05', 'Fresh'),
        (4, 1, 75, 25, 'PARTIAL', 'ORD-007', '2026-09-07', 'Fresh')
    ");

    // Delivery logs
    $conn->query("INSERT INTO `delivery_log` (`order_id`, `qty_delivered`, `dc_no`, `vehicle_no`, `notes`, `delivery_date`) VALUES
        (2, 20, 'DC-001', 'LEA-1234', 'First batch', '2026-09-05'),
        (5, 50, 'DC-002', 'LEB-5678', 'Partial', '2026-09-06'),
        (7, 25, 'DC-003', 'LEC-9012', 'Initial', '2026-09-08')
    ");

    // NEW (2026-09-09): stock_ledger IN entries (initial warehouse stock)
    $conn->query("INSERT INTO `stock_ledger` (`item_id`, `type`, `item_condition`, `quantity`, `party_id`, `order_id`, `ref_no`, `remarks`, `entry_date`) VALUES
        (1, 'IN', 'FRESH', 500, NULL, NULL, 'GRN-001', 'Initial stock', '2026-08-20'),
        (2, 'IN', 'FRESH', 300, NULL, NULL, 'GRN-001', 'Initial stock', '2026-08-20'),
        (3, 'IN', 'FRESH', 400, NULL, NULL, 'GRN-002', 'Initial stock', '2026-08-22'),
        (5, 'IN', 'FRESH', 350, NULL, NULL, 'GRN-004', 'Initial stock', '2026-08-24'),
        (6, 'IN', 'FRESH', 600, NULL, NULL, 'GRN-005', 'Initial stock', '2026-08-25')
    ");

    // OUT entries for sold orders (ownership transferred)
    $conn->query("INSERT INTO `stock_ledger` (`item_id`, `type`, `item_condition`, `quantity`, `party_id`, `order_id`, `ref_no`, `remarks`, `entry_date`) VALUES
        (1, 'OUT', 'FRESH', 36, 1, 1, 'ORD-001', 'Sale', '2026-09-01'),
        (2, 'OUT', 'FRESH', 50, 1, 2, 'ORD-002', 'Sale', '2026-09-02'),
        (3, 'OUT', 'FRESH', 200, 2, 3, 'ORD-003', 'Sale', '2026-09-03'),
        (6, 'OUT', 'FRESH', 100, 4, 4, 'ORD-004', 'Sale', '2026-09-06'),
        (5, 'OUT', 'FRESH', 150, 3, 5, 'ORD-005', 'Sale', '2026-09-04'),
        (6, 'OUT', 'FRESH', 300, 3, 6, 'ORD-006', 'Sale', '2026-09-05'),
        (1, 'OUT', 'FRESH', 75, 4, 7, 'ORD-007', 'Sale', '2026-09-07')
    ");

    echo "Parties + Items + Orders + delivery_log + stock_ledger inserted<br>";
} else {
    echo "Data already exists<br>";
}

// NEW (2026-09-09): Stock IN check for stock_ledger (separate from orders)
$si = $conn->query("SELECT COUNT(*) as cnt FROM stock_ledger WHERE type='IN'");
$si_row = $si->fetch_assoc();
if ($si_row['cnt'] == 0) {
    $conn->query("INSERT INTO `stock_ledger` (`item_id`, `type`, `item_condition`, `quantity`, `ref_no`, `remarks`, `entry_date`) VALUES
        (1, 'IN', 'FRESH', 500, 'GRN-001', 'Warehouse stock', '2026-08-20'),
        (2, 'IN', 'FRESH', 300, 'GRN-001', 'Warehouse stock', '2026-08-20'),
        (3, 'IN', 'FRESH', 400, 'GRN-002', 'Warehouse stock', '2026-08-22'),
        (5, 'IN', 'FRESH', 350, 'GRN-004', 'Warehouse stock', '2026-08-24'),
        (6, 'IN', 'FRESH', 600, 'GRN-005', 'Warehouse stock', '2026-08-25')
    ");
    echo "7 Stock IN entries inserted<br>";
} else {
    echo "Data already exists<br>";
}

// NEW (2026-09-08): Sample Stock IN — agar stock_in table khali hai toh bhar do
// (orders check se ALAG rakha hai taaake purane DB pe bhi stock available ho)
$si = $conn->query("SELECT COUNT(*) as cnt FROM stock_in");
$si_row = $si->fetch_assoc();
if ($si_row['cnt'] == 0) {
    // Warehouse mein maal aaya hua hai — taaake Stock Out form se stock kata ja sake
    // CHANGED (2026-09-08): in_time remove + condition column add
    $conn->query("INSERT INTO `stock_in` (`item_id`, `qty`, `supplier`, `in_date`, `condition`) VALUES
        (1, 500, 'Rice Mills Ltd', '2026-08-20', 'fresh'),
        (2, 300, 'Rice Mills Ltd', '2026-08-20', 'fresh'),
        (3, 400, 'Agro Traders', '2026-08-22', 'fresh'),
        (4, 250, 'Flour House', '2026-08-23', 'fresh'),
        (5, 350, 'Sugar Corp', '2026-08-24', 'fresh'),
        (6, 600, 'Oil Refinery', '2026-08-25', 'fresh'),
        (7, 200, 'Oil Refinery', '2026-08-25', 'fresh')
    ");
    echo "7 Stock IN entries inserted<br>";
} else {
    echo "Stock IN data already exists<br>";
}

// Step 6: Done message
echo "<hr><b>Done! Database 'pendingwithsarim' ready.</b><br>";
echo "<a href='pending.php'>Open Pending Orders</a>";

$conn->close();
?>