<?php
// ajax/get_pending_orders.php — Pending orders list + plates, party ke hisaab se grouped
header('Content-Type: application/json');
require_once '../config/db.php';

$party_id = isset($_GET['party_id']) ? (int)$_GET['party_id'] : null;

$sql = "SELECT o.id AS order_id, o.item_id AS item_id, o.booked_qty, o.dispatched_qty,
            (o.booked_qty - o.dispatched_qty) AS pending_qty,
            o.status, o.ref_no, o.order_date, o.item_condition,
            i.name AS item_name, i.category,
            p.name AS party_name, p.id AS party_id,
            COALESCE(so.plates, 0) AS plates,
            COALESCE(so.pcs_per_plate, 36) AS pcs_per_plate
        FROM orders o
        JOIN items i ON i.id = o.item_id
        JOIN parties p ON p.id = o.party_id
        LEFT JOIN stock_out so ON so.order_id = o.id
        WHERE o.status NOT IN ('COMPLETED', 'CANCELLED')";
if ($party_id) {
    $sql .= " AND o.party_id = ?";
}
$sql .= " ORDER BY p.name ASC, o.order_date ASC";

$stmt = $conn->prepare($sql);
if ($party_id) {
    $stmt->bind_param("i", $party_id);
}
$stmt->execute();
$result = $stmt->get_result();

$grouped = [];
while ($row = $result->fetch_assoc()) {
    $pid = $row['party_id'];
    if (!isset($grouped[$pid])) {
        $grouped[$pid] = [
            'party_id'   => $pid,
            'party_name' => $row['party_name'],
            'orders'     => []
        ];
    }
    $row['plates']        = (int)$row['plates'];
    $row['pcs_per_plate'] = (int)$row['pcs_per_plate'];
    $grouped[$pid]['orders'][] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'data' => array_values($grouped)]);
$conn->close();