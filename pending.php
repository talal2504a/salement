<?php
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

<!-- ============================================================
     NEW (2026-09-08): UPDATE MODAL — same design as Deliver Modal
     Pehle Update button prompt() se qty leta tha, ab modal se leta hai.
     Sirf Booked Qty field hai (Update sirf qty change karta hai,
     stock_out adjust backend update_order.php mein hota hai)
     ============================================================ -->
<div class="modal-overlay" id="updateModal">
    <div class="modal-box">
        <h3>Update Order</h3>
        <div class="modal-sub" id="updateModalSub">Booked qty: —</div>

        <div class="field">
            <label>Booked Quantity</label>
            <input type="number" id="updateModalQty" min="1">
        </div>

        <div class="modal-actions">
            <button class="btn ghost" onclick="closeUpdateModal()">Cancel</button>
            <button class="btn amber" onclick="submitUpdateModal()">Save</button>
        </div>
    </div>
</div>

<!-- ============================================================
     NEW (2026-09-08): CANCEL MODAL — same design as Deliver/Update Modal
     Pehle Cancel button confirm() (browser popup) se cancel hota tha
     Ab same-design modal popup se cancel hoga
     ============================================================ -->
<div class="modal-overlay" id="cancelModal">
    <div class="modal-box">
        <h3>Cancel Order</h3>
        <div class="modal-sub" id="cancelModalSub">Order # —</div>

        <!-- NEW (2026-09-08): Cancel pe stock split inputs -->
        <!-- Customer ne mana kiya toh stock wapis aata hai - fresh/damaged mein -->
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
// PENDING DELIVERIES PAGE - JavaScript
// Changes made on 2026-09-08:
//   Line 66: 'ajax/get_pending_orders.php' → 'get_pending_orders.php'
//   Line 144: 'ajax/deliver_pending.php' → 'deliver_pending.php'
//   Line 169: 'ajax/get_delivery_log.php' → 'get_delivery_log.php'
// NEW (2026-09-08): Update + Cancel buttons add kiye
//   - Line ~197: Update/Cancel buttons HTML mein add kiye
//   - Line ~284: updateOrder() — MODAL kholta hai (prompt hat gaya)
//   - Line ~347: cancelOrder() — MODAL kholta hai (confirm hat gaya)
//   - Line ~94:  CANCEL MODAL HTML (same design as Deliver/Update modal)
// ============================================================

let deliverModalOrderId = null;

// ============================================================
// NEW (2026-09-08): Pakistani Date/Time Format helper functions
// Date "2026-08-26" → "26 Aug-26" (month ka naam, number nahi)
// Time "14:30:00"  → "2:30 PM" (12-hour format)
// Sirf DISPLAY ke liye - database mein ISO format hi rehta hai
// ============================================================
function formatDatePK(d) {
    if (!d) return '';
    const parts = d.split('-'); // YYYY-MM-DD
    if (parts.length !== 3) return d; // format alag hai toh waisa hi wapas
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const day = parseInt(parts[2], 10);   // leading zero hatao: 05 → 5
    const monthName = months[parseInt(parts[1], 10) - 1];
    const shortYear = parts[0].slice(2);  // 2026 → 26
    return `${day} ${monthName}-${shortYear}`;
}

function formatTimePK(t) {
    if (!t) return '';
    const parts = t.split(':'); // HH:MM:SS ya HH:MM
    if (parts.length < 2) return t;
    let h = parseInt(parts[0], 10);
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12; // 0 → 12, 13 → 1
    return `${h}:${parts[1]} ${ampm}`;
}

// ============================================================
// NEW (2026-09-08): Date INPUT helpers — month name wala format
// ============================================================
// Aaj ki date input format mein: "08-Sep-2026" (modal pre-fill ke liye)
function todayPKInput() {
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const now = new Date();
    return String(now.getDate()).padStart(2, '0') + '-' + m[now.getMonth()] + '-' + now.getFullYear();
}

