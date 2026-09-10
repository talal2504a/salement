<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$active_page = 'activity';
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="ur-PK">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Log — Inventory Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app">
    <?php include 'includes/loader.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <main>
        <div class="pagehead">
            <div>
                <h1>Activity Log</h1>
                <p class="desc">System me hone wale her kaam ka record</p>
            </div>
        </div>

        <div class="log-filter" style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
            <select id="logAction" onchange="loadLog()" style="padding:8px 12px;border:1px solid var(--line);border-radius:8px;font-size:13px;background:#FCFBF8;font-family:'Inter',sans-serif;">
                <option value="">Sab Actions</option>
                <option value="LOGIN">Login</option>
                <option value="STOCK IN">Stock IN</option>
                <option value="STOCK OUT">Stock OUT</option>
                <option value="PALLET ADD">Pallet Add</option>
                <option value="PALLET DELETE">Pallet Delete</option>
                <option value="DELIVERY">Delivery</option>
                <option value="ORDER CANCEL">Order Cancel</option>
                <option value="ORDER UPDATE">Order Update</option>
                <option value="ITEM ADD">Item Add</option>
                <option value="PARTY ADD">Party Add</option>
            </select>
            <span style="font-size:12.5px;color:var(--muted);" id="logCount"></span>
        </div>

        <div class="panel">
            <table>
                <tr><th>Time</th><th>User</th><th>Action</th><th>Details</th></tr>
                <tbody id="logBody"><tr><td colspan="4" class="empty-note">Loading...</td></tr></tbody>
            </table>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadLog);

function loadLog() {
    const action = document.getElementById('logAction').value;
    fetch('ajax/get_activity_log.php?action=' + encodeURIComponent(action))
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById('logBody');
            tbody.innerHTML = '';
            if (!res.success || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="empty-note">Abhi koi activity record nahi hai.</td></tr>';
                return;
            }
            const isDark = document.body.classList.contains('dark');
            const colorMap = isDark ? {
                'STOCK IN': '#7FC79B', 'PALLET ADD': '#7FC79B',
                'STOCK OUT': '#E08A6B', 'PALLET DELETE': '#E08A6B',
                'LOGIN': '#C7CFE0', 'DELIVERY': '#7FA8D6',
                'ORDER UPDATE': '#E3B45F', 'ORDER CANCEL': '#E08A6B',
                'ITEM ADD': '#D9B060', 'PARTY ADD': '#D9B060'
            } : {
                'STOCK IN': '#4C8F63', 'PALLET ADD': '#4C8F63',
                'STOCK OUT': '#BD5B3D', 'PALLET DELETE': '#BD5B3D',
                'LOGIN': '#1C2333', 'DELIVERY': '#3B6FA0',
                'ORDER UPDATE': '#9C6A15', 'ORDER CANCEL': '#BD5B3D',
                'ITEM ADD': '#7A5A1B', 'PARTY ADD': '#7A5A1B'
            };
            res.data.forEach(log => {
                const color = colorMap[log.action] || '#767C74';
                tbody.innerHTML += `
                    <tr>
                        <td style="white-space:nowrap;">${log.created_at}</td>
                        <td>${log.user_name}</td>
                        <td><span class="tag" style="background:${color}18;color:${color};">${log.action}</span></td>
                        <td style="color:var(--muted);">${log.details || ''}</td>
                    </tr>`;
            });
            document.getElementById('logCount').textContent = res.data.length + ' entries';
        });
}
</script>
</body>
</html>