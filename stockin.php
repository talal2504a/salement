<?php
$active_page = 'stockin';
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="ur-PK">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock IN — Inventory Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app">
    <?php include 'includes/sidebar.php'; ?>

    <main>
        <div class="pagehead">
            <div>
                <h1>📦 Stock IN</h1>
                <p class="desc">Add new stock OR manage pallets for each item</p>
            </div>
        </div>

        <div class="form-wrap" style="grid-template-columns:1fr;max-width:520px;">
            <div class="form-card">
                <div class="msg-box" id="msgBox"></div>

                <form id="stockInForm">
                    <div class="field">
                        <label>Item</label>
                        <select id="itemSelect" required>
                            <option value="">Loading items...</option>
                        </select>
                    </div>

                    <div class="field" id="newItemBox" style="display:none;">
                        <label>New Item Name</label>
                        <input type="text" id="newItemName" placeholder="e.g. 725W Bifacial Panel">
                        <div class="split-row" style="margin-top:10px;">
                            <input type="text" id="newItemUnit" placeholder="Unit (pcs/box/kg)" value="pcs">
                            <input type="number" id="newItemReorder" placeholder="Reorder level">
                        </div>
                        <button type="button" class="btn ghost" style="margin-top:10px;width:100%;" onclick="saveNewItem()">Save New Item</button>
                    </div>

                    <!-- PLATE INPUT -->
                    <div class="field">
                        <label>Plate Name</label>
                        <input type="text" id="plateName" placeholder="e.g. Plate A">
                    </div>

                    <div class="field">
                        <label>Pcs per Plate</label>
                        <input type="number" id="pcsPerPlate" min="1" value="36">
                    </div>

                    <div class="split-row">
                        <div class="field">
                            <label>Fresh Quantity</label>
                            <input type="number" id="freshQty" min="0" value="0" oninput="calcPlates()">
                        </div>
                        <div class="field">
                            <label>Damaged Quantity</label>
                            <input type="number" id="damagedQty" min="0" value="0" oninput="calcPlates()">
                        </div>
                    </div>

                    <!-- CALCULATION RESULT -->
                    <div id="calcBox" style="display:none; background:#FAF8F3; border:1px dashed var(--amber); padding:12px; border-radius:8px; margin-bottom:15px;">
                        <div style="font-size:14px; font-weight:600; margin-bottom:8px;">📊 Plate Calculation</div>
                        <div style="font-size:13px; margin-bottom:6px;">
                            Total Pcs: <b id="totalPcs">0</b>
                        </div>
                        <div style="font-size:16px; font-weight:700; color:var(--navy);">
                            Plate Count: <span id="plateCount">0</span>
                            <span style="font-size:12px; font-weight:400; color:var(--muted);">
                                (baqi: <span id="remainingPcs">0</span> pcs)
                            </span>
                        </div>
                        <button type="button" class="btn amber" style="margin-top:10px; width:100%;" onclick="savePlates()">
                            💾 Save <span id="savePlateLabel">0</span> Plates in Stock
                        </button>
                    </div>

                    <!-- PALLETS SECTION -->
                    <div id="palletSection" style="display:none; margin-top:20px; padding-top:15px; border-top:2px dashed var(--line);">
                        <h4 style="font-size:13px; margin:0 0 10px 0; font-weight:600; color:var(--muted); text-transform:uppercase;">
                            📋 Saved Plates — <span id="selectedItemName"></span>
                        </h4>
                        
                        <div id="palletList"></div>
                    </div>

                    <div class="field">
                        <label>Supplier / Reference No</label>
                        <input type="text" id="refNo" placeholder="e.g. GRN-105">
                    </div>

                    <div class="field">
                        <label>Date</label>
                        <input type="date" id="entryDate">
                    </div>

                    <button type="submit" class="btn amber" style="width:100%;">Save Entry</button>
                </form>
            </div>
        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadItems();
    document.getElementById('entryDate').value = new Date().toISOString().split('T')[0];
});

function loadItems() {
    fetch('ajax/get_items.php')
        .then(res => res.json())
        .then(res => {
            const select = document.getElementById('itemSelect');
            select.innerHTML = '<option value="">-- Select Item --</option>';
            res.data.forEach(item => {
                select.innerHTML += `<option value="${item.id}" data-name="${item.name}">${item.name}</option>`;
            });
            select.innerHTML += `<option value="__new__">+ Add New Item</option>`;
        });
}

document.getElementById('itemSelect').addEventListener('change', function () {
    const val = this.value;
    document.getElementById('newItemBox').style.display = (val === '__new__') ? 'block' : 'none';
    
    if (val && val !== '__new__') {
        const selectedOption = this.options[this.selectedIndex];
        const itemName = selectedOption.getAttribute('data-name');
        document.getElementById('selectedItemName').textContent = itemName;
        document.getElementById('palletSection').style.display = 'block';
        loadPallets(val);
    } else {
        document.getElementById('palletSection').style.display = 'none';
    }
});

