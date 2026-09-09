<?php
$active_page = 'stockout';
?>
<!DOCTYPE html>
<html lang="ur-PK">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock OUT — Inventory Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app">
    <?php include 'includes/sidebar.php'; ?>

    <main>
        <div class="pagehead">
            <div>
                <h1>Stock OUT</h1>
                <p class="desc">Stock sell to Party</p>
            </div>
        </div>

        <div class="form-wrap" style="grid-template-columns:1fr;max-width:560px;">
            <div class="form-card">
                <div class="msg-box" id="msgBox"></div>

                <div class="field">
                    <label>Party Name</label>
                    <select id="partySelect">
                        <option value="">Loading parties...</option>
                    </select>
                </div>

                <div class="field" id="newPartyBox" style="display:none;">
                    <label>New Party Name</label>
                    <input type="text" id="newPartyName" placeholder="e.g. ABC Traders">
                    <input type="text" id="newPartyPhone" placeholder="Phone (optional)" style="margin-top:10px;">
                    <button type="button" class="btn ghost" style="margin-top:10px;width:100%;" onclick="saveNewParty()">Save New Party</button>
                </div>

                <form id="stockOutForm">
                    <div class="field">
                        <label>Item</label>
                        <select id="itemSelect" required>
                            <option value="">Loading items...</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Condition — Select Stock Type</label>
                        <div class="cond-toggle">
                            <button type="button" class="fresh-on" data-value="FRESH" onclick="selectCondition(this)">Fresh</button>
                            <button type="button" data-value="DAMAGED" onclick="selectCondition(this)">Damaged</button>
                        </div>
                        <input type="hidden" id="conditionValue" value="FRESH">
                    </div>

                    <div class="field">
                        <label>Quantity</label>
                        <input type="number" id="qty" min="1" placeholder="e.g. 10" oninput="calcPlateOut()">
                    </div>

                    <!-- PLATE CALC BOX -->
                    <div id="plateCalcBox" style="display:none; background:#FAF8F3; border:1px dashed var(--amber); padding:12px; border-radius:8px; margin-bottom:15px;">
                        <div style="font-size:14px; font-weight:600; margin-bottom:8px;">📊 Plate Calculation</div>
                        <div style="font-size:13px; margin-bottom:6px;">
                            Pcs per Plate: <b id="pcsPerPlateLabel">—</b>
                        </div>
                        <div style="font-size:16px; font-weight:700; color:var(--navy);">
                            Plate Count: <span id="plateCountOut">0</span>
                            <span style="font-size:12px; font-weight:400; color:var(--muted);">
                                (baqi: <span id="remainingPcsOut">0</span> pcs)
                            </span>
                        </div>
                    </div>

                    <div class="field">
                        <label>Reference / Invoice No</label>
                        <input type="text" id="refNo" placeholder="e.g. INV-201">
                    </div>

                    <div class="field">
                        <label>Date</label>
                        <input type="date" id="entryDate">
                    </div>

                    <button type="submit" class="btn amber" style="width:100%;margin-top:14px;">Save Sale</button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
let selectedPcsPerPlate = 36;

document.addEventListener('DOMContentLoaded', () => {
    loadParties();
    loadItems();
    document.getElementById('entryDate').value = new Date().toISOString().split('T')[0];
});

function loadParties() {
    fetch('ajax/get_parties.php')
        .then(res => res.json())
        .then(res => {
            const select = document.getElementById('partySelect');
            select.innerHTML = '<option value="">-- Select Party --</option>';
            res.data.forEach(p => {
                select.innerHTML += `<option value="${p.id}">${p.name}</option>`;
            });
            select.innerHTML += `<option value="__new__">+ New Party</option>`;
        });
}

document.getElementById('partySelect').addEventListener('change', function () {
    const val = this.value;
    document.getElementById('newPartyBox').style.display = (val === '__new__') ? 'block' : 'none';
});

function saveNewParty() {
    const name = document.getElementById('newPartyName').value.trim();
    const phone = document.getElementById('newPartyPhone').value.trim();
    if (!name) { showMsg('Party name likhein', 'error'); return; }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('phone', phone);

    fetch('ajax/add_party.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showMsg('Party add ho gayi', 'success');
                document.getElementById('newPartyBox').style.display = 'none';
                loadParties();
            } else {
                showMsg(res.message, 'error');
            }
        });
}

function loadItems() {
    fetch('ajax/get_items.php')
        .then(res => res.json())
        .then(res => {
            const select = document.getElementById('itemSelect');
            select.innerHTML = '<option value="">-- Select Item --</option>';
            res.data.forEach(item => {
                select.innerHTML += `<option value="${item.id}" data-pps="${item.pcs_per_plate}">${item.name}</option>`;
            });
        });
}

document.getElementById('itemSelect').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    selectedPcsPerPlate = parseInt(opt.getAttribute('data-pps')) || 36;
    document.getElementById('pcsPerPlateLabel').textContent = selectedPcsPerPlate;
    calcPlateOut();
});

function calcPlateOut() {
    const qty = parseInt(document.getElementById('qty').value) || 0;
    const box = document.getElementById('plateCalcBox');

    if (qty > 0 && selectedPcsPerPlate > 0) {
        box.style.display = 'block';
        const plates = Math.floor(qty / selectedPcsPerPlate);
        const remaining = qty % selectedPcsPerPlate;
        document.getElementById('plateCountOut').textContent = plates;
        document.getElementById('remainingPcsOut').textContent = remaining;
    } else {
        box.style.display = 'none';
        document.getElementById('plateCountOut').textContent = '0';
        document.getElementById('remainingPcsOut').textContent = '0';
    }
}

function selectCondition(btn) {
    document.querySelectorAll('.cond-toggle button').forEach(b => b.classList.remove('fresh-on', 'dmg-on'));
    const val = btn.dataset.value;
    btn.classList.add(val === 'FRESH' ? 'fresh-on' : 'dmg-on');
    document.getElementById('conditionValue').value = val;
}

document.getElementById('stockOutForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const partyId = document.getElementById('partySelect').value;
    const itemId = document.getElementById('itemSelect').value;

    if (!partyId || partyId === '__new__') { showMsg('Party select karein', 'error'); return; }
    if (!itemId) { showMsg('Item select karein', 'error'); return; }

    const qty = parseInt(document.getElementById('qty').value) || 0;
    if (qty <= 0) { showMsg('Quantity likhein', 'error'); return; }

    const plates = parseInt(document.getElementById('plateCountOut').textContent) || 0;

    const formData = new FormData();
    formData.append('party_id', partyId);
    formData.append('item_id', itemId);
    formData.append('item_condition', document.getElementById('conditionValue').value);
    formData.append('quantity', qty);
    formData.append('ref_no', document.getElementById('refNo').value);
    formData.append('entry_date', document.getElementById('entryDate').value);
    formData.append('plates', plates);
    formData.append('pcs_per_plate', selectedPcsPerPlate);

    fetch('ajax/save_stock_out.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            showMsg(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                document.getElementById('qty').value = '';
                document.getElementById('refNo').value = '';
                document.getElementById('plateCalcBox').style.display = 'none';
            }
        });
});

function showMsg(text, type) {
    const box = document.getElementById('msgBox');
    box.textContent = text;
    box.className = 'msg-box ' + type;
    setTimeout(() => { box.className = 'msg-box'; }, 4500);
}
</script>
</body>
</html>