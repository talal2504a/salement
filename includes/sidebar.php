<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($active_page)) { $active_page = ''; }

// User name session mein nahi toh database se lao
if (!isset($_SESSION['user_name']) && isset($_SESSION['user_id'])) {
    require_once 'config/db.php';
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if ($u) {
        $_SESSION['user_name'] = $u['name'];
        $_SESSION['user_email'] = $u['email'];
    }
    $stmt->close();
}
?>
<style>
body.dark{
    --bg:#141821;
    --panel:#1C2333;
    --panel-2:#262E42;
    --line:#333D52;
    --text:#E8EDF5;
    --muted:#9AA4B8;
    --amber-dim:#8A6A2F;
    --green-bg:#1E2E26;
    --rust-bg:#33221C;
}
body.dark, body.dark .app{background:var(--bg);}
body.dark main{background:var(--bg);}
body.dark .sidebar{background:#0F121A;}
body.dark .brand .sub{color:rgba(255,255,255,0.45);}
body.dark .btn.ghost{color:var(--text);}
body.dark .field input,body.dark .field select,
body.dark .log-filter select{background:#141821;color:var(--text);border-color:var(--line);}
body.dark .cond-toggle label{background:var(--panel-2);color:var(--muted);border-color:var(--line);}
body.dark .doc-opt:hover,body.dark .doc-group-title:hover{background:var(--panel-2);}
body.dark .doc-opt.active{color:var(--amber);}
body.dark .totals-bar{background:#262E42;border-color:var(--amber-dim);color:#E8EDF5;}
body.dark .totals-bar b{color:var(--amber);}
body.dark .pd-card .head{background:var(--panel-2);}
body.dark .item-select{background:var(--panel);}
body.dark .ledger-summary .box{background:var(--panel);}
body.dark .modal-box{background:var(--panel);}
body.dark .modal-box .modal-sub{color:var(--muted);}
body.dark th{color:var(--muted);}
body.dark .stat-card .value{color:var(--text);}
body.dark .panel h3{color:var(--text);}
body.dark .item-row .name{color:var(--text);}
body.dark .tag.in-txt{color:var(--green);}
body.dark .tag.ok{background:var(--green-bg);color:var(--green);}
body.dark .tag.low{background:var(--rust-bg);color:var(--rust);}
body.dark .tag.pending{background:#33220f;color:#F3D9A8;}
</style>
<div class="sidebar">
    <div class="brand">
        <div class="mark">IM</div>
        <div class="name">Inventory Manager</div>
        <div class="sub">Solar Stock Control</div>
    </div>
    <nav>
        <a href="dashboard.php" class="nav-item <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
            <span class="dot"></span>Dashboard
        </a>
        <a href="stockin.php" class="nav-item <?php echo $active_page === 'stockin' ? 'active' : ''; ?>">
            <span class="dot"></span>Stock IN
        </a>
        <a href="stockout.php" class="nav-item <?php echo $active_page === 'stockout' ? 'active' : ''; ?>">
            <span class="dot"></span>Stock OUT
        </a>
        <a href="item_ledger.php" class="nav-item <?php echo $active_page === 'ledger' ? 'active' : ''; ?>">
            <span class="dot"></span>Item Ledger
        </a>
        <a href="pending.php" class="nav-item <?php echo $active_page === 'pending' ? 'active' : ''; ?>">
            <span class="dot"></span>Pending Deliveries
        </a>
        <a href="delivery_history.php" class="nav-item <?php echo $active_page === 'history' ? 'active' : ''; ?>">
            <span class="dot"></span>Delivery History
        </a>
                <a href="activity_log.php" class="nav-item <?php echo $active_page === 'activity' ? 'active' : ''; ?>">
            <span class="dot"></span>Activity Log
        </a>
        <a href="documents.php" class="nav-item <?php echo $active_page === 'documents' ? 'active' : ''; ?>">
            <span class="dot"></span>Documents
        </a>
    </nav>

    <!-- USER INFO + LOGOUT + DARK MODE -->
    <div style="display:flex;align-items:center;gap:10px;padding:14px 20px 0 20px;margin-top:14px;">
        <div style="width:32px;height:32px;border-radius:8px;flex-shrink:0;background:#E8A33D;color:#1C2333;display:flex;align-items:center;justify-content:center;font-family:'Sora',sans-serif;font-weight:700;font-size:13px;">
            <?php echo htmlspecialchars(strtoupper(substr(($_SESSION['user_name'] ?? 'User'), 0, 1))); ?>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:12.5px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
            </div>
        </div>
    </div>

    <div style="padding:10px 20px 0 20px;">
        <a href="logout.php" style="display:flex;align-items:center;justify-content:center;gap:7px;width:100%;padding:7px 12px;border-radius:8px;background:rgba(255,255,255,0.08);color:#fff;font-size:12px;font-weight:600;font-family:'Inter',sans-serif;text-decoration:none;border:1px solid rgba(255,255,255,0.16);">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Logout
        </a>
    </div>

    <!-- DARK MODE TOGGLE (logout ke niche) -->
    <button id="themeToggle" onclick="toggleTheme()" style="display:flex;align-items:center;justify-content:center;gap:7px;width:calc(100% - 40px);padding:7px 12px;border-radius:8px;background:rgba(255,255,255,0.08);color:#fff;font-size:12px;font-weight:600;font-family:'Inter',sans-serif;border:1px solid rgba(255,255,255,0.16);cursor:pointer;margin:8px 20px 0 20px;">
        <span id="themeIcon">🌙</span><span id="themeLabel">Dark Mode</span>
    </button>

    <div class="sidebar-foot">Inventory App v1</div>
</div>

<script>
(function(){
    if (localStorage.getItem('im_theme') === 'dark') {
        document.body.classList.add('dark');
        const i = document.getElementById('themeIcon');
        const l = document.getElementById('themeLabel');
        if (i) i.textContent = '☀️';
        if (l) l.textContent = 'Light Mode';
    }
})();
function toggleTheme(){
    const isDark = document.body.classList.toggle('dark');
    localStorage.setItem('im_theme', isDark ? 'dark' : 'light');
    document.getElementById('themeIcon').textContent = isDark ? '☀️' : '🌙';
    document.getElementById('themeLabel').textContent = isDark ? 'Light Mode' : 'Dark Mode';
}
</script>