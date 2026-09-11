<?php
// ajax/upload_stock_file.php — scan / import Excel/CSV/DOCX (role auto-detect)
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload nahi hui']);
    exit;
}

$mode   = $_POST['mode'] ?? 'scan';
$tmp    = $_FILES['file']['tmp_name'];
$ext    = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
$tempPath = sys_get_temp_dir() . '/stock_' . uniqid() . '.' . $ext;
move_uploaded_file($tmp, $tempPath);

function readTable($filePath, $ext) {
    $rows = [];
    if ($ext === 'csv') {
        $handle = fopen($filePath, 'r');
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map('trim', $row);
        }
        fclose($handle);
    } elseif ($ext === 'docx') {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $doc = new DOMDocument();
            if ($xml && $doc->loadXML($xml)) {
                foreach ($doc->getElementsByTagNameNS('*', 'tr') as $tr) {
                    $rowVals = [];
                    foreach ($tr->getElementsByTagNameNS('*', 'tc') as $tc) {
                        $cellText = '';
                        foreach ($tc->getElementsByTagNameNS('*', 't') as $t) {
                            $cellText .= $t->nodeValue;
                        }
                        $rowVals[] = trim($cellText);
                    }
                    $rows[] = $rowVals;
                }
            }
        }
    } else {
        require_once __DIR__ . '/../lib/SimpleXLSX.php';
        $xlsx = \Shuchkin\SimpleXLSX::parse($filePath);
        if ($xlsx) {
            foreach ($xlsx->rows() as $row) {
                $rows[] = array_map('trim', (array)$row);
            }
        }
    }
    return $rows;
}

// role detect — header naam se, SR.NO skip
function detectRoles($header, $selIdx) {
    $roles  = ['item' => null, 'fresh' => null, 'damage' => null, 'wattage' => null];
    $skip   = [];
    $unmatched = [];

    $isSkip = function ($n) {
        return preg_match('/^sr$|srno|s\.?no|sno|serial|^no$|^s#|srno\./', $n);
    };
    $isItem = function ($n) {
        return preg_match('/item|product|^name|desc/', $n);
    };
    $isFresh = function ($n) {
        return preg_match('/fresh|good/', $n);
    };
    $isDamage = function ($n) {
        return preg_match('/damage|dmg|broken|bad/', $n);
    };
    $isWattage = function ($n) {
        return preg_match('/watt|power|^w$/', $n);
    };

    foreach ($selIdx as $i) {
        $n = strtolower(preg_replace('/[^A-Za-z]/', '', $header[$i] ?? ''));
        if ($isSkip($n)) { $skip[] = $i; continue; }

        if ($roles['item'] === null && $isItem($n)) {
            $roles['item'] = $i;
        } elseif ($roles['fresh'] === null && $isFresh($n)) {
            $roles['fresh'] = $i;
        } elseif ($roles['damage'] === null && $isDamage($n)) {
            $roles['damage'] = $i;
        } elseif ($roles['wattage'] === null && $isWattage($n)) {
            $roles['wattage'] = $i;
        } else {
            $unmatched[] = $i;
        }
    }

    // unmatched → order se fallback (item, fresh, damage)
    foreach (['item', 'fresh', 'damage'] as $r) {
        if ($roles[$r] === null && count($unmatched)) {
            $roles[$r] = array_shift($unmatched);
        }
    }

    return ['roles' => $roles, 'skip' => $skip];
}

try {
    $rows = readTable($tempPath, $ext);
    @unlink($tempPath);

    if (empty($rows)) {
        echo json_encode(['success' => false, 'message' => 'File khali hai ya padh nahi saka']);
        exit;
    }

    $header = $rows[0];

    // ===== SCAN MODE =====
    if ($mode === 'scan') {
        $allIdx = array_keys($header);
        $detect = detectRoles($header, $allIdx);

        echo json_encode([
            'success' => true,
            'mode'    => 'scan',
            'columns' => $header,
            'roles'   => $detect['roles'],
            'skip'    => $detect['skip'],
            'total'   => max(count($rows) - 1, 0),
            'sample'  => array_slice(array_slice($rows, 1), 0, 2)
        ]);
        exit;
    }

    // ===== IMPORT MODE =====
    $cols = json_decode($_POST['cols'] ?? '[]', true);
    if (empty($cols)) {
        echo json_encode(['success' => false, 'message' => 'Koi column select nahi kiya']);
        exit;
    }

    $selIdx = [];
    foreach ($cols as $c) {
        $p = array_search($c, $header);
        if ($p !== false) $selIdx[] = $p;
    }
    if (empty($selIdx)) {
        echo json_encode(['success' => false, 'message' => 'Columns se match nahi hua']);
        exit;
    }

    $detect  = detectRoles($header, $selIdx);
    $roles   = $detect['roles'];

    $data = [];
    for ($i = 1; $i < count($rows); $i++) {
        $r = $rows[$i];

        $item  = ($roles['item'] !== null && isset($r[$roles['item']])) ? trim($r[$roles['item']]) : '';
        $watt  = ($roles['wattage'] !== null && isset($r[$roles['wattage']]))
                 ? intval(preg_replace('/[^0-9]/', '', $r[$roles['wattage']]))
                 : 0;
        $fresh = ($roles['fresh'] !== null && isset($r[$roles['fresh']]))
                 ? intval(preg_replace('/[^0-9]/', '', $r[$roles['fresh']]))
                 : 0;
        $dmg   = ($roles['damage'] !== null && isset($r[$roles['damage']]))
                 ? intval(preg_replace('/[^0-9]/', '', $r[$roles['damage']]))
                 : 0;

        if ($item === '') continue;

        // wattage merge — number already hai to W wahan lagao, nahi to 580W
        if ($watt > 0) {
            if (stripos($item, 'W') !== false || stripos($item, 'w') !== false) {
                // pehle se W maujood — kuch nahi
            } elseif (strpos($item, (string)$watt) !== false) {
                // number name me hai — W us number ke turant baad
                $item = preg_replace('/' . $watt . '/', $watt . 'W', $item, 1);
            } elseif (strpos($item, $watt . 'W') === false) {
                // number nahi — 580W end pe
                $item = trim($item . ' ' . $watt . 'W');
            }
        }

        $data[] = ['item' => $item, 'fresh' => $fresh, 'damage' => $dmg];
    }

    echo json_encode([
        'success' => true,
        'mode'    => 'import',
        'columns' => $header,
        'roles'   => $roles,
        'skip'    => $detect['skip'],
        'total'   => count($data),
        'data'    => $data
    ]);

} catch (Exception $e) {
    @unlink($tempPath);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>