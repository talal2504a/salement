<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$active_page = 'pending';
?>
<!DOCTYPE html>
<html lang="ur-PK">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pending Deliveries — Inventory Manager</title>
<!-- CHANGED (2026-09-08): ?v=2 cache-busting add kiya taake browser nayi CSS load kare -->
<link rel="stylesheet" href="assets/style.css?v=2">
</head>
<body>
<div class="app">
    <?php include 'includes/loader.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main>
        <div class="pagehead">
            <div>
                <h1>Pending Deliveries</h1>
                <p class="desc">Bik chuka maal jo abhi godown se uthana baaki hai</p>
            </div>
        </div>

        <div id="pendingContainer">
            <div class="empty-note">Loading...</div>
        </div>
    </main>
</div>

<!-- Deliver Modal -->
<div class="modal-overlay" id="deliverModal">
    <div class="modal-box">
        <h3>Mark Delivery</h3>
        <div class="modal-sub" id="deliverModalSub">Pending qty: —</div>

        <div class="field">
            <label>Quantity Delivered</label>
            <input type="number" id="modalQty" min="1">
        </div>
        <!-- CHANGED (2026-09-08): Time input REMOVE kar diya — sirf date save hoti hai -->
        <div class="field">
            <label>Delivery Date</label>
            <input type="text" id="modalDate" placeholder="e.g. 26-Nov-2026">
        </div>
        <div class="field">
            <label>DC No (Delivery Challan)</label>
            <input type="text" id="modalDcNo" placeholder="e.g. DC-045">
        </div>
        <div class="field">
            <label>Vehicle No</label>
            <input type="text" id="modalVehicleNo" placeholder="e.g. LEA-1234">
        </div>
        <div class="field">
            <label>Note (optional)</label>
            <input type="text" id="modalNotes" placeholder="e.g. Driver Ahmed">
        </div>

        <div class="modal-actions">
            <button class="btn ghost" onclick="closeDeliverModal()">Cancel</button>
            <button class="btn amber" onclick="submitDeliverModal()">Save</button>
        </div>
    </div>
</div>

<!-- UPDATE MODAL: stockout jaisi poori details + database prefill -->
<div class="modal-overlay" id="updateModal">
    <div class="modal-box">
        <h3>Update Order</h3>
        <div class="modal-sub" id="updateModalSub">Order #—</div>
        <div class="msg-box" id="updateMsg"></div>

        <div class="field">
            <label>Party Name</label>
            <select id="updatePartySelect"><option>Loading parties...</option></select>
        </div>

        <div class="field">
            <label>Item</label>
            <select id="updateItemSelect"><option>Loading items...</option></select>
        </div>

        <div class="field">
            <label>Condition</label>
            <div class="cond-toggle">
                <button type="button" class="fresh-on" data-value="FRESH" onclick="selectConditionUpd(this)">Fresh</button>
                <button type="button" data-value="DAMAGED" onclick="selectConditionUpd(this)">Damaged</button>
            </div>
            <input type="hidden" id="updateConditionValue" value="FRESH">
        </div>

        <div class="field">
            <label>Quantity</label>
            <input type="number" id="updateModalQty" min="1" oninput="calcPlateUpd()">
        </div>

        <div id="updatePlateCalcBox" style="display:none; background:#FAF8F3; border:1px dashed var(--amber); padding:12px; border-radius:8px; margin-bottom:15px;">
            <div style="font-size:13px; margin-bottom:6px;">Pcs per Plate: <b id="updatePpsLabel">—</b></div>
            <div style="font-size:16px; font-weight:700; color:var(--navy);">
                Plate Count: <span id="updatePlateCount">0</span>
                <span style="font-size:12px; font-weight:400; color:var(--muted);">(baqi: <span id="updateRemainingPcs">0</span> pcs)</span>
            </div>
        </div>

        <div class="field">
            <label>Reference / Invoice No</label>
            <input type="text" id="updateRefNo" placeholder="e.g. INV-201">
        </div>

        <div class="field">
            <label>Date</label>
            <input type="text" id="updateModalDate" placeholder="e.g. 26-Nov-2026">
        </div>

        <div class="modal-actions">
            <button class="btn ghost" onclick="closeUpdateModal()">Cancel</button>
            <button class="btn amber" onclick="submitUpdateModal()">Save Update</button>
        </div>
    </div>
