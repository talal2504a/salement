<?php
// ajax/add_party.php — Add new party
header('Content-Type: application/json');
require_once '../config/db.php';

$name     = trim($_POST['name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$address  = trim($_POST['address'] ?? '');

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Party name likhein']);
    exit;
}

// Check duplicate
$stmt = $conn->prepare("SELECT id FROM parties WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Party already exists']);
    $stmt->close();
    exit;
}
$stmt->close();

$stmt = $conn->prepare(
    "INSERT INTO parties (name, phone, address, created_at) VALUES (?, ?, ?, NOW())"
);
$stmt->bind_param("sss", $name, $phone, $address);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Party add ho gaya', 'party_id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}
$stmt->close();
$conn->close();