// User ka likha "26-Nov-2026" (ya "26-nov-26" / "26/Nov/2026") → DB format "2026-11-26"
// Ghalat format ho toh null return karta hai (validation ke liye)
function datePKToISO(str) {
    if (!str) return null;
    const m = str.trim().match(/^(\d{1,2})[-\/ ]([A-Za-z]{3,})[-\/ ](\d{2,4})$/);
    if (!m) return null;
    const months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
    const mi = months.indexOf(m[2].toLowerCase().slice(0, 3));
    if (mi === -1) return null;
    let year = parseInt(m[3], 10);
    if (year < 100) year += 2000; // "26" → 2026
    const day = parseInt(m[1], 10);
    if (day < 1 || day > 31) return null;
    return year + '-' + String(mi + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
}


document.addEventListener('DOMContentLoaded', loadPending);

function loadPending() {
    // CHANGED: 'ajax/get_pending_orders.php' → 'get_pending_orders.php'
    // Kyunki ajax/ subfolder exist nahi karta, file root mein hai
    fetch('ajax/get_pending_orders.php')
        .then(res => res.json())
        .then(res => {
            const container = document.getElementById('pendingContainer');
            container.innerHTML = '';

            if (!res.success || res.data.length === 0) {
                container.innerHTML = '<div class="empty-note">Koi pending delivery nahi hai — sab maal uthaya ja chuka hai.</div>';
                return;
            }

            res.data.forEach(party => {
                let ordersHtml = '';
                party.orders.forEach(o => {
                    const percent = Math.round((o.dispatched_qty / o.booked_qty) * 100);
                    ordersHtml += `
                        <div class="pd-line">
                            <div>
                                <div class="l-name">${o.item_name} (${o.item_condition})</div>
                                <!-- CHANGED (2026-09-08): Booked date Pakistani format mein - formatDatePK() -->
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
                                <!-- NEW (2026-09-08): Update aur Cancel buttons add kiye Deliver se pehle -->
                                <!-- Same design: .pd-line button styles use ho rahe hain -->
                                <button class="btn-update" onclick="updateOrder(${o.order_id}, ${o.booked_qty})">Update</button>
                                <!-- NEW (2026-09-08): Cancel button SIRF PENDING orders ke liye -->
                                <!-- PARTIAL/COMPLETED cancel nahi ho sakte (already deliver ho chuka) -->
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

// ---- Deliver button: modal open karo ----
function deliverOrder(orderId, pendingQty) {
    deliverModalOrderId = orderId;
    document.getElementById('deliverModalSub').textContent = `Pending qty: ${pendingQty}`;
    document.getElementById('modalQty').value = pendingQty;
    // NEW (2026-09-08): Modal khulte hi aaj ki date aur current time pre-fill karo
    // User chahe toh change kar sakta hai
    // CHANGED (2026-09-08): ab "08-Sep-2026" format mein pre-fill hota hai (month ka naam)
    // pehle browser format (2026-09-08) jata tha kyunki type="date" tha
    const now = new Date(); // time ke liye zaroori
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

// ---- Modal Save: DC no, vehicle no, note ke sath save ----
function submitDeliverModal() {
    const qty = document.getElementById('modalQty').value;
    if (!qty || qty <= 0) { alert('Quantity likhein'); return; }

    const formData = new FormData();
    formData.append('order_id', deliverModalOrderId);
    formData.append('qty_delivered', qty);
    formData.append('dc_no', document.getElementById('modalDcNo').value);
    formData.append('vehicle_no', document.getElementById('modalVehicleNo').value);
    formData.append('notes', document.getElementById('modalNotes').value);
    // CHANGED (2026-09-08): Time REMOVE — ab sirf date bhejte hain
    // Date "26-Nov-2026" format mein aati hai (text input)
    // datePKToISO() use DB format (2026-11-26) mein convert karta hai
    const d = datePKToISO(document.getElementById('modalDate').value);
    if (!d) { alert('Date sahi likhein — format: 26-Nov-2026'); return; }
    formData.append('delivery_date', d);

    // CHANGED: 'ajax/deliver_pending.php' → 'deliver_pending.php'
    // Kyunki ajax/ subfolder exist nahi karta, file root mein hai
    fetch('ajax/deliver_pending.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                closeDeliverModal();
                loadPending();
            } else {
                alert(res.message);
            }
        });
}

// ============================================================
// NEW (2026-09-08): Update Modal — order id yahan store hoti hai
// (Deliver modal ke deliverModalOrderId jaisa hi pattern)
// CHANGED: prompt() hataya, ab MODAL use hota hai (same design as Deliver)
// ============================================================
let updateModalOrderId = null;

// CHANGED (2026-09-08): updateOrder ab prompt() ki jagah MODAL kholta hai
// (same design as Deliver modal — user ne yehi design manga tha)
// Modal khulne pe current booked qty pre-filled hoti hai
function updateOrder(orderId, currentQty) {
    updateModalOrderId = orderId;
    document.getElementById('updateModalSub').textContent = `Booked qty: ${currentQty}`;
    document.getElementById('updateModalQty').value = currentQty;
    document.getElementById('updateModal').classList.add('open');
}

function closeUpdateModal() {
    document.getElementById('updateModal').classList.remove('open');
    updateModalOrderId = null;
}

// Modal Save button: nayi qty lo aur update_order.php ko bhejo
// Backend (update_order.php) khud stock_out adjust karta hai:
//   - qty barhao  → available check karke extra kata jata hai
//   - qty ghatao  → farq stock wapis restore hota hai
function submitUpdateModal() {
    const newQty = document.getElementById('updateModalQty').value;

    // Validation — pehle frontend pe (backend pe bhi hai)
    if (!newQty || newQty <= 0) { alert('Sahi quantity likhein'); return; }

    const formData = new FormData();
    formData.append('order_id', updateModalOrderId);
    formData.append('booked_qty', newQty);

    fetch('ajax/update_order.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            alert(res.message);
            if (res.success) {
                closeUpdateModal();
                loadPending(); // List refresh karo
            }
        });
}

// ============================================================
// NEW (2026-09-08): Cancel Modal — order id yahan store hoti hai
let cancelModalOrderId = null;

// CHANGED (2026-09-08): cancelOrder ab confirm() ki jagah MODAL kholta hai
// (same design as Deliver/Update modal — browser popup hat gaya)
function cancelOrder(orderId) {
    cancelModalOrderId = orderId;
    document.getElementById('cancelModalSub').textContent = `Order #${orderId}`;
    // NEW (2026-09-08): Reset qty inputs when modal opens
    document.getElementById('cancelFreshQty').value = '';
    document.getElementById('cancelDamagedQty').value = '';
    document.getElementById('cancelTotalWapis').textContent = '0';
    document.getElementById('cancelModal').classList.add('open');
}

function closeCancelModal() {
    // FIXED (2026-09-08): 'remove('open')' → 'classList.remove('open')'
    // Pehle .remove() poori modal div DOM se hata deta tha (element.remove()),
    // isliye modal band hone ke baad dobara Cancel kaam nahi karta tha.
    // Ab sirf 'open' class hatati hai — modal DOM mein rehta hai, sirf chhup jata hai.
    document.getElementById('cancelModal').classList.remove('open');
    cancelModalOrderId = null;
}

// NEW (2026-09-08): Fresh + Damaged total calculate karo (live)
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

// Modal "Haan, Cancel Karo" button: cancel_order.php ko AJAX call
// Backend (cancel_order.php) stock_in mein fresh/damaged insert + order delete
function submitCancelModal() {
    const freshQty = parseInt(document.getElementById('cancelFreshQty').value) || 0;
    const damagedQty = parseInt(document.getElementById('cancelDamagedQty').value) || 0;

    // Validation: total 0 nahi ho sakta
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
                loadPending(); // List refresh karo
            }
        });
}

// ---- History toggle: DC no, vehicle no, date, qty dikhata hai ----
function toggleHistory(orderId) {
    const box = document.getElementById('history-' + orderId);
    const isOpen = box.classList.contains('open');

    if (isOpen) {
        box.classList.remove('open');
        return;
    }

    box.innerHTML = 'Loading...';
    box.classList.add('open');

    // CHANGED: 'ajax/get_delivery_log.php' → 'get_delivery_log.php'
    // Kyunki ajax/ subfolder exist nahi karta, file root mein hai
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
                    <!-- CHANGED (2026-09-08): Date ke saath time bhi dikhata hai ab -->
                    <!-- CHANGED (2026-09-08): Pakistani format - "26 Aug-26 · 2:30 PM" -->
                    <div class="h-meta">${formatDatePK(d.delivery_date)}</div>
                </div>
            `).join('');
        });
}
</script>
</body>
</html>