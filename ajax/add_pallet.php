<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$item_id = intval($_POST['item_id'] ?? 0);
$pallet_name = trim($_POST['pallet_name'] ?? '');
$qty = intval($_POST['qty'] ?? 1);

if ($item_id <= 0 || empty($pallet_name)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$notes = 'Pallet:' . $pallet_name;
$stmt = $conn->prepare(
    "INSERT INTO stock_in (item_id, qty, supplier, in_date, `condition`, notes) 
     VALUES (?, ?, 'Pallet', NOW(), 'fresh', ?)"
);
$stmt->bind_param("iis", $item_id, $qty, $notes);
if ($stmt->execute()) {
    $stmt2 = $conn->prepare("UPDATE items SET pcs_per_plate = ? WHERE id = ?");
    $stmt2->bind_param("ii", $qty, $item_id);
    $stmt2->execute();
    $stmt2->close();
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();