</div>

<!-- CANCEL MODAL -->
<div class="modal-overlay" id="cancelModal">
    <div class="modal-box">
        <h3>Cancel Order</h3>
        <div class="modal-sub" id="cancelModalSub">Order # —</div>

        <!-- NEW (2026-09-08): Cancel pe stock split inputs -->
        <div class="field">
            <label>Fresh Qty (theek maal)</label>
            <input type="number" id="cancelFreshQty" min="0" placeholder="e.g. 25">
        </div>
        <div class="field">
            <label>Damaged Qty (kharab maal)</label>
            <input type="number" id="cancelDamagedQty" min="0" placeholder="e.g. 5">
        </div>
        <div style="font-size:12px;color:#999;margin-top:-8px;">
            Total wapis: <span id="cancelTotalWapis">0</span> pcs
        </div>

        <div class="modal-actions">
            <button class="btn ghost" onclick="closeCancelModal()">No, Rakho</button>
            <button class="btn amber" onclick="submitCancelModal()">Haan, Cancel Karo</button>
        </div>
    </div>
</div>

<script>
// ============================================================
// PENDING DELIVERIES PAGE - JavaScript (CLEANED VERSION)
// ============================================================

let deliverModalOrderId = null;

// Pakistani Date/Time Format helpers (sirf display ke liye)
function formatDatePK(d) {
    if (!d) return '';
    const parts = d.split('-'); // YYYY-MM-DD
    if (parts.length !== 3) return d;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const day = parseInt(parts[2], 10);
    const monthName = months[parseInt(parts[1], 10) - 1];
    const shortYear = parts[0].slice(2);
    return `${day} ${monthName}-${shortYear}`;
}