// ---- PLATE CALCULATION ----
function calcPlates() {
    const fresh = parseInt(document.getElementById('freshQty').value) || 0;
    const damaged = parseInt(document.getElementById('damagedQty').value) || 0;
    const perPlate = parseInt(document.getElementById('pcsPerPlate').value) || 0;

    const total = fresh + damaged;
    document.getElementById('totalPcs').textContent = total;
    document.getElementById('calcBox').style.display = (total > 0 && perPlate > 0) ? 'block' : 'none';

    if (perPlate > 0 && total > 0) {
        const count = Math.floor(total / perPlate);
        const remaining = total % perPlate;
        document.getElementById('plateCount').textContent = count;
        document.getElementById('remainingPcs').textContent = remaining;
        document.getElementById('savePlateLabel').textContent = count;
    } else {
        document.getElementById('plateCount').textContent = '0';
        document.getElementById('remainingPcs').textContent = '0';
        document.getElementById('savePlateLabel').textContent = '0';
    }
}

// ---- SAVE PLATES IN STOCK ----
function savePlates() {
    const itemId = document.getElementById('itemSelect').value;
    const plateName = document.getElementById('plateName').value.trim();
    const perPlate = parseInt(document.getElementById('pcsPerPlate').value) || 0;
    const count = parseInt(document.getElementById('plateCount').textContent) || 0;

    if (!itemId) { alert('Pehle item select karo!'); return; }
    if (!plateName) { alert('Plate name daalo!'); return; }
    if (count <= 0) { alert('Plate count zero hai!'); return; }

    let done = 0;
    for (let i = 1; i <= count; i++) {
        const pName = `${plateName} ${i}`;
        const formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('pallet_name', pName);
        formData.append('qty', perPlate);

        fetch('ajax/add_pallet.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            done++;
            if (done === count) {
                alert(`${count} plates save ho gaye!`);
                loadPallets(itemId);
            }
        })
        .catch(err => {
            done++;
            if (done === count) {
                alert('Kuch plates me error aya: ' + err);
            }
        });
    }
}

function loadPallets(itemId) {
    fetch(`ajax/get_pallets.php?item_id=${itemId}`)
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('palletList');
            if (data.success && data.pallets.length > 0) {
                let html = '';
                data.pallets.forEach(p => {
                    html += `
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:5px 0; border-bottom:1px dashed var(--line); font-size:12px;">
                            <span>
                                • ${p.name}
                                <span style="font-size:10px; color:var(--muted); margin-left:6px;">${p.date}</span>
                            </span>
                            <span style="display:flex; align-items:center; gap:8px;">
                                <span style="font-weight:600; font-family:'Sora',sans-serif;">Qty: ${p.quantity}</span>
                                <button onclick="deletePallet(${p.id})" 
                                        style="background:none; border:none; color:var(--rust); cursor:pointer; font-size:13px;">✕</button>
                            </span>
                        </div>`;
                });
                list.innerHTML = html;
            } else {
                list.innerHTML = '';
            }
        });
}

function deletePallet(palletId) {
    if (!confirm('Sure? Ye plate delete ho jayega.')) return;
    fetch('ajax/delete_pallet.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${palletId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const itemId = document.getElementById('itemSelect').value;
            loadPallets(itemId);
        } else {
            alert('Delete error: ' + data.message);
        }
    })
    .catch(err => alert('Network error: ' + err));
}

function saveNewItem() {
    const name = document.getElementById('newItemName').value.trim();
    const unit = document.getElementById('newItemUnit').value.trim() || 'pcs';
    const reorder = document.getElementById('newItemReorder').value || 0;

    if (!name) { showMsg('Item name likhein', 'error'); return; }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('unit', unit);
    formData.append('reorder_level', reorder);

    fetch('ajax/add_item.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showMsg('Item add ho gaya', 'success');
                document.getElementById('newItemBox').style.display = 'none';
                loadItems();
            } else {
                showMsg(res.message, 'error');
            }
        });
}

document.getElementById('stockInForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const itemId = document.getElementById('itemSelect').value;
    if (!itemId || itemId === '__new__') {
        showMsg('Pehle item select karein', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('fresh_qty', document.getElementById('freshQty').value || 0);
    formData.append('damaged_qty', document.getElementById('damagedQty').value || 0);
    formData.append('ref_no', document.getElementById('refNo').value);
    formData.append('entry_date', document.getElementById('entryDate').value);

    fetch('ajax/save_stock_in.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            showMsg(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                document.getElementById('stockInForm').reset();
                document.getElementById('entryDate').value = new Date().toISOString().split('T')[0];
                document.getElementById('palletSection').style.display = 'none';
                document.getElementById('calcBox').style.display = 'none';
            }
        });
});

function showMsg(text, type) {
    const box = document.getElementById('msgBox');
    box.textContent = text;
    box.className = 'msg-box ' + type;
    setTimeout(() => { box.className = 'msg-box'; }, 4000);
}
</script>
</body>
</html>