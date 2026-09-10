<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/activity.php';

$item_id   = intval($_POST['item_id'] ?? 0);
$plate_name = trim($_POST['plate_name'] ?? '');
$count      = intval($_POST['count'] ?? 0);
$qty        = intval($_POST['qty'] ?? 0);

if ($item_id <= 0 || $count <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$item_name = '';
$stmt = $conn->prepare("SELECT name FROM items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if ($row) { $item_name = $row['name']; }
$stmt->close();

$user = $_SESSION['user_name'] ?? 'System';
$details = "{$count} pallets ({$plate_name} 1-{$count}), Qty: {$qty} each, Item: {$item_name} (#{$item_id})";

$stmt = $conn->prepare("INSERT INTO activity_log (user_name, action, details) VALUES (?, 'PALLET ADD', ?)");
$stmt->bind_param("ss", $user, $details);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => true]);
?>