function formatTimePK(t) {
    if (!t) return '';
    const parts = t.split(':');
    if (parts.length < 2) return t;
    let h = parseInt(parts[0], 10);
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${parts[1]} ${ampm}`;
}

// Aaj ki date input format mein: "08-Sep-2026"
function todayPKInput() {
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const now = new Date();
    return String(now.getDate()).padStart(2, '0') + '-' + m[now.getMonth()] + '-' + now.getFullYear();
}

// DB format "2026-11-26" → "26-Nov-2026" (update modal prefill ke liye)
function dateISOToPK(iso) {
    if (!iso) return todayPKInput();
    const parts = iso.split('-');
    if (parts.length !== 3) return iso;
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return String(parseInt(parts[2], 10)).padStart(2, '0') + '-' + m[parseInt(parts[1], 10) - 1] + '-' + parts[0];
}

// "26-Nov-2026" → DB format "2026-11-26"
function datePKToISO(str) {
    if (!str) return null;
    const m = str.trim().match(/^(\d{1,2})[-\/ ]([A-Za-z]{3,})[-\/ ](\d{2,4})$/);
    if (!m) return null;
    if (str.indexOf('-') > -1 && !/[A-Za-z]{3}/.test(str)) return null; // "2026-11-26" jaisa raw ISO ley nahi (neche convert hota hai)
    const months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
    const mi = months.indexOf(m[2].toLowerCase().slice(0, 3));
    if (mi === -1) return null;
    let year = parseInt(m[3], 10);
    if (year < 100) year += 2000;
    const day = parseInt(m[1], 10);
    if (day < 1 || day > 31) return null;
    return year + '-' + String(mi + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
}


document.addEventListener('DOMContentLoaded', loadPending);

// ---------- PENDING LIST ----------
function loadPending() {
    fetch('ajax/get_pending_orders.php')
        .then(res => res.json())
        .then(res => {
            const container = document.getElementById('pendingContainer');
            container.innerHTML = '';

            if (!res.success || res.data.length === 0) {
                container.innerHTML = '<div class="empty-note">Koi pending delivery nahi hai — sab maal uthaya ja chuka hai.</div>';
                return;
            }

            window.__orderData = {};
            res.data.forEach(party => {
                let ordersHtml = '';
                party.orders.forEach(o => {
                    window.__orderData[o.order_id] = o;
                    const percent = Math.round((o.dispatched_qty / o.booked_qty) * 100);
                    ordersHtml += `
                        <div class="pd-line">
                            <div>
                                <div class="l-name">${o.item_name} (${o.item_condition})</div>
                                <div class="l-order">Order #${o.order_id} · Booked ${formatDatePK(o.order_date)}
                                    ${o.dispatched_qty > 0 ? `· <button class="link-btn" onclick="toggleHistory(${o.order_id})">History</button>` : ''}
                                </div>
                                <div class="history-box" id="history-${o.order_id}"></div>
                                <div class="l-order" style="color:#B8860B;">${o.plates} plates · ${o.pcs_per_plate} pcs/plate</div>
                            </div>
                            <div class="progress-wrap">
                                <div class="progress-bar"><div class="fill" style="width:${percent}%;"></div></div>
                                <div class="progress-txt">${o.dispatched_qty} of ${o.booked_qty} delivered</div>
                            </div>
                            <div class="actions">
                                <span class="tag pending">${o.pending_qty} Pending</span>
                                <button class="btn-update" onclick="updateOrder(${o.order_id})">Update</button>
                                ${o.status === 'PENDING' ? `<button class="btn-cancel" onclick="cancelOrder(${o.order_id})">Cancel</button>` : ''}
                                <button onclick="deliverOrder(${o.order_id}, ${o.pending_qty})">Deliver</button>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML += `
                    <div class="pd-card">
                        <div class="head">
                            <div class="party">${party.party_name}</div>
                            <div class="count">${party.orders.length} item(s) pending</div>
                        </div>
                        <div class="body">${ordersHtml}</div>
                    </div>
                `;
            });
        });
}

// ---------- DELIVER ----------
function deliverOrder(orderId, pendingQty) {
    deliverModalOrderId = orderId;
    document.getElementById('deliverModalSub').textContent = `Pending qty: ${pendingQty}`;
    document.getElementById('modalQty').value = pendingQty;
    document.getElementById('modalDate').value = todayPKInput();
    document.getElementById('modalDcNo').value = '';
    document.getElementById('modalVehicleNo').value = '';
    document.getElementById('modalNotes').value = '';
    document.getElementById('deliverModal').classList.add('open');
}

function closeDeliverModal() {
    document.getElementById('deliverModal').classList.remove('open');
    deliverModalOrderId = null;
}

function submitDeliverModal() {
    const qty = parseInt(document.getElementById('modalQty').value) || 0;
    if (qty <= 0) { alert('Sahi quantity likhein'); return; }
    const d = datePKToISO(document.getElementById('modalDate').value);
    if (!d) { alert('Date sahi likhein — format: 26-Nov-2026'); return; }

    const formData = new FormData();
    formData.append('order_id', deliverModalOrderId);
    formData.append('qty', qty);
    formData.append('delivery_date', d);
    formData.append('dc_no', document.getElementById('modalDcNo').value);
    formData.append('vehicle_no', document.getElementById('modalVehicleNo').value);
    formData.append('notes', document.getElementById('modalNotes').value);

    fetch('ajax/deliver_pending.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            alert(res.message);
            if (res.success) {
                closeDeliverModal();
                loadPending();
            }
        });
}

// ---------- UPDATE ----------
let updateModalOrderId = null;
let updateSelectedPps = 36;

function updateOrder(orderId) {
    const o = (window.__orderData || {})[orderId];
    if (!o) { alert('Order data nahi mila'); return; }

    updateModalOrderId = orderId;
    updateSelectedPps = o.pcs_per_plate || 36;

    document.getElementById('updateModalSub').textContent =
        `Order #${o.order_id} · ${o.party_name} · ${o.item_name}`;
    document.getElementById('updateMsg').className = 'msg-box';
    document.getElementById('updateMsg').textContent = '';

    document.getElementById('updateModalQty').value = o.booked_qty;
    document.getElementById('updateRefNo').value = o.ref_no || '';
    document.getElementById('updateModalDate').value = dateISOToPK(o.order_date);

    loadPartiesUpd(function () {
        const ps = document.getElementById('updatePartySelect');
        Array.from(ps.options).forEach(opt => { if (parseInt(opt.value) === o.party_id) ps.value = opt.value; });
    });
    loadItemsUpd(function () {
        const is = document.getElementById('updateItemSelect');
        Array.from(is.options).forEach(opt => { if (parseInt(opt.value) === o.item_id) is.value = opt.value; });
        updateSelectedPps = o.pcs_per_plate || 36;
        document.getElementById('updatePpsLabel').textContent = updateSelectedPps;
        document.getElementById('updateModalQty').value = o.booked_qty;
        calcPlateUpd();
    });

    const cond = (o.item_condition || 'FRESH').toUpperCase();
    document.getElementById('updateConditionValue').value = cond;
    document.querySelectorAll('#updateModal .cond-toggle button').forEach(b => {
        b.classList.remove('fresh-on', 'dmg-on');
        if (b.dataset.value === cond) b.classList.add(cond === 'FRESH' ? 'fresh-on' : 'dmg-on');
    });

    document.getElementById('updateModal').classList.add('open');
}

function closeUpdateModal() {
    document.getElementById('updateModal').classList.remove('open');
    updateModalOrderId = null;
}

function loadPartiesUpd(cb) {
    fetch('ajax/get_parties.php')
        .then(res => res.json())
        .then(res => {
            const sel = document.getElementById('updatePartySelect');
            sel.innerHTML = '';
            res.data.forEach(p => {
                sel.innerHTML += `<option value="${p.id}">${p.name}</option>`;
            });
            if (cb) cb();
        });
}

function loadItemsUpd(cb) {
    fetch('ajax/get_items.php')
        .then(res => res.json())
        .then(res => {
            const sel = document.getElementById('updateItemSelect');
            sel.innerHTML = '<option value="">-- Select Item --</option>';
            res.data.forEach(item => {
                sel.innerHTML += `<option value="${item.id}" data-pps="${item.pcs_per_plate}">${item.name}</option>`;
            });
            if (cb) cb();
        });
}

document.getElementById('updateItemSelect').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    updateSelectedPps = parseInt(opt.getAttribute('data-pps')) || 36;
    document.getElementById('updatePpsLabel').textContent = updateSelectedPps;
    calcPlateUpd();
});

function selectConditionUpd(btn) {
    document.querySelectorAll('#updateModal .cond-toggle button').forEach(b => b.classList.remove('fresh-on', 'dmg-on'));
    const val = btn.dataset.value;
    btn.classList.add(val === 'FRESH' ? 'fresh-on' : 'dmg-on');
    document.getElementById('updateConditionValue').value = val;
}

function calcPlateUpd() {
    const qty = parseInt(document.getElementById('updateModalQty').value) || 0;
    const box = document.getElementById('updatePlateCalcBox');
    if (qty > 0 && updateSelectedPps > 0) {
        box.style.display = 'block';
        document.getElementById('updatePlateCount').textContent = Math.floor(qty / updateSelectedPps);
        document.getElementById('updateRemainingPcs').textContent = qty % updateSelectedPps;
    } else {
        box.style.display = 'none';
    }
}

function submitUpdateModal() {
    const newQty = parseInt(document.getElementById('updateModalQty').value) || 0;
    const partyId = document.getElementById('updatePartySelect').value;
    const itemId = document.getElementById('updateItemSelect').value;
    if (newQty <= 0) { alert('Sahi quantity likhein'); return; }
    if (!partyId) { alert('Party select karein'); return; }
    if (!itemId) { alert('Item select karein'); return; }

    const plates = parseInt(document.getElementById('updatePlateCount').textContent) || 0;
    const d = datePKToISO(document.getElementById('updateModalDate').value);
    if (!d) { alert('Date sahi likhein — format: 26-Nov-2026'); return; }

    const formData = new FormData();
    formData.append('order_id', updateModalOrderId);
    formData.append('party_id', partyId);
    formData.append('item_id', itemId);
    formData.append('item_condition', document.getElementById('updateConditionValue').value);
    formData.append('booked_qty', newQty);
    formData.append('plates', plates);
    formData.append('pcs_per_plate', updateSelectedPps);
    formData.append('ref_no', document.getElementById('updateRefNo').value);
    formData.append('order_date', d);

    fetch('ajax/update_order.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                closeUpdateModal();
                loadPending();
                alert(res.message);
            } else {
                document.getElementById('updateMsg').textContent = res.message;
                document.getElementById('updateMsg').className = 'msg-box error';
            }
        });
}

// ---------- CANCEL ----------
let cancelModalOrderId = null;

function cancelOrder(orderId) {
    cancelModalOrderId = orderId;
    document.getElementById('cancelModalSub').textContent = `Order #${orderId}`;
    document.getElementById('cancelFreshQty').value = '';
    document.getElementById('cancelDamagedQty').value = '';
    document.getElementById('cancelTotalWapis').textContent = '0';
    document.getElementById('cancelModal').classList.add('open');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('open');
    cancelModalOrderId = null;
}

document.addEventListener('DOMContentLoaded', () => {
    const freshInput = document.getElementById('cancelFreshQty');
    const damagedInput = document.getElementById('cancelDamagedQty');
    const totalSpan = document.getElementById('cancelTotalWapis');
    if (freshInput && damagedInput) {
        const calcTotal = () => {
            const f = parseInt(freshInput.value) || 0;
            const d = parseInt(damagedInput.value) || 0;
            totalSpan.textContent = f + d;
        };
        freshInput.addEventListener('input', calcTotal);
        damagedInput.addEventListener('input', calcTotal);
    }
});

function submitCancelModal() {
    const freshQty = parseInt(document.getElementById('cancelFreshQty').value) || 0;
    const damagedQty = parseInt(document.getElementById('cancelDamagedQty').value) || 0;
    if (freshQty + damagedQty <= 0) {
        alert('Fresh ya Damaged qty likho (total 0 nahi ho sakta)');
        return;
    }

    const formData = new FormData();
    formData.append('order_id', cancelModalOrderId);
    formData.append('fresh_qty', freshQty);
    formData.append('damaged_qty', damagedQty);

    fetch('ajax/cancel_order.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            alert(res.message);
            if (res.success) {
                closeCancelModal();
                loadPending();
            }
        });
}

// ---------- HISTORY ----------
function toggleHistory(orderId) {
    const box = document.getElementById('history-' + orderId);
    const isOpen = box.classList.contains('open');

    if (isOpen) {
        box.classList.remove('open');
        return;
    }

    box.innerHTML = 'Loading...';
    box.classList.add('open');

    fetch('ajax/get_delivery_log.php?order_id=' + orderId)
        .then(res => res.json())
        .then(res => {
            if (!res.success || res.data.length === 0) {
                box.innerHTML = 'Koi delivery history nahi mili.';
                return;
            }
            box.innerHTML = res.data.map(d => `
                <div class="history-row">
                    <div>
                        <strong>${d.qty_delivered} pcs</strong> — ${d.dc_no || 'no DC#'}
                        ${d.vehicle_no ? ' · ' + d.vehicle_no : ''}
                        ${d.notes ? ' · ' + d.notes : ''}
                    </div>
                    <div class="h-meta">${formatDatePK(d.delivery_date)}</div>
                </div>
            `).join('');
        });
}
</script>
</body>
</html>