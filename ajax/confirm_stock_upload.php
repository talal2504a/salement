<?php
// ajax/confirm_stock_upload.php — Confirm upload → add items + stock
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/activity.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$rows = json_decode($_POST['rows'] ?? '[]', true);
if (empty($rows)) {
    echo json_encode(['success' => false, 'message' => 'Rows khaali hain']);
    exit;
}

$conn->begin_transaction();
$newItems  = 0;
$stockRows = 0;

try {
    foreach ($rows as $r) {
        $itemName = trim($r['item'] ?? '');
        $fresh    = intval($r['fresh'] ?? 0);
        $damage   = intval($r['damage'] ?? 0);

        if ($itemName === '') continue;

        // Step 1: Item dhundo
        $stmt = $conn->prepare("SELECT id FROM items WHERE name = ?");
        $stmt->bind_param("s", $itemName);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($item) {
            $itemId = $item['id'];
        } else {
            // Step 2: Naya item
            $stmt = $conn->prepare("INSERT INTO items (name, category, unit_price, pcs_per_plate) VALUES (?, '', 0, 10)");
            $stmt->bind_param("s", $itemName);
            $stmt->execute();
            $itemId = $conn->insert_id;
            $stmt->close();
            $newItems++;
        }

        // Step 3: Stock insert — fresh
        if ($fresh > 0) {
            $note = "Upload: " . $itemName;
            $stmt = $conn->prepare(
                "INSERT INTO stock_in (item_id, qty, supplier, in_date, `condition`, notes)
                 VALUES (?, ?, 'FILE UPLOAD', CURDATE(), 'fresh', ?)"
            );
            $stmt->bind_param("iis", $itemId, $fresh, $note);
            $stmt->execute();
            $stmt->close();
            $stockRows++;
        }

        // Step 4: Stock insert — damaged
        if ($damage > 0) {
            $note = "Upload: " . $itemName;
            $stmt = $conn->prepare(
                "INSERT INTO stock_in (item_id, qty, supplier, in_date, `condition`, notes)
                 VALUES (?, ?, 'FILE UPLOAD', CURDATE(), 'damaged', ?)"
            );
            $stmt->bind_param("iis", $itemId, $damage, $note);
            $stmt->execute();
            $stmt->close();
            $stockRows++;
        }
    }

    $conn->commit();
    log_activity($conn, 'STOCK IN', "File Upload: {$stockRows} stock entries, {$newItems} new items created");

    echo json_encode([
        'success' => true,
        'message' => "✅ {$stockRows} stock add, {$newItems} naye items banaye",
        'items'   => $newItems,
        'stock'   => $stockRows
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>