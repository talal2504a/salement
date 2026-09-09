<?php
if (!isset($active_page)) { $active_page = ''; }
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
    </nav>
    <div class="sidebar-foot">Inventory App v1</div>
</div>