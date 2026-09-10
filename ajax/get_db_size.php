<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$db = $conn->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];

$stmt = $conn->prepare(
    "SELECT SUM(data_length + index_length + data_free) AS bytes
     FROM information_schema.TABLES WHERE table_schema = ?"
);
$stmt->bind_param("s", $db);
$stmt->execute();
$bytes = (float)$stmt->get_result()->fetch_assoc()['bytes'];
$stmt->close();

$mb = round($bytes / (1024*1024), 2);
$gb = round($bytes / (1024*1024*1024), 2);

$size_txt = $gb >= 1 ? $gb . ' GB' : $mb . ' MB';

echo json_encode(['success' => true, 'size_txt' => $size_txt, 'mb' => $mb]);
$conn->close();