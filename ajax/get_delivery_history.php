<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$sql = "SELECT dl.delivery_date, dl.qty_delivered, dl.dc_no, dl.vehicle_no, dl.notes,
               p.name AS party_name, i.name AS item_name, o.item_condition, o.status
        FROM delivery_log dl
        JOIN orders o ON o.id = dl.order_id
        JOIN parties p ON p.id = o.party_id
        JOIN items i ON i.id = o.item_id
        ORDER BY dl.delivery_date DESC";

$data = [];
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) $data[] = $row;
}
echo json_encode(['success' => true, 'data' => $data]);
$conn->close();