<?php
// ajax/get_parties.php — Parties list
header('Content-Type: application/json');
require_once '../config/db.php';

$sql = "SELECT id, name, phone, address, created_at
        FROM parties ORDER BY name ASC";
$result = $conn->query($sql);

$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
}

echo json_encode(['success' => true, 'data' => $data]);
$conn->close();