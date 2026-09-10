<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$action = trim($_GET['action'] ?? '');

$sql = "SELECT user_name, action, details, created_at FROM activity_log";
if ($action !== '') {
    $sql .= " WHERE action = ? ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $action);
} else {
    $stmt = $conn->prepare($sql . " ORDER BY id DESC");
}
$stmt->execute();
$data = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'data' => $data]);
?>