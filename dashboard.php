<?php
$active_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="ur-PK">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Inventory Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app">
    <?php include 'includes/sidebar.php'; ?>

    <main>
        <div class="pagehead">
            <div>
                <h1>Dashboard</h1>
                <p class="desc">Poora stock overview — ek nazar mein</p>
            </div>
        </div>

        <div class="stat-row" id="statRow">
            <div class="stat-card"><div class="label">Total Items</div><div class="value" id="statTotalItems">—</div></div>
            <div class="stat-card"><div class="label">Book Stock (Available)</div><div class="value amber" id="statTotalQty">—</div></div>
            <div class="stat-card"><div class="label">Physical Stock (Godown)</div><div class="value" id="statPhysical" style="color:#3B6FA0;">—</div></div>
            <div class="stat-card"><div class="label">Fresh Stock</div><div class="value green" id="statFresh">—</div></div>
            <div class="stat-card"><div class="label">Damaged Stock</div><div class="value rust" id="statDamaged">—</div></div>
        </div>

        <div class="grid-2">
            <div class="panel">
                <h3>Stock by Item <span>Fresh / Damaged / Plates breakdown</span></h3>
                <table>
                    <tr><th>Item</th><th>Fresh</th><th>Damaged</th><th>Book Total</th><th>Physical Total</th><th>Plates</th><th>Status</th></tr>
                    <tbody id="itemTableBody">
                        <tr><td colspan="7" class="empty-note">Loading...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <h3>Recent Transactions</h3>
                <div id="recentList">
                    <div class="empty-note">Loading...</div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadSummary();
    loadRecent();
});

function loadSummary() {
    fetch('ajax/get_item_summary.php')
        .then(res => res.json())
        .then(res => {
            if (!res.success) return;

            document.getElementById('statTotalItems').textContent = res.totals.total_items;
            document.getElementById('statTotalQty').textContent = res.totals.total_qty + ' pcs';
            document.getElementById('statPhysical').textContent = res.totals.total_physical + ' pcs';
            document.getElementById('statFresh').textContent = res.totals.total_fresh + ' pcs';
            document.getElementById('statDamaged').textContent = res.totals.total_damaged + ' pcs';

            const tbody = document.getElementById('itemTableBody');
            tbody.innerHTML = '';

            if (res.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-note">Abhi koi item nahi hai. Stock IN page se add karein.</td></tr>';
                return;
            }

            res.items.forEach(item => {
                const statusTag = item.low_stock
                    ? '<span class="tag low">Low</span>'
                    : '<span class="tag ok">OK</span>';

                tbody.innerHTML += `
                    <tr>
                        <td>${item.name}</td>
                        <td class="num-cell">${item.fresh_qty}</td>
                        <td class="num-cell">${item.damaged_qty}</td>
                        <td class="num-cell">${item.total_qty}</td>
                        <td class="num-cell" style="color:#3B6FA0;font-weight:600;">${item.total_physical}</td>
                        <td class="num-cell" style="color:#B8860B;font-weight:600;">${item.plate_count}</td>
                        <td>${statusTag}</td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error(err));
}
function loadRecent() {
    fetch('ajax/get_recent_transactions.php')
        .then(res => res.json())
        .then(res => {
            if (!res.success) return;

            const list = document.getElementById('recentList');
            list.innerHTML = '';

            if (res.data.length === 0) {
                list.innerHTML = '<div class="empty-note">Abhi koi transaction nahi hui.</div>';
                return;
            }

            res.data.forEach(tx => {
                const isIn = tx.type === 'IN';
                const qtyClass = isIn ? 'in-txt' : 'out-txt';
                const qtySign = isIn ? '+' : '−';
                const partyText = tx.party_name ? tx.party_name : (tx.ref_no || '');

                let plateTxt = '';
                if (isIn && parseInt(tx.entry_plates) > 0) plateTxt = ' · ' + tx.entry_plates + ' plates IN';
                if (!isIn && parseInt(tx.plates) > 0) plateTxt = ' · ' + tx.plates + ' plates OUT' + (tx.pcs_per_plate ? ' (' + tx.pcs_per_plate + ' pcs/plate)' : '');

                list.innerHTML += `
                    <div class="item-row">
                        <div>
                            <div class="name">${tx.item_name} — ${tx.type}</div>
                            <div class="meta">${partyText} · ${tx.entry_date}${plateTxt}</div>
                        </div>
                        <div class="right"><div class="qty ${qtyClass}">${qtySign}${tx.quantity}</div></div>
                    </div>
                `;
            });
        })
        .catch(err => console.error(err));
}

</script>
</body>
</html>