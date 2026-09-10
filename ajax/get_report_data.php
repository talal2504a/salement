<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$report = $_GET['report'] ?? '';

$headers = [];
$rows = [];
$num_cols = [];

switch ($report) {
    case 'stock':
        $headers = ['Item', 'Category', 'Fresh', 'Damaged', 'Total', 'Plates'];
        $num_cols = [2, 3, 4, 5];
        $q = $conn->query("SELECT i.id, i.name, i.category,
            COALESCE((SELECT SUM(qty) FROM stock_in si WHERE si.item_id=i.id AND si.`condition`='fresh'),0) AS fresh,
            COALESCE((SELECT SUM(qty) FROM stock_in si WHERE si.item_id=i.id AND si.`condition`='damaged'),0) AS damaged,
            COALESCE((SELECT COUNT(*) FROM stock_in si WHERE si.item_id=i.id AND si.notes LIKE 'Pallet:%'),0) AS pin,
            COALESCE((SELECT COALESCE(SUM(plates),0) FROM stock_out so WHERE so.item_id=i.id),0) AS pout
            FROM items i ORDER BY i.name");
        while ($r = $q->fetch_assoc()) {
            $total = max(0, $r['fresh'] + $r['damaged']);
            $plates = max(0, $r['pin'] - $r['pout']);
            $rows[] = ['cells' => [$r['name'], $r['category'], $r['fresh'], $r['damaged'], $total, $plates]];
        }
        break;

    case 'pallets':
        $headers = ['Item', 'Pallet', 'Qty', 'Condition', 'Date'];
        $num_cols = [2];
        $q = $conn->query("SELECT i.name AS item, si.notes AS pallet, si.qty, si.`condition` AS cond, si.in_date AS dt
            FROM stock_in si JOIN items i ON i.id=si.item_id
            WHERE si.notes LIKE 'Pallet:%' ORDER BY si.in_date DESC");
        while ($r = $q->fetch_assoc()) {
            $rows[] = ['cells' => [$r['item'], $r['pallet'], $r['qty'], $r['cond'], $r['dt']]];
        }
        break;

    case 'sales':
        $headers = ['Item', 'Party', 'Qty', 'Plates', 'Pcs/Plate', 'Condition', 'Date'];
        $num_cols = [2, 3];
        $q = $conn->query("SELECT i.name AS item, so.customer_name AS party, so.qty, so.plates, so.pcs_per_plate,
            so.item_condition AS cond, so.out_date AS dt
            FROM stock_out so JOIN items i ON i.id=so.item_id ORDER BY so.out_date DESC");
        while ($r = $q->fetch_assoc()) {
            $rows[] = ['cells' => [$r['item'], $r['party'], $r['qty'], $r['plates'], $r['pcs_per_plate'], $r['cond'], $r['dt']]];
        }
        break;

    case 'transactions':
        $headers = ['Item', 'Type', 'Qty', 'Party / Ref', 'Date'];
        $num_cols = [2];
        $q = $conn->query("SELECT i.name, 'IN' AS type, si.qty, COALESCE(si.notes,'') AS ref, si.in_date AS dt
            FROM stock_in si JOIN items i ON i.id=si.item_id
            UNION ALL SELECT i.name, 'OUT', so.qty, COALESCE(so.customer_name,''), so.out_date
            FROM stock_out so JOIN items i ON i.id=so.item_id
            ORDER BY dt DESC LIMIT 200");
        while ($r = $q->fetch_assoc()) {
            $rows[] = ['cells' => [$r['name'], $r['type'], $r['qty'], $r['ref'], $r['dt']]];
        }
        break;

    case 'ledger':
        $headers = ['Item', 'In Qty', 'Out Qty', 'Balance'];
        $num_cols = [1, 2, 3];
        $q = $conn->query("SELECT i.name,
            COALESCE((SELECT SUM(qty) FROM stock_in si WHERE si.item_id=i.id),0) AS tin,
            COALESCE((SELECT SUM(qty) FROM stock_out so WHERE so.item_id=i.id),0) AS tout
            FROM items i ORDER BY i.name");
        while ($r = $q->fetch_assoc()) {
            $bal = $r['tin'] - $r['tout'];
            $rows[] = ['cells' => [$r['name'], $r['tin'], $r['tout'], $bal]];
        }
        break;

    case 'items':
        $headers = ['Item', 'Category', 'Pcs/Plate'];
        $num_cols = [2];
        $q = $conn->query("SELECT name, category, pcs_per_plate FROM items ORDER BY name");
        while ($r = $q->fetch_assoc()) {
            $rows[] = ['cells' => [$r['name'], $r['category'], $r['pcs_per_plate']]];
        }
        break;

    case 'parties':
        $headers = ['Party', 'Phone'];
        $num_cols = [];
        $q = $conn->query("SELECT name, COALESCE(phone,'') AS phone FROM parties ORDER BY name");
        while ($r = $q->fetch_assoc()) {
            $rows[] = ['cells' => [$r['name'], $r['phone']]];
        }
        break;

    case 'orders':
        $headers = ['Party', 'Item', 'Booked', 'Dispatched', 'Pending', 'Status'];
        $num_cols = [2, 3, 4];
        $q = $conn->query("SELECT p.name AS party, i.name AS item, o.booked_qty, o.dispatched_qty,
            (o.booked_qty - o.dispatched_qty) AS pending, o.status
            FROM orders o JOIN parties p ON p.id=o.party_id JOIN items i ON i.id=o.item_id
            WHERE o.status <> 'CANCELLED' ORDER BY o.order_date DESC");
        while ($r = $q->fetch_assoc()) {
            $rows[] = ['cells' => [$r['party'], $r['item'], $r['booked_qty'], $r['dispatched_qty'], $r['pending'], $r['status']]];
        }
        break;

    case 'delivery':
        $headers = ['Order#', 'DC No', 'Qty', 'Vehicle', 'Date'];
        $num_cols = [2];
        $q = $conn->query("SELECT dl.order_id, COALESCE(dl.dc_no,'') AS dc, dl.qty_delivered,
            COALESCE(dl.vehicle_no,'') AS veh, dl.delivery_date
            FROM delivery_log dl ORDER BY dl.delivery_date DESC");
        while ($r = $q->fetch_assoc()) {
            $rows[] = ['cells' => [$r['order_id'], $r['dc'], $r['qty_delivered'], $r['veh'], $r['delivery_date']]];
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Report nahi mili']);
        $conn->close();
        exit;
}

echo json_encode(['success' => true, 'headers' => $headers, 'rows' => $rows, 'num_cols' => $num_cols]);
$conn->close();