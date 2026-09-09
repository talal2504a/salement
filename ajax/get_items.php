<?php
// ajax/get_items.php — Items list with available stock + pcs_per_plate (item ke saath)
header('Content-Type: application/json');
require_once '../config/db.php';

$sql = "SELECT i.id,
               i.name,
               i.category,
               'pcs' AS unit,
               COALESCE(si.total_in, 0) - COALESCE(so.total_out, 0) AS available,
               i.pcs_per_plate AS pcs_per_plate
        FROM items i
        LEFT JOIN (
            SELECT item_id, SUM(qty) AS total_in
            FROM stock_in
            GROUP BY item_id
        ) si ON si.item_id = i.id
        LEFT JOIN (
            SELECT item_id, SUM(qty) AS total_out
            FROM stock_out
            GROUP BY item_id
        ) so ON so.item_id = i.id
        ORDER BY i.name";

$result = $conn->query($sql);
if (!$result) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    $conn->close();
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $row['available'] = (int)$row['available'];
    $row['pcs_per_plate'] = (int)$row['pcs_per_plate'];
    $data[] = $row;
}
echo json_encode(['success' => true, 'data' => $data]);
$conn->close();