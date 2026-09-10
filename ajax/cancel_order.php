<?php
// ajax/cancel_order.php — Order cancel karta hai
// Stock wapis: fresh/damaged stock_in me insert + order CANCELLED
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/activity.php';

$order_id    = (int)($_POST['order_id'] ?? 0);
$fresh_qty   = (int)($_POST['fresh_qty'] ?? 0);
$damaged_qty = (int)($_POST['damaged_qty'] ?? 0);

if ($order_id <= 0 || ($fresh_qty + $damaged_qty) <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order aur qty (fresh+damaged) zaroori hai']);
    exit;
}

$stmt = $conn->prepare("SELECT item_id, status FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || $order['status'] === 'CANCELLED') {
    echo json_encode(['success' => false, 'message' => 'Order nahi mila ya pehle se cancelled hai']);
    exit;
}

$notes = 'Cancel return Order #' . $order_id;

$conn->begin_transaction();
try {
    if ($fresh_qty > 0) {
        $ins = $conn->prepare("INSERT INTO stock_in (item_id, qty, supplier, in_date, `condition`, notes)
                               VALUES (?, ?, 'CANCEL', NOW(), 'fresh', ?)");
        $ins->bind_param("iis", $order['item_id'], $fresh_qty, $notes);
        $ins->execute();
        $ins->close();
    }
    if ($damaged_qty > 0) {
        $ins = $conn->prepare("INSERT INTO stock_in (item_id, qty, supplier, in_date, `condition`, notes)
                               VALUES (?, ?, 'CANCEL', NOW(), 'damaged', ?)");
        $ins->bind_param("iis", $order['item_id'], $damaged_qty, $notes);
        $ins->execute();
        $ins->close();
    }
    $upd = $conn->prepare("UPDATE orders SET status = 'CANCELLED' WHERE id = ?");
    $upd->bind_param("i", $order_id);
    $upd->execute();
    $upd->close();

    $conn->commit();
    log_activity($conn, 'ORDER CANCEL', "Order #{$order_id} cancelled, {$fresh_qty} fresh + {$damaged_qty} damaged wapis");
    echo json_encode(['success' => true, 'message' => "Order cancelled. {$fresh_qty} fresh + {$damaged_qty} damaged stock wapis."]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
$conn->close();