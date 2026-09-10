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
];
$title = $titles[$report] ?? 'Report';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $report . '_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');

if ($sel === 'none') {
    echo "Koi row select nahi hai.";
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

    default:
        echo "Report nahi mili";
        exit;
}

// Column widths (headers + data ke mutabiq)
$widths = [];
foreach ($headers as $h) { $widths[] = strlen($h) * 7 + 15; }
foreach ($rows as $r) {
    foreach ($r as $ci => $v) {
        $w = strlen($v) * 6 + 10;
        if ($w > $widths[$ci]) $widths[$ci] = $w;
    }
}
foreach ($widths as $ci => $w) {
    $widths[$ci] = min(280, max(60, $w));
}

function esc_cell($v) {
    return htmlspecialchars((string)$v, ENT_XML1, 'UTF-8');
}

// TOTAL row values
$tot_cells = [];
if (!empty($num_cols) && count($rows) > 0) {
    $sums = array_fill(0, count($rows[0]), 0);
    foreach ($num_cols as $ci) {
        foreach ($rows as $r) { $sums[$ci] += (int)$r[$ci]; }
    }
    foreach ($rows[0] as $ci => $v) {
        $tot_cells[$ci] = in_array($ci, $num_cols) ? $sums[$ci] : ($ci === 0 ? 'TOTAL' : '');
    }
}

$colspan = count($headers);
?>
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
    <Author>Inventory Manager</Author>
    <Title><?php echo esc_cell($title); ?></Title>
    <Created><?php echo gmdate('Y-m-d\TH:i:s\Z'); ?></Created>
</DocumentProperties>
<Styles>
    <Style ss:ID="sTitle">
        <Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#1C2333"/>
    </Style>
    <Style ss:ID="sSub">
        <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#767C74"/>
    </Style>
    <Style ss:ID="sReport">
        <Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1" ss:Color="#E8A33D"/>
    </Style>
    <Style ss:ID="sHead">
        <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>
        <Interior ss:Color="#1C2333" ss:Pattern="Solid"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1C2333"/>
        </Borders>
    </Style>
    <Style ss:ID="sCell">
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E4E1D8"/>
        </Borders>
    </Style>
    <Style ss:ID="sAlt">
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <Interior ss:Color="#F8F6F1" ss:Pattern="Solid"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E4E1D8"/>
        </Borders>
    </Style>
    <Style ss:ID="sNum">
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <NumberFormat ss:Format="#,##0"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E4E1D8"/>
        </Borders>
    </Style>
    <Style ss:ID="sNumAlt">
        <Font ss:FontName="Calibri" ss:Size="11"/>
        <Interior ss:Color="#F8F6F1" ss:Pattern="Solid"/>
        <NumberFormat ss:Format="#,##0"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E4E1D8"/>
        </Borders>
    </Style>
    <Style ss:ID="sTot">
        <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#1C2333"/>
        <Interior ss:Color="#FCF0DB" ss:Pattern="Solid"/>
        <NumberFormat ss:Format="#,##0"/>
        <Borders>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#E8A33D"/>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#E8A33D"/>
        </Borders>
    </Style>
</Styles>
<Worksheet ss:Name="<?php echo esc_cell(substr($title, 0, 30)); ?>">
    <Table ss:StyleID="sCell">
        <?php foreach ($widths as $w) { echo "<Column ss:Width=\"$w\"/>"; } ?>
        <Row ss:Height="24">
            <Cell ss:MergeAcross="<?php echo ($colspan - 1); ?>" ss:StyleID="sTitle"><Data ss:Type="String">Inventory Manager</Data></Cell>
        </Row>
        <Row>
            <Cell ss:MergeAcross="<?php echo ($colspan - 1); ?>" ss:StyleID="sSub"><Data ss:Type="String">Solar Stock Control - <?php echo esc_cell($title); ?></Data></Cell>
        </Row>
        <Row>
            <Cell ss:MergeAcross="<?php echo ($colspan - 1); ?>" ss:StyleID="sReport"><Data ss:Type="String"><?php echo esc_cell($title); ?></Data></Cell>
        </Row>
        <Row>
            <Cell ss:MergeAcross="<?php echo ($colspan - 1); ?>" ss:StyleID="sSub"><Data ss:Type="String">Generated: <?php echo date('d-F-Y h:i A'); ?></Data></Cell>
        </Row>
        <Row ss:Height="20">
            <?php foreach ($headers as $h) { echo "<Cell ss:StyleID=\"sHead\"><Data ss:Type=\"String\">" . esc_cell($h) . "</Data></Cell>"; } ?>
        </Row>
        <?php
        $n = 0;
        foreach ($rows as $r) {
            $st = ($n % 2) ? 'sAlt' : 'sCell';
            echo "<Row>";
            foreach ($r as $ci => $v) {
                $style = in_array($ci, $num_cols) ? ($n % 2 ? 'sNumAlt' : 'sNum') : $st;
                $type  = in_array($ci, $num_cols) ? 'Number' : 'String';
                echo "<Cell ss:StyleID=\"$style\"><Data ss:Type=\"$type\">" . ($type === 'Number' ? (int)$v : esc_cell($v)) . "</Data></Cell>";
            }
            echo "</Row>";
            $n++;
        }
        if (!empty($tot_cells)) {
            echo "<Row>";
            foreach ($tot_cells as $ci => $v) {
                $type = in_array($ci, $num_cols) ? 'Number' : 'String';
                echo "<Cell ss:StyleID=\"sTot\"><Data ss:Type=\"$type\">" . ($type === 'Number' ? (int)$v : esc_cell($v)) . "</Data></Cell>";
            }
            echo "</Row>";
        }
        ?>
    </Table>
</Worksheet>
</Workbook>