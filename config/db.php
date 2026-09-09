<?php
// ============================================================
// DATABASE CONNECTION FILE
// Created: 2026-09-08
// Purpose: Database connection for pendingwithsarim
// ============================================================

$db_host = 'localhost';      // MySQL host
$db_user = 'root';           // MySQL username
$db_pass = '';               // MySQL password (agar set hai toh yahan likho)
$db_name = 'pendingwithsarim'; // Database name

// Connection create karo
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// UTF-8 support ke liye
$conn->set_charset('utf8mb4');

// ============================================================
// HELPER FUNCTIONS (2026-09-09)
// Current model: stock_in / stock_out (separate tables)
// Available stock = SUM(stock_in.qty) - SUM(stock_out.qty)
// ============================================================

// Get available stock for a specific item (total, all conditions combined)
function get_available_stock($conn, $item_id) {
    // Total IN
    $stmt = $conn->prepare("SELECT COALESCE(SUM(qty), 0) AS total FROM stock_in WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $total_in = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Total OUT
    $stmt = $conn->prepare("SELECT COALESCE(SUM(qty), 0) AS total FROM stock_out WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $total_out = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    return $total_in - $total_out;
}

// Stock IN entry add karo (Fresh/Damaged dono ke liye)
function stock_in_add($conn, $item_id, $qty, $condition, $ref_no, $entry_date) {
    $stmt = $conn->prepare(
        "INSERT INTO stock_in (item_id, qty, in_date, `condition`, notes) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iisss", $item_id, $qty, $entry_date, $condition, $ref_no);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

// Stock OUT entry add karo (order se related)
function stock_out_add($conn, $item_id, $qty, $order_id, $customer_name, $delivery_number, $dc_no, $vehicle_no, $notes, $out_date) {
    $stmt = $conn->prepare(
        "INSERT INTO stock_out (item_id, order_id, qty, customer_name, delivery_number, dc_no, vehicle_no, notes, out_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iiissssss", $item_id, $order_id, $qty, $customer_name, $delivery_number, $dc_no, $vehicle_no, $notes, $out_date);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

// Stock IN minus karo (update order pe zyada qty restore ke liye)
function stock_in_minus($conn, $item_id, $qty) {
    // stock_in mein negative qty wali entry daal do (ya phir latest entry update karo)
    // Simple approach: ek negative entry daal do
    $stmt = $conn->prepare(
        "INSERT INTO stock_in (item_id, qty, in_date, `condition`, notes) VALUES (?, ?, CURDATE(), 'fresh', 'Update adjustment (minus)')"
    );
    $stmt->bind_param("ii", $item_id, $qty); // qty negative aayega
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}
?>