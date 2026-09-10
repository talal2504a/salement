<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

require_once 'config/db.php';

$db = $conn->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="backup_' . $db . '_' . date('Y-m-d_H-i') . '.sql"');

echo "-- Backup of $db on " . date('Y-m-d H:i:s') . "\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

$tables = $conn->query("SHOW TABLES");
while ($t = $tables->fetch_row()) {
    $table = $t[0];

    $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];
    echo "-- Table: $table\n";
    echo "DROP TABLE IF EXISTS `$table`;\n";
    echo "$create;\n\n";

    $rows = $conn->query("SELECT * FROM `$table`");
    $cols = $rows->num_rows;
    if ($cols === 0) continue;

    $fields = $rows->fetch_fields();
    $names = array_map(fn($f) => "`" . $f->name . "`", $fields);
    echo "INSERT INTO `$table` (" . implode(", ", $names) . ") VALUES\n";

    $i = 0;
    while ($row = $rows->fetch_assoc()) {
        $vals = [];
        foreach ($fields as $f) {
            $v = $row[$f->name];
            $vals[] = ($v === null) ? 'NULL' : "'" . addslashes($v) . "'";
        }
        $end = (++$i === $cols) ? ";\n\n" : ",\n";
        echo "(" . implode(", ", $vals) . ")$end";
    }
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
echo "-- Backup complete\n";
$conn->close();