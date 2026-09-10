<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'config/db.php';

$report = $_GET['report'] ?? '';
$sel = $_GET['sel'] ?? '';

$titles = [
    'stock'       => 'Stock by Item',
    'pallets'     => 'Stock IN (Pallets)',
    'sales'       => 'Stock OUT (Sales)',
    'transactions'=> 'Recent Transactions',
    'ledger'      => 'Stock Ledger',
    'items'       => 'Items List',
    'parties'     => 'Parties List',
    'orders'      => 'Orders',
    'delivery'    => 'Delivery History',
    'activity'    => 'Activity Log',
];
$title = $titles[$report] ?? 'Report';

header('Content-Type: application/msword');
header('Content-Disposition: attachment; filename="' . $report . '_' . date('Y-m-d') . '.doc"');
header('Pragma: no-cache');

if ($sel === 'none') {
    echo "<h3>Koi row select nahi hai.</h3>";
    exit;
}

$headers = [];
$num_cols = [];
$rows = [];

switch ($report) {
    case 'stock':
        $headers = ['Item', 'Category', 'Fresh', 'Damaged', 'Total', 'Plates'];
        $num_cols = [2, 3, 4, 5];
        $q = $conn->query("SELECT i.name, i.category,
            COALESCE((SELECT SUM(qty) FROM stock_in si WHERE si.item_id=i.id AND si.`condition`='fresh'),0) AS fresh,
            COALESCE((SELECT SUM(qty) FROM stock_in si WHERE si.item_id=i.id AND si.`condition`='damaged'),0) AS damaged,
            COALESCE((SELECT COUNT(*) FROM stock_in si WHERE si.item_id=i.id AND si.notes LIKE 'Pallet:%'),0) AS pin,
            COALESCE((SELECT COALESCE(SUM(plates),0) FROM stock_out so WHERE so.item_id=i.id),0) AS pout
            FROM items i ORDER BY i.name");
        $i = 0;
        while ($r = $q->fetch_assoc()) {
            if ($sel !== '' && strpos(',' . $sel . ',', ',' . $i . ',') === false) { $i++; continue; }
            $rows[] = [$r['name'], $r['category'], max(0,$r['fresh']), max(0,$r['damaged']), max(0,$r['fresh']+$r['damaged']), max(0,$r['pin']-$r['pout'])];
            $i++;
        }
        break;

    case 'pallets':
        $headers = ['Item', 'Pallet', 'Qty', 'Condition', 'Date'];
        $num_cols = [2];
        $q = $conn->query("SELECT i.name AS item, si.notes AS pallet, si.qty, si.`condition` AS cond, si.in_date AS dt
            FROM stock_in si JOIN items i ON i.id=si.item_id
            WHERE si.notes LIKE 'Pallet:%' ORDER BY si.in_date DESC");
        $i = 0;
        while ($r = $q->fetch_assoc()) {
            if ($sel !== '' && strpos(',' . $sel . ',', ',' . $i . ',') === false) { $i++; continue; }
            $rows[] = [$r['item'], $r['pallet'], $r['qty'], $r['cond'], $r['dt']];
            $i++;
        }
        break;

    case 'sales':
        $headers = ['Item', 'Party', 'Qty', 'Plates', 'Pcs/Plate', 'Condition', 'Date'];
        $num_cols = [2, 3];
        $q = $conn->query("SELECT i.name AS item, so.customer_name AS party, so.qty, so.plates, so.pcs_per_plate,
            so.item_condition AS cond, so.out_date AS dt
            FROM stock_out so JOIN items i ON i.id=so.item_id ORDER BY so.out_date DESC");
        $i = 0;
        while ($r = $q->fetch_assoc()) {
            if ($sel !== '' && strpos(',' . $sel . ',', ',' . $i . ',') === false) { $i++; continue; }
            $rows[] = [$r['item'], $r['party'], $r['qty'], $r['plates'], $r['pcs_per_plate'], $r['cond'], $r['dt']];
            $i++;
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
        $i = 0;
        while ($r = $q->fetch_assoc()) {
            if ($sel !== '' && strpos(',' . $sel . ',', ',' . $i . ',') === false) { $i++; continue; }
            $rows[] = [$r['name'], $r['type'], $r['qty'], $r['ref'], $r['dt']];
            $i++;
        }
        break;

    case 'ledger':
        $headers = ['Item', 'In Qty', 'Out Qty', 'Balance'];
        $num_cols = [1, 2, 3];
        $q = $conn->query("SELECT i.name,
            COALESCE((SELECT SUM(qty) FROM stock_in si WHERE si.item_id=i.id),0) AS tin,
            COALESCE((SELECT SUM(qty) FROM stock_out so WHERE so.item_id=i.id),0) AS tout
            FROM items i ORDER BY i.name");
        $i = 0;
        while ($r = $q->fetch_assoc()) {
            if ($sel !== '' && strpos(',' . $sel . ',', ',' . $i . ',') === false) { $i++; continue; }
            $rows[] = [$r['name'], $r['tin'], $r['tout'], $r['tin'] - $r['tout']];
            $i++;
        }
        break;

    case 'items':
        $headers = ['Item', 'Category', 'Pcs/Plate'];
        $num_cols = [2];
        $q = $conn->query("SELECT name, category, pcs_per_plate FROM items ORDER BY name");
        $i = 0;
        while ($r = $q->fetch_assoc()) {
            if ($sel !== '' && strpos(',' . $sel . ',', ',' . $i . ',') === false) { $i++; continue; }
            $rows[] = [$r['name'], $r['category'], $r['pcs_per_plate']];
            $i++;
        }
        break;

    case 'parties':
        $headers = ['Party', 'Phone'];
        $num_cols = [];
        $q = $conn->query("SELECT name, COALESCE(phone,'') AS phone FROM parties ORDER BY name");
        $i = 0;
        while ($r = $q->fetch_assoc()) {
            if ($sel !== '' && strpos(',' . $sel . ',', ',' . $i . ',') === false) { $i++; continue; }
            $rows[] = [$r['name'], $r['phone']];
            $i++;
        }
        break;

    case 'orders':
        $headers = ['Party', 'Item', 'Booked', 'Dispatched', 'Pending', 'Status'];
        $num_cols = [2, 3, 4];
        $q = $conn->query("SELECT p.name AS party, i.name AS item, o.booked_qty, o.dispatched_qty,
            (o.booked_qty - o.dispatched_qty) AS pending, o.status
            FROM orders o JOIN parties p ON p.id=o.party_id JOIN items i ON i.id=o.item_id
            WHERE o.status <> 'CANCELLED' ORDER BY o.order_date DESC");
        $i = 0;
        while ($r = $q->fetch_assoc()) {
            if ($sel !== '' && strpos(',' . $sel . ',', ',' . $i . ',') === false) { $i++; continue; }
            $rows[] = [$r['party'], $r['item'], $r['booked_qty'], $r['dispatched_qty'], $r['pending'], $r['status']];
            $i++;
        }
        break;

    case 'delivery':
        $headers = ['Order#', 'DC No', 'Qty (Pcs)', 'Vehicle', 'Date'];
        $num_cols = [2];
        $q = $conn->query("SELECT dl.order_id, COALESCE(dl.dc_no,'') AS dc, dl.qty_delivered,
            COALESCE(dl.vehicle_no,'') AS veh, dl.delivery_date
            FROM delivery_log dl ORDER BY dl.delivery_date DESC");
        $i = 0;
        while ($r = $q->fetch_assoc()) {
            if ($sel !== '' && strpos(',' . $sel . ',', ',' . $i . ',') === false) { $i++; continue; }
            $rows[] = [$r['order_id'], $r['dc'], $r['qty_delivered'], $r['veh'], $r['delivery_date']];
            $i++;
        }
        break;

    case 'activity':
        $headers = ['User', 'Action', 'Details', 'Time'];
        $num_cols = [];
        $q = $conn->query("SELECT user_name, action, details, created_at FROM activity_log ORDER BY id DESC");
        $i = 0;
        while ($r = $q->fetch_assoc()) {
            if ($sel !== '' && strpos(',' . $sel . ',', ',' . $i . ',') === false) { $i++; continue; }
            $rows[] = [$r['user_name'], $r['action'], $r['details'], $r['created_at']];
            $i++;
        }
        break;

    default:
        echo "Report nahi mili";
        exit;
}
?>

<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Calibri, Arial, sans-serif; margin: 16px; color: #1C2333; }
    .company { font-size: 18px; font-weight: bold; color: #1C2333; }
    .sub { font-size: 11px; color: #767C74; margin-top: 2px; }
    .report-title { font-size: 14px; font-weight: bold; color: #E8A33D; margin-top: 14px; margin-bottom: 4px; }
    .meta { font-size: 10px; color: #767C74; margin-bottom: 12px; }
    table { border-collapse: collapse; width: 100%; }
    th { background: #1C2333; color: #FFFFFF; padding: 7px 10px; font-size: 11px; text-align: left; }
    td { padding: 6px 10px; font-size: 11px; border-bottom: 1px solid #E4E1D8; }
    tr.alt td { background: #F8F6F1; }
    tr.tot td { background: #FCF0DB; font-weight: bold; border-top: 2px solid #E8A33D; }
</style>
</head>
<body>
    <div class="company">Inventory Manager</div>
    <div class="sub">Solar Stock Control — <?php echo htmlspecialchars($title); ?></div>
    <div class="report-title"><?php echo htmlspecialchars($title); ?></div>
    <div class="meta">Generated: <?php echo date('d-F-Y h:i A'); ?></div>

    <table>
        <tr>
            <?php foreach ($headers as $h) { echo "<th>" . htmlspecialchars($h) . "</th>"; } ?>
        </tr>
        <?php
        $n = 0;
        foreach ($rows as $r) {
            echo $n % 2 ? "<tr class='alt'>" : "<tr>";
            foreach ($r as $c) { echo "<td>" . htmlspecialchars($c) . "</td>"; }
            echo "</tr>";
            $n++;
        }
        if (!empty($num_cols) && count($rows) > 0) {
            $sums = array_fill(0, count($rows[0]), 0);
            foreach ($num_cols as $ci) {
                foreach ($rows as $r) { $sums[$ci] += (int)$r[$ci]; }
            }
            echo "<tr class='tot'>";
            for ($c = 0; $c < count($rows[0]); $c++) {
                if (in_array($c, $num_cols)) {
                    echo "<td>" . $sums[$c] . "</td>";
                } elseif ($c === 0) {
                    echo "<td>TOTAL</td>";
                } else {
                    echo "<td></td>";
                }
            }
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>