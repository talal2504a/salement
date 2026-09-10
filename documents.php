<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="ur-PK">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Documents — Inventory Manager</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.doc-layout{display:grid;grid-template-columns:230px 1fr;gap:16px;align-items:start;}
.doc-menu{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:10px;}
.doc-group{margin-bottom:6px;}
.doc-group-title{font-size:12.5px;font-weight:700;color:var(--navy);padding:8px 10px;cursor:pointer;border-radius:6px;display:flex;justify-content:space-between;align-items:center;}
.doc-group-title:hover{background:#FAF8F3;}
.doc-group-title .arrow{color:var(--muted);font-size:10px;}
.doc-options{display:none;padding-left:8px;}
.doc-options.show{display:block;}
.doc-opt{display:block;width:100%;text-align:left;border:none;background:none;padding:7px 10px;border-radius:6px;font-size:12.5px;color:var(--text);cursor:pointer;font-family:'Inter',sans-serif;}
.doc-opt:hover{background:var(--amber-dim);}
.doc-opt.active{background:var(--amber);color:var(--navy);font-weight:600;}
.doc-toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap;}
.sel-info{font-size:12.5px;color:var(--muted);}
.totals-bar{background:#FCF6EA;border:1px solid var(--amber-dim);border-radius:8px;padding:10px 12px;font-size:12.5px;color:#7A5A1B;margin-bottom:10px;display:none;}
.totals-bar b{color:var(--navy);}
.table-wrap{overflow-x:auto;}
table.doc-table td{font-size:12.5px;}
table.doc-table td:first-child{width:30px;}
</style>
</head>
<body>
<div class="app">
    <?php include 'includes/loader.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <main>
        <div class="pagehead">
            <div>
                <h1>Documents</h1>
                <p class="desc">Koi bhi list select karo, rows tick karo, Excel/Word me download karo</p>
            </div>
        </div>

        <div class="doc-layout">
            <div class="doc-menu" id="docMenu">
                <div class="doc-group">
                    <div class="doc-group-title" onclick="toggleGroup(this)">Stock <span class="arrow">▼</span></div>
                    <div class="doc-options show">
                        <button class="doc-opt" data-report="stock" onclick="loadReport(this)">Stock by Item</button>
                        <button class="doc-opt" data-report="pallets" onclick="loadReport(this)">Stock IN (Pallets)</button>
                        <button class="doc-opt" data-report="sales" onclick="loadReport(this)">Stock OUT (Sales)</button>
                        <button class="doc-opt" data-report="ledger" onclick="loadReport(this)">Stock Ledger</button>
                    </div>
                </div>
                <div class="doc-group">
                    <div class="doc-group-title" onclick="toggleGroup(this)">Transactions <span class="arrow">▼</span></div>
                    <div class="doc-options show">
                        <button class="doc-opt" data-report="transactions" onclick="loadReport(this)">Recent Transactions</button>
                    </div>
                </div>
                <div class="doc-group">
                    <div class="doc-group-title" onclick="toggleGroup(this)">Lists <span class="arrow">▼</span></div>
                    <div class="doc-options show">
                        <button class="doc-opt" data-report="items" onclick="loadReport(this)">Items List</button>
                        <button class="doc-opt" data-report="parties" onclick="loadReport(this)">Parties List</button>
                    </div>
                </div>
                <div class="doc-group">
                    <div class="doc-group-title" onclick="toggleGroup(this)">Deliveries <span class="arrow">▼</span></div>
                    <div class="doc-options show">
                        <button class="doc-opt" data-report="orders" onclick="loadReport(this)">Orders</button>
                        <button class="doc-opt" data-report="delivery" onclick="loadReport(this)">Delivery History</button>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="doc-toolbar">
                    <div style="display:inline-flex;align-items:center;gap:10px;">
                        <label style="font-size:12.5px;color:var(--muted);display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" id="selectAll" onchange="toggleAll(this)"> Select All
                        </label>
                        <span class="sel-info" id="selInfo"> 0 selected</span>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <a href="#" class="btn" onclick="exportSheet('excel');return false;">Download Excel</a>
                        <a href="#" class="btn amber" onclick="exportSheet('word');return false;">Download Word</a>
                    </div>
                </div>
                <div class="totals-bar" id="totalsBar"></div>
                <div class="table-wrap">
                    <div id="reportBox"><div class="empty-note">Koi report select karo (left menu se)</div></div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
let currentReport = '';
let currentHeaders = [];
let currentRows = [];
let currentNumCols = [];

function toggleGroup(el) {
    el.nextElementSibling.classList.toggle('show');
}

function loadReport(btn) {
    document.querySelectorAll('.doc-opt').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentReport = btn.dataset.report;

    fetch('ajax/get_report_data.php?report=' + currentReport)
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                document.getElementById('reportBox').innerHTML = '<div class="empty-note">' + res.message + '</div>';
                document.getElementById('totalsBar').style.display = 'none';
                return;
            }
            currentHeaders = res.headers;
            currentRows = res.rows;
            currentNumCols = res.num_cols || [];
            renderTable();
        });
}

function renderTable() {
    const box = document.getElementById('reportBox');
    let html = '<table class="doc-table"><tr><th></th>';
    currentHeaders.forEach(h => { html += '<th>' + h + '</th>'; });
    html += '</tr>';

    currentRows.forEach(row => {
        html += '<tr><td><input type="checkbox" class="rowCheck" data-num=\'' + JSON.stringify(row.cells) + '\' onchange="updateInfo()"></td>';
        row.cells.forEach(c => { html += '<td>' + c + '</td>'; });
        html += '</tr>';
    });
    html += '</table>';
    box.innerHTML = html;
    document.getElementById('selectAll').checked = false;
    document.getElementById('totalsBar').style.display = 'none';
    updateInfo();
}

function toggleAll(el) {
    document.querySelectorAll('.rowCheck').forEach(r => r.checked = el.checked);
    updateInfo();
}

function updateInfo() {
    const checks = document.querySelectorAll('.rowCheck:checked');
    const n = checks.length;
    document.getElementById('selInfo').textContent = n + ' selected';

    const bar = document.getElementById('totalsBar');
    if (n === 0) { bar.style.display = 'none'; return; }

    let parts = ['<b>Total (' + n + ' rows):</b>'];
    currentNumCols.forEach(ci => {
        let sum = 0;
        checks.forEach(r => {
            const cells = JSON.parse(r.dataset.num);
            sum += parseInt(cells[ci]) || 0;
        });
        parts.push(currentHeaders[ci] + ': <b>' + sum + '</b>');
    });
    bar.innerHTML = parts.join(' &nbsp;|&nbsp; ');
    bar.style.display = 'block';
}

function getSelected() {
    return Array.from(document.querySelectorAll('.rowCheck:checked')).map((r, i) => i);
}

function exportSheet(type) {
    if (!currentReport) { alert('Pehle report select karo'); return; }
    const checkedCount = document.querySelectorAll('.rowCheck:checked').length;
    const allCount = currentRows.length;
    const ids = (checkedCount === allCount) ? '' : (checkedCount === 0 ? 'none' : getSelected().join(','));
    const url = 'export_' + type + '.php?report=' + currentReport + '&sel=' + ids;
    window.location.href = url;
}
</script>
</body>
</html>