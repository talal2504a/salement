<?php
// ajax/update_order.php — Full order update (stockout jaisi details: party, item, condition, ref, date)
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/activity.php';

$order_id       = (int)($_POST['order_id'] ?? 0);
$party_id       = (int)($_POST['party_id'] ?? 0);
$item_id        = (int)($_POST['item_id'] ?? 0);
$item_condition = strtoupper(trim($_POST['item_condition'] ?? 'FRESH'));
$new_qty        = (int)($_POST['booked_qty'] ?? 0);
$plates         = (int)($_POST['plates'] ?? 0);
$pcs_per_plate  = (int)($_POST['pcs_per_plate'] ?? 36);
$ref_no         = trim($_POST['ref_no'] ?? '');
$order_date     = $_POST['order_date'] ?? date('Y-m-d');

if ($order_id <= 0 || $new_qty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order aur quantity zaroori hai']);
    exit;
}

$stmt = $conn->prepare("SELECT item_id, booked_qty, dispatched_qty, status FROM orders WHERE id = ?");
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
if ($item_id != $order['item_id'] && $order['dispatched_qty'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Item change tabhi ho sakta hai jab abhi tak kuch dispatched na hua ho']);
    exit;
}

$diff = $new_qty - $order['booked_qty'];

if ($diff > 0) {
    $stmt = $conn->prepare(
        "SELECT COALESCE((SELECT SUM(qty) FROM stock_in WHERE item_id = ?), 0) -
                COALESCE((SELECT SUM(qty) FROM stock_out WHERE item_id = ? AND order_id <> ?), 0) AS available"
    );
    $stmt->bind_param("iii", $item_id, $item_id, $order_id);
    $stmt->execute();
    $available = (int)$stmt->get_result()->fetch_assoc()['available'];
    $stmt->close();
    if ($diff > $available) {
        echo json_encode(['success' => false, 'message' => "Extra {$diff} pcs ke liye sirf {$available} available hain"]);
        exit;
    }
}

$pps    = ($pcs_per_plate > 0) ? $pcs_per_plate : 36;
$plates = intdiv($new_qty, $pps);

$pname = '';
$pn = $conn->prepare("SELECT name FROM parties WHERE id = ?");
$pn->bind_param("i", $party_id);
$pn->execute();
$pr = $pn->get_result()->fetch_assoc();
$pname = $pr['name'] ?? '';
$pn->close();

$conn->begin_transaction();
try {
    $upd = $conn->prepare("UPDATE orders SET party_id = ?, item_id = ?, item_condition = ?, booked_qty = ?, ref_no = ?, order_date = ? WHERE id = ?");
    $upd->bind_param("iisisss", $party_id, $item_id, $item_condition, $new_qty, $ref_no, $order_date, $order_id);
    $upd->execute();
    $upd->close();

    $upd2 = $conn->prepare("UPDATE stock_out SET item_id = ?, qty = ?, plates = ?, pcs_per_plate = ?, item_condition = ?, customer_name = ?, notes = ?, out_date = ? WHERE order_id = ?");
    $upd2->bind_param("iiiiisssi", $item_id, $new_qty, $plates, $pps, $item_condition, $pname, $ref_no, $order_date, $order_id);
    $upd2->execute();
    $upd2->close();

    $conn->commit();
    log_activity($conn, 'ORDER UPDATE', "Order #{$order_id}: {$new_qty} pcs to {$pname}");
    echo json_encode(['success' => true, 'message' => "Order updated: {$new_qty} pcs, {$plates} plates"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
$conn->close();