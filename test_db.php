<?php
header('Content-Type: application/json');
require_once 'config/db.php';

$debug = [];

// Show all tables
$result = $conn->query("SHOW TABLES");
$debug['tables'] = [];
while ($row = $result->fetch_array()) {
    $debug['tables'][] = $row[0];
}

// Check items count
$result = $conn->query("SELECT COUNT(*) as cnt FROM items");
$debug['items_count'] = $result->fetch_assoc()['cnt'];

// Check parties count
$result = $conn->query("SELECT COUNT(*) as cnt FROM parties");
$debug['parties_count'] = $result->fetch_assoc()['cnt'];

// Check orders count (if exists)
$result = $conn->query("SHOW TABLES LIKE 'orders'");
$debug['orders_table_exists'] = $result->num_rows > 0;
if ($debug['orders_table_exists']) {
    $result = $conn->query("SELECT COUNT(*) as cnt FROM orders");
    $debug['orders_count'] = $result->fetch_assoc()['cnt'];
}

// Check delivery_log count (if exists)
$result = $conn->query("SHOW TABLES LIKE 'delivery_log'");
$debug['delivery_log_table_exists'] = $result->num_rows > 0;

// Check stock_ledger count (if exists)
$result = $conn->query("SHOW TABLES LIKE 'stock_ledger'");
$debug['stock_ledger_table_exists'] = $result->num_rows > 0;

echo json_encode($debug, JSON_PRETTY_PRINT);
$conn->close();
?>