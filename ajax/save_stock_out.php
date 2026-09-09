<?php
// ajax/save_stock_out.php — Save stock out + plate count
header('Content-Type: application/json');
require_once '../config/db.php';

$item_id    = intval($_POST['item_id'] ?? 0);
$party_id   = intval($_POST['party_id'] ?? 0);
$condition  = $_POST['item_condition'] ?? '';
$qty        = intval($_POST['quantity'] ?? 0);
$ref_no     = trim($_POST['ref_no'] ?? '');
$order_date = $_POST['entry_date'] ?? date('Y-m-d');
$plates     = intval($_POST['plates'] ?? 0);
$pcs_per_plate = intval($_POST['pcs_per_plate'] ?? 0);

if ($item_id <= 0 || $party_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Item aur Party dono select karein']);
    exit;
}
if (!in_array($condition, ['FRESH', 'DAMAGED'])) {
    echo json_encode(['success' => false, 'message' => 'Condition (Fresh/Damaged) select karein']);
    exit;
}
if ($qty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity 0 se zyada honi chahiye']);
    exit;
}

// Available stock = stock_in total - stock_out total (per item)
$stmt = $conn->prepare(
    "SELECT COALESCE((SELECT SUM(qty) FROM stock_in WHERE item_id = ?), 0) - 
            COALESCE((SELECT SUM(qty) FROM stock_out WHERE item_id = ?), 0) AS available"
);
$stmt->bind_param("ii", $item_id, $item_id);
$stmt->execute();
$available = (int)$stmt->get_result()->fetch_assoc()['available'];
$stmt->close();

if ($qty > $available) {
    echo json_encode(['success' => false, 'message' => "Sirf {$available} pcs available hain, {$qty} nahi de sakte"]);
    exit;
}

$notes = "Plates: {$plates} ({$pcs_per_plate} pcs/plate)";

// Transaction: order + stock_out row
$conn->begin_transaction();
try {
    // 1. Party name fetch karo
    $pn = $conn->prepare("SELECT name FROM parties WHERE id = ?");
    $pn->bind_param("i", $party_id);
    $pn->execute();
    $party_name = $pn->get_result()->fetch_assoc()['name'] ?? '';
    $pn->close();

    // 2. Orders me PENDING record (pending deliver system saath chale)
    $stmt1 = $conn->prepare(
        "INSERT INTO orders (party_id, item_id, item_condition, booked_qty, dispatched_qty, status, ref_no, order_date)
         VALUES (?, ?, ?, ?, 0, 'PENDING', ?, ?)"
    );
    $stmt1->bind_param("iisiss", $party_id, $item_id, $condition, $qty, $ref_no, $order_date);
    $stmt1->execute();
    $order_id = $stmt1->insert_id;
    $stmt1->close();

    // 3. stock_out table me save (plates + pcs_per_plate columns ke saath)
        $stmt2 = $conn->prepare(
        "INSERT INTO stock_out (item_id, order_id, qty, plates, pcs_per_plate, item_condition, customer_name, notes, out_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt2->bind_param("iiiiissss", $item_id, $order_id, $qty, $plates, $pcs_per_plate, $condition, $party_name, $notes, $order_date);
    $stmt2->execute();
    $stmt2->close();

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => "Sale saved. {$qty} pcs, {$plates} plates.",
        'order_id' => $order_id,
        'plates' => $plates
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
$conn->close();