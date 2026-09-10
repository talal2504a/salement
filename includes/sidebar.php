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
                <a href="documents.php" class="nav-item <?php echo $active_page === 'documents' ? 'active' : ''; ?>">
            <span class="dot"></span>Documents
        </a>
    </nav>

    <!-- USER INFO + LOGOUT (project theme ke mutabik) -->
        <!-- USER INFO -->
        <!-- USER INFO (inline style - CSS file ki zaroorat nahi) -->
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
    <div class="sidebar-foot">Inventory App v1</div>
</div>