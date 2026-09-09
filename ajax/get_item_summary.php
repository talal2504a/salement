<?php
// ajax/get_item_summary.php — Dashboard item summary card
// FIXED 2026-09-09: stock_in/stock_out tables (NOT stock_ledger which doesn't exist)
// + Plate/Pallet count per item added
header('Content-Type: application/json');
require_once '../config/db.php';

// ---- Per-item summary from stock_in / stock_out ----
$sql = "SELECT i.id, i.name, i.category,
        COALESCE(si.fresh_in, 0)  - COALESCE(so_out.fresh_out, 0)     AS fresh_qty,
        COALESCE(si.damaged_in,0) - COALESCE(so_out.damaged_out, 0)   AS damaged_qty,
        (COALESCE(si.total_in,0) - COALESCE(so_out.total_out,0))      AS total_qty,
        COALESCE(si.total_in,0)                                       AS physical_in,
        COALESCE(so_out.total_out,0)                                  AS physical_out
        FROM items i
        LEFT JOIN (
            SELECT item_id,
                   SUM(CASE WHEN `condition`='FRESH'   THEN qty ELSE 0 END) AS fresh_in,
                   SUM(CASE WHEN `condition`='DAMAGED' THEN qty ELSE 0 END) AS damaged_in,
                   SUM(qty) AS total_in
            FROM stock_in
            GROUP BY item_id
        ) si ON si.item_id = i.id
        LEFT JOIN (
            SELECT item_id,
                   SUM(qty) AS total_out,
                   SUM(CASE WHEN item_condition = 'FRESH' THEN qty ELSE 0 END) AS fresh_out,
                   SUM(CASE WHEN item_condition = 'DAMAGED' THEN qty ELSE 0 END) AS damaged_out
            FROM stock_out
            GROUP BY item_id
        ) so_out ON so_out.item_id = i.id";

$result = $conn->query($sql);
if (!$result) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    $conn->close();
    exit;
}

$items = [];
$totals = [
    'total_items'   => 0,
    'total_qty'     => 0,
    'total_physical'=> 0,
    'total_fresh'   => 0,
    'total_damaged' => 0
];

while ($row = $result->fetch_assoc()) {
    $fresh_qty      = (int)$row['fresh_qty'];
    $damaged_qty    = (int)$row['damaged_qty'];
    $total_qty      = (int)$row['total_qty'];
    $physical_total = (int)$row['physical_in'] - (int)$row['physical_out'];

    // Plate count = available plates (pallets In minus plates Out)
    $stmt = $conn->prepare(
        "SELECT (SELECT COUNT(*) FROM stock_in WHERE item_id = ? AND notes LIKE 'Pallet:%')
              - (SELECT COALESCE(SUM(plates), 0) FROM stock_out WHERE item_id = ?) AS plate_count"
    );
    $stmt->bind_param("ii", $row['id'], $row['id']);
    $stmt->execute();
    $pc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $plate_count = (int)$pc['plate_count'];

    $items[] = [
        'id'            => $row['id'],
        'name'          => $row['name'],
        'category'      => $row['category'],
        'fresh_qty'     => $fresh_qty,
        'damaged_qty'   => $damaged_qty,
        'total_qty'     => $total_qty,
        'total_physical'=> $physical_total,
        'plate_count'   => $plate_count,
        'low_stock'     => ($total_qty < 10) ? 1 : 0
    ];

    $totals['total_items']++;
    $totals['total_qty']      += $total_qty;
    $totals['total_physical'] += $physical_total;
    $totals['total_fresh']    += $fresh_qty;
    $totals['total_damaged']  += $damaged_qty;
}

echo json_encode(['success' => true, 'items' => $items, 'totals' => $totals]);
$conn->close();