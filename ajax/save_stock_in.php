<?php
// ajax/save_stock_in.php — Stock IN save (stockin.php ka AJAX target)
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/activity.php';

$item_id       = intval($_POST['item_id'] ?? 0);
$fresh_qty     = intval($_POST['fresh_qty'] ?? 0);
$damaged_qty   = intval($_POST['damaged_qty'] ?? 0);
$ref_no        = trim($_POST['ref_no'] ?? '');
$entry_date    = $_POST['entry_date'] ?? date('Y-m-d');

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Item select karein']);
    exit;
}
if ($fresh_qty <= 0 && $damaged_qty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Fresh ya Damaged quantity zaroor likhein']);
    exit;
}

$conn->begin_transaction();

try {
    // Fresh entry -> stock_in table
    if ($fresh_qty > 0) {
        $stmt = $conn->prepare(
            "INSERT INTO stock_in (item_id, qty, supplier, in_date, `condition`, notes)
             VALUES (?, ?, ?, ?, 'fresh', ?)"
        );
        $notes = empty($ref_no) ? 'Stock IN' : $ref_no;
        $stmt->bind_param("iisss", $item_id, $fresh_qty, $ref_no, $entry_date, $notes);
        $stmt->execute();
        $stmt->close();
    }

    // Damaged entry -> stock_in table
    if ($damaged_qty > 0) {
        $stmt = $conn->prepare(
            "INSERT INTO stock_in (item_id, qty, supplier, in_date, `condition`, notes)
             VALUES (?, ?, ?, ?, 'damaged', ?)"
        );
        $notes = empty($ref_no) ? 'Stock IN' : $ref_no;
        $stmt->bind_param("iisss", $item_id, $damaged_qty, $ref_no, $entry_date, $notes);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    log_activity($conn, 'STOCK IN', "Item #{$item_id}: {$fresh_qty} fresh + {$damaged_qty} damaged");
    echo json_encode([
        'success' => true,
        'message' => "Stock IN saved: {$fresh_qty} Fresh, {$damaged_qty} Damaged"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>