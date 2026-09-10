<?php
// ajax/add_item.php — Add new item
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/activity.php';

$name = trim($_POST['name'] ?? '');
$unit = trim($_POST['unit'] ?? 'pcs');
$reorder = (int)($_POST['reorder_level'] ?? 0);
$category = trim($_POST['category'] ?? '');

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Item name likhein']);
    exit;
}

// Check duplicate
$stmt = $conn->prepare("SELECT id FROM items WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Item already exists']);
    $stmt->close();
    exit;
}
$stmt->close();

$stmt = $conn->prepare("INSERT INTO items (name, category, unit_price) VALUES (?, ?, 0)");
$stmt->bind_param("ss", $name, $category);
if ($stmt->execute()) {
    log_activity($conn, 'ITEM ADD', "{$name} ({$category})");
    echo json_encode(['success' => true, 'message' => 'Item add ho gaya', 'item_id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
