<?php
// ============================================================
// DELIVERY HISTORY PAGE
// Created: 2026-09-08 (partner ne banaya)
// FIXED (2026-09-08): File ka naam 'deilvery_history.php' se
// 'delivery_history.php' kiya — sidebar ka link isi naam se hai
// CHANGED: fetch URL 'ajax/get_delivery_history.php' se
// 'get_delivery_history.php' kiya — backend file root mein hai
// (baaki sab pages bhi root pattern follow karte hain)
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$active_page = 'history';
?>
<!DOCTYPE html>
<html lang="ur-PK">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delivery History — Inventory Manager</title>
<!-- CHANGED (2026-09-08): ?v=2 cache-busting add kiya -->
<link rel="stylesheet" href="assets/style.css?v=2">
</head>
<body>
<div class="app">
    <?php include 'includes/loader.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main>
        <div class="pagehead">
            <div>
                <h1>Delivery History</h1>
                <p class="desc">Har delivery ka record — chahe order poora complete ho chuka ho ya abhi baaki ho</p>
            </div>
        </div>

        <div class="panel">
            <table>
                <tr>
                    <!-- CHANGED (2026-09-08): Time column REMOVE kar diya -->
                    <th>Date</th><th>Party</th><th>Item</th><th>Condition</th>
                    <th>Qty</th><th>DC No</th><th>Vehicle No</th><th>Note</th><th>Order Status</th>
                </tr>
                <tbody id="historyBody">
                    <!-- CHANGED (2026-09-08): Time column remove → colspan 9 -->
                    <tr><td colspan="9" class="empty-note">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
// ============================================================
// NEW (2026-09-08): Pakistani Date format helper
// (pending.php / stockout.php jaisi hi — same logic)
// "2026-09-05" → "5 Sep-26"
// CHANGED (2026-09-08): formatTimePK remove (time hat gaya)
function formatDatePK(iso) {
    if (!iso) return '—';
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const p = iso.split('-');
    return parseInt(p[2]) + ' ' + m[parseInt(p[1]) - 1] + '-' + p[0].slice(2);
}

document.addEventListener('DOMContentLoaded', loadHistory);

function loadHistory() {
    // CHANGED (2026-09-08): 'ajax/get_delivery_history.php' → 'get_delivery_history.php'
    // Backend file root folder mein hai (HANDOVER.md Section 3 dekho)
    fetch('ajax/get_delivery_history.php')
        .then(res => res.json())
        .then(res => {
            const body = document.getElementById('historyBody');
            body.innerHTML = '';

            if (!res.success || res.data.length === 0) {
                // CHANGED (2026-09-08): Time column remove → colspan 9
                body.innerHTML = '<tr><td colspan="9" class="empty-note">Abhi koi delivery record nahi hai.</td></tr>';
                return;
            }

            res.data.forEach(d => {
                const statusTag = d.status === 'COMPLETED'
                    ? '<span class="tag ok">Completed</span>'
                    : '<span class="tag pending">Partial</span>';

                body.innerHTML += `
                    <tr>
                        <!-- CHANGED (2026-09-08): Time column remove — sirf date -->
                        <td>${formatDatePK(d.delivery_date)}</td>
                        <td>${d.party_name}</td>
                        <td>${d.item_name}</td>
                        <td>${d.item_condition}</td>
                        <td class="num-cell">${d.qty_delivered}</td>
                        <td>${d.dc_no || '—'}</td>
                        <td>${d.vehicle_no || '—'}</td>
                        <td>${d.notes || '—'}</td>
                        <td>${statusTag}</td>
                    </tr>
                `;
            });
        });
}
</script>
</body>
</html>