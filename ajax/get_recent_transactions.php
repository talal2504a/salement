<?php
// ajax/get_recent_transactions.php — Dashboard recent transactions
header('Content-Type: application/json');
require_once '../config/db.php';

$sql = "SELECT 'IN' AS type, in_date AS entry_date, `condition` AS item_condition, qty, supplier AS ref_no,
        i.name AS item_name, NULL AS party_name,
        0 AS plates, 0 AS pcs_per_plate,
        (SELECT COUNT(*) FROM stock_in p
         WHERE p.item_id = si.item_id AND p.in_date = si.in_date
           AND p.notes LIKE 'Pallet:%' AND p.supplier = 'Pallet') AS entry_plates
        FROM stock_in si
        JOIN items i ON i.id = si.item_id
        WHERE si.notes NOT LIKE 'Pallet:%'
        UNION ALL
        SELECT 'OUT' AS type, out_date AS entry_date, NULL AS item_condition, qty, dc_no AS ref_no,
        i.name AS item_name, customer_name AS party_name,
            so.plates AS plates, so.pcs_per_plate AS pcs_per_plate,
        0 AS entry_plates
        FROM stock_out so
        JOIN items i ON i.id = so.item_id
        ORDER BY entry_date DESC
        LIMIT 20";

$result = $conn->query($sql);
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
echo json_encode(['success' => true, 'data' => $data]);
$conn->close();