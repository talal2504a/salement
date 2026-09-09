<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order ID chahiye']);
    exit;
}

$sql = "SELECT qty_delivered, dc_no, vehicle_no, notes, delivery_date
        FROM delivery_log
        WHERE order_id = " . $order_id . "
        ORDER BY id";

$data = [];
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) $data[] = $row;
}
echo json_encode(['success' => true, 'data' => $data]);
$conn->close();