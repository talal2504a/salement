<?php
// ajax/deliver_pending.php — Deliver pending order (stockout.php)
header('Content-Type: application/json');
require_once '../config/db.php';

$order_id = (int)($_POST['order_id'] ?? 0);
$qty_delivered = (int)($_POST['qty_delivered'] ?? 0);
$delivery_date = $_POST['delivery_date'] ?? date('Y-m-d');
$dc_no = trim($_POST['dc_no'] ?? '');
$vehicle_no = trim($_POST['vehicle_no'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order ID zaroori hai']);
    exit;
}
if ($qty_delivered <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity 0 se zyada honi chahiye']);
    exit;
}

$stmt = $conn->prepare("SELECT booked_qty, dispatched_qty, status FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || $order['status'] === 'CANCELLED') {
    echo json_encode(['success' => false, 'message' => 'Order nahi mila ya cancelled']);
    exit;
}

$pending_before = $order['booked_qty'] - $order['dispatched_qty'];
if ($qty_delivered > $pending_before) {
    echo json_encode(['success' => false, 'message' => "Sirf {$pending_before} pcs pending hain"]);
    exit;
}

$new_dispatched = $order['dispatched_qty'] + $qty_delivered;
$new_status = ($new_dispatched >= $order['booked_qty']) ? 'COMPLETED' : 'PARTIAL';

$conn->begin_transaction();
try {
    $upd = $conn->prepare("UPDATE orders SET dispatched_qty = ?, status = ? WHERE id = ?");
    $upd->bind_param("isi", $new_dispatched, $new_status, $order_id);
    $upd->execute();
    $upd->close();

    $ins = $conn->prepare("INSERT INTO delivery_log (order_id, qty_delivered, dc_no, vehicle_no, notes, delivery_date)
                           VALUES (?, ?, ?, ?, ?, ?)");
    $ins->bind_param("iissss", $order_id, $qty_delivered, $dc_no, $vehicle_no, $notes, $delivery_date);
    $ins->execute();
    $ins->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Delivery saved. {$qty_delivered} pcs dispatched."]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
$conn->close();