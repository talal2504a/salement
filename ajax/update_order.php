<?php
// ajax/update_order.php — Order ki booked qty update + plates recalculate
header('Content-Type: application/json');
require_once '../config/db.php';

$order_id = (int)($_POST['order_id'] ?? 0);
$new_qty  = (int)($_POST['booked_qty'] ?? 0);

if ($order_id <= 0 || $new_qty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order aur quantity zaroori hai']);
    exit;
}

// Current order + pcs_per_plate (stock_out se)
$stmt = $conn->prepare(
    "SELECT o.item_id, o.booked_qty, o.dispatched_qty, o.status,
            COALESCE(so.pcs_per_plate, 36) AS pps
     FROM orders o
     LEFT JOIN stock_out so ON so.order_id = o.id
     WHERE o.id = ?"
);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || $order['status'] === 'CANCELLED') {
    echo json_encode(['success' => false, 'message' => 'Order nahi mila ya cancelled hai']);
    exit;
}
if ($new_qty < $order['dispatched_qty']) {
    echo json_encode(['success' => false, 'message' => "Booked qty dispatched ({$order['dispatched_qty']}) se kam nahi ho sakti"]);
    exit;
}

$diff = $new_qty - $order['booked_qty'];

if ($diff > 0) {
    $stmt = $conn->prepare(
        "SELECT COALESCE((SELECT SUM(qty) FROM stock_in WHERE item_id = ?), 0) -
                COALESCE((SELECT SUM(qty) FROM stock_out WHERE item_id = ? AND order_id <> ?), 0) AS available"
    );
    $stmt->bind_param("iii", $order['item_id'], $order['item_id'], $order_id);
    $stmt->execute();
    $available = (int)$stmt->get_result()->fetch_assoc()['available'];
    $stmt->close();
    if ($diff > $available) {
        echo json_encode(['success' => false, 'message' => "Extra {$diff} pcs ke liye sirf {$available} available hain"]);
        exit;
    }
}

$pps    = ($order['pps'] > 0) ? $order['pps'] : 36;
$plates = intdiv($new_qty, $pps);

$conn->begin_transaction();
try {
    $conn->prepare("UPDATE orders SET booked_qty = ? WHERE id = ?")
        ->bind_param("ii", $new_qty, $order_id)->execute();

    if ($diff != 0) {
        $upd = $conn->prepare("UPDATE stock_out SET qty = ?, plates = ?, pcs_per_plate = ? WHERE order_id = ? AND item_id = ?");
        $upd->bind_param("iiiii", $new_qty, $plates, $pps, $order_id, $order['item_id']);
        $upd->execute();
        $upd->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Order updated: {$new_qty} booked, {$plates} plates"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
$conn->close();