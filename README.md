# Pending Delivery System — Setup Guide

> 📦 **Project ka DOOSRA half karne wale developer ke liye:** pehle **`HANDOVER.md`** parho —
> usme likha hai kya bana hua hai, kya baki hai, aur backend/pages kaise connect karne hain.

## 🔄 STOCK FLOW LOGIC (2026-09-09 UPDATE) — SABSE IMPORTANT SECTION

> **Yeh logic poore system ka core hai. Pehle yeh samjho, phir kuch aur dekho.**

### 🆕 STOCK KA CONCEPT (IMPORTANT FIX 2026-09-09):

```
stock_in   = ASLI LIVE GODOWN STOCK (jisme se maal nikalta hai, wapas aata hai)
stock_out  = SIRF RECORD/HISTORY (maal nikalne ka proof — double minus nahi hota)

Available Stock = SUM(stock_in.qty)   <-- YAHI FORMULA AB (2026-09-09)
```

- **Stock Out karo** → `stock_in` se qty MINUS hoti hai (maal nikla) + `stock_out` mein record
- **Update barhao** → `stock_in` se aur MINUS + `stock_out` record barhta hai
- **Update ghatao** → `stock_in` mein wapas PLUS + `stock_out` record kamta hai
- **Cancel karo** → `stock_in` mein wapas PLUS (fresh + damaged) + `stock_out` record delete

### 🔄 Stock Flow — Teen Buttons Ka Kaam (POORA FLOW):

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        STOCK FLOW DIAGRAM                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ① Stock Out Form (stockout.php)                                        │
│     → stock_out mein RECORD + stock_in se MINUS (asli stock kam)        │
│     → orders table mein PENDING order BANA (Pending page pe dikhega)    │
│     → Backend: api.php?action=stockout_save                                        │
│                                                                         │
│  ② Deliver Button (pending.php)                                         │
│     → SIRF delivery_log mein trip record banta hai                      │
│     → Stock DOBARA NAHI kata (pehle Stock Out mein kat chuka hai)       │
│     → Backend: api.php?action=deliver                                      │
│                                                                         │
│  ③ Update Button (pending.php)                                          │
│     → stock_out qty ADJUST hoti hai (stock me farq padta hai)           │
│     → Qty BARHAO (100→150) → stock_in se +50 AUR minus                  │
│       (pehle available stock check hota hai)                            │
│     → Qty GHATAO (150→100) → stock_in mein +50 WAPIS (fresh)            │
│     → Backend: api.php?action=update_order                                         │
│                                                                         │
│  ④ Cancel Button (pending.php)                                          │
│     → stock_in mein PLUS: fresh_qty (condition='fresh')                 │
│                        + damaged_qty (condition='damaged')              │
│     → stock_out record DELETE + order DELETE                            │
│     → Backend: api.php?action=cancel_order                                         │
│                                                                         │
│  💰 Available Stock Ka Math (2026-09-09 fix):                           │
│     Total Stock = SUM(stock_in.qty)                                     │
│     (stock_out SIRF record hai — double minus nahi hota)                │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 🗄️ Cancel Pe Database Mein Kya Hota Hai (Step by Step):

```sql
-- Step 1: stock_in mein FRESH wapis (theek maal aaya)
INSERT INTO stock_in (item_id, qty, supplier, in_date, notes, `condition`)
VALUES (?, ?, 'Cancel Return', CURDATE(), 'Order cancel - fresh return', 'fresh');

-- Step 2: stock_in mein DAMAGED wapis (kharab maal aaya)
INSERT INTO stock_in (item_id, qty, supplier, in_date, notes, `condition`)
VALUES (?, ?, 'Cancel Return', CURDATE(), 'Order cancel - damaged return', 'damaged');

-- Step 3: stock_out record DELETE karo
DELETE FROM stock_out WHERE order_id = ?;

-- Step 4: Order DELETE (delivery_log FK CASCADE se khud delete)
DELETE FROM orders WHERE id = ?;
```

### 🔄 Update Pe Stock Adjust Kaise Hota Hai (2026-09-09):

| Qty Change | stock_in (LIVE) | stock_out (RECORD) |
|-----------|-----------------|--------------------|
| 100 → 150 (barhao) | **-50** (aur kata) | **+50** (record barha) |
| 150 → 100 (ghatao) | **+50** (wapis fresh) | **-50** (record kam) |
| → 1000 (zyada) | ❌ BLOCK: "Sirf X pcs available hain" | ❌ |

### 💰 Available Stock Ka Math (SIRF EK - 2026-09-09):

```sqls
Total Stock = SUM(stock_in.qty)
-- stock_out sirf record hai — LIVE stock stock_in mein reflected hota hai
```

### Nayi/Changed Backend Files (LIVE stock_in logic ke saath):

| File | Kya Karta Hai |
|------|---------------|
| `config/db.php` ✏️ | **`stock_in_minus()` helper** — stock_in se FIFO minus karta hai (2026-09-09) |
| `stockout_save.php` 🆕 | Stock out: stock_out RECORD + **stock_in MINUS** + PENDING order (transaction) |
| `get_items.php` 🆕 | Items + **LIVE available** = SUM(stock_in) |
| `get_stock_out_list.php` 🆕 | Recent stock-out records (stockout.php table ke liye) |
| `cancel_order.php` ✏️ | stock_in mein fresh/damaged PLUS + stock_out DELETE + order DELETE |
| `update_order.php` ✏️ | stock_in se minus/plus + stock_out record adjust |
| `stockout.php` ✏️ | Form: Customer + Item + Qty (pehle order select hota tha) |
| `setup_database.php` ✏️ | stock_in/stock_out tables + sample data |
| `reset_database.php` ✏️ | Dono nayi tables drop/create/sample mein add |

### Live Test Results (2026-09-09 — sab pass ✅):

```
1. Stock Out: FlowTest, 20 pcs → stock_in 290 → 270 ✅ (MINUS hua!)
2. Update 20→40 (barhao): stock_in -20 → 250 ✅
3. Update 45→30 (ghatao): stock_in +15 (fresh wapis) ✅
4. Update 30→35 (barhao): stock_in -5 ✅
5. Update 35→30 (ghatao): stock_in +5 ✅
6. Cancel (fresh 25 + damaged 5): stock_in +30 → 290 ✅
7. Cancel validation: fresh+damaged = booked nahi → BLOCK ✅
```

---

## 🗑️ CHANGED: Time Remove Kar Diya (2026-09-08)

> **User ne bola: "time hata do database, backend, frontend se — delivery se, Delivery History se, stockout se bhi"**

### Kya Hataya:

| Jagah | Pehle | Ab |
|-------|-------|-----|
| Deliver modal | Date + Time dono inputs | **Sirf Date** |
| Delivery History page | Date + Time column | **Sirf Date** |
| Stock Out form | Date + Time dono inputs | **Sirf Date** |
| `delivery_log` table | `delivery_time` TIME column | **Column drop** |
| `stock_out` table | `out_time` TIME column | **Column drop** |
| `stock_in` table | `in_time` TIME column | **Column drop** |

### Files Mein Kya Change:

| File | Change |
|------|--------|
| `pending.php` | Modal se Time input + `formatTimePK` call remove · `submitDeliverModal` se `delivery_time` POST remove · history mein time display remove |
| `stockout.php` | Form se Time input remove · `formatTimePK` function remove · `prefillDateTime` se time pre-fill remove · `submitStockOut` se `out_time` remove · table se Time column remove |
| `delivery_history.php` | `formatTimePK` function remove · table header se `<th>Time` remove · colspan 10→9 · row se Time cell remove |
| `deliver_pending.php` | `delivery_time` input + validation + INSERT se remove |
| `stockout_save.php` | `out_time` input + validation + INSERT se remove |
| `get_delivery_log.php` | SELECT se `COALESCE(delivery_time...)` remove |
| `get_delivery_history.php` | SELECT se `COALESCE(delivery_time...)` remove |
| `get_stock_out_list.php` | SELECT se `out_time` remove |
| `setup_database.php` | 3 tables se TIME columns remove · sample data se time values remove · **DROP COLUMN patch** (purani DB mein columns drop hote hain — data safe) |
| `reset_database.php` | Same as setup — TIME columns + sample time values remove |

### Live Test (sab pass ✅):
```
1. Deliver (no time): {"success":true} ✅
2. get_delivery_log: sirf date, koi time field nahi ✅
3. get_delivery_history: sirf date, koi time field nahi ✅
4. Stock Out (no time): {"success":true, "order_id":10} ✅
5. get_stock_out_list: sirf date, koi time field nahi ✅
6. DB patch: 3 columns drop ho gaye (data safe) ✅
```

---

## Project Structure
```
pending/
├── assets/
│   └── style.css           # CSS styles (moved from root to assets/)
│                           # CHANGED 2026-09-08: Update/Cancel button colors add kiye (line ~168)
├── config/
│   └── db.php              # Database connection file (NEW)
├── includes/
│   └── sidebar.php         # Sidebar include (moved from root to includes/)
├── setup_database.php      # Run this first to create database (NEW)
│                           # CHANGED 2026-09-08: orders.status enum mein 'CANCELLED' add kiya (line ~56)
├── reset_database.php      # Database drop karke fresh banata hai (NEW 2026-09-08)
│                           # Duplicate data fix karne ke liye - isse use karo agar data dup ho jaye
├── pending.php             # Main page - shows pending deliveries
│                           # CHANGED 2026-09-08:
│                           #   - Line 66,144,169: 'ajax/...' paths fix kiye
│                           #   - Line ~106: Update + Cancel buttons HTML mein add kiye
│                           #   - Line ~172: updateOrder() function add kiya
│                           #   - Line ~195: cancelOrder() function add kiya
├── deliver_pending.php     # AJAX - marks delivery complete
│                           # CHANGED 2026-09-08: Line ~42 - Cancelled order pe delivery block
├── get_pending_orders.php  # AJAX - fetches pending orders
│                           # CHANGED 2026-09-08: Line 36 - CANCELLED orders bhi hide kiye
├── get_delivery_log.php    # AJAX - fetches delivery history (NEW)
├── update_order.php        # AJAX - order ki booked qty update karta hai (NEW 2026-09-08)
├── cancel_order.php        # AJAX - order cancel karta hai (NEW 2026-09-08)
└── README.md               # This file (NEW)
```

## Naya Feature: Update & Cancel Buttons (2026-09-08)

Har pending order pe ab 3 buttons hain (Deliver se pehle):

| Button | Normal Style | Hover | Kya Karta Hai |
|--------|--------------|-------|---------------|
| **Update** | White bg + black text + black border | `#E8A33D` (amber) bg | Order ki booked quantity change karta hai (prompt se nayi qty leta hai) |
| **Cancel** | White bg + black text + black border | Red (`#D32F2F`) bg | Order cancel karta hai (confirm poochta hai, phir list se hide ho jata hai) |
| **Deliver** | Navy bg + white text (ORIGINAL) | opacity 0.9 | Delivery modal kholta hai (pehle jaisa hi) |

**CSS Style (2026-09-08 v2) — `assets/style.css` lines 168-177:**
```css
.pd-line button.btn-update,
.pd-line button.btn-cancel{
    background:#fff;color:#000;border:2px solid #000;font-weight:600;
}
.pd-line button.btn-update:hover{background:#E8A33D;border-color:#E8A33D;color:#fff;}
.pd-line button.btn-cancel:hover{background:#D32F2F;border-color:#D32F2F;color:#fff;}
.pd-line button:not(.btn-update):not(.btn-cancel){background:var(--navy);color:#fff;border:none;}
```

**Cache-busting (2026-09-08) — `pending.php` line 11:**
```html
<link rel="stylesheet" href="assets/style.css?v=2">
```
Browser purani CSS cache kar leta tha isliye `?v=2` add kiya — ab hamesha nayi CSS load hogi.

---

## 🇵🇰 Naya Feature: Pakistani Date/Time Format (2026-09-08)

Ab dates **Pakistani style** mein dikhengi — month ke number ki jagah **month ka naam**:

| Pehle (ISO) | Ab (Pakistani) |
|-------------|----------------|
| `2026-08-26` | **26 Aug-26** |
| `2026-09-01` | **1 Sep-26** |
| Time: `14:30:00` | **2:30 PM** (12-hour format) |

### Kahan Use Hua:

| Kahan | File | Line (approx) | Kya Hai |
|-------|------|---------------|---------|
| Helper Function | `pending.php` | ~85-110 | `formatDatePK(d)` — "2026-08-26" → "26 Aug-26" · `formatTimePK(t)` — "14:30:00" → "2:30 PM" |
| Booked Date | `pending.php` | ~138 | `Booked ${formatDatePK(o.order_date)}` — har order line mein |
| History Date+Time | `pending.php` | ~300 | `${formatDatePK(d.delivery_date)} · ${formatTimePK(d.delivery_time)}` — history rows mein |

### Logic:
- **`formatDatePK()`**: `YYYY-MM-DD` split karta hai → month number ko month NAME mein convert (Jan/Feb/...) → `26 Aug-26` format
- **`formatTimePK()`**: 24-hour time ko 12-hour (AM/PM) mein convert karta hai
- **Sirf display** ke liye — **database mein ISO format hi save hota hai** (`2026-08-26`), koi DB change nahi
- ~~Modal ke date/time inputs waise hi rahe~~ → **UPDATED (2026-09-08)**: ab modal mein bhi month name format hai — neeche wala section dekho

---

## 📅 CHANGED: Date Input Ab Month Name Format Mein (2026-09-08)

### Problem:
Native `<input type="date">` browser ki apni format dikhata tha (`09/08/2026` — numbers only).
User ko chahiye tha **`26-Nov-2026`** (month ka naam).

### Solution:
Date input `type="date"` se **`type="text"`** bana diya — ab user `26-Nov-2026` likh/dekh sakta hai.

| | Pehle | Ab |
|--|-------|-----|
| Modal mein dikhta tha | `09/08/2026` | **`08-Sep-2026`** (pre-filled) |
| User likh sakta hai | sirf browser picker | `26-Nov-2026`, `8-sep-26`, `01/Jan/2027` — sab chalega |
| Database mein jata hai | `2026-09-08` | `2026-09-08` (**same — DB change nahi**) |

### Kahan Kya Change Hua:

| File | Line (approx) | Change |
|------|---------------|--------|
| `pending.php` | ~47-57 | Modal date input `type="date"` → `type="text"` + placeholder `e.g. 26-Nov-2026` |
| `pending.php` | ~117-140 | **Naye helpers**: `todayPKInput()` (aaj ki date "08-Sep-2026" format mein) + `datePKToISO()` (user format → DB format) |
| `pending.php` | ~210 | Modal pre-fill ab `todayPKInput()` se — "08-Sep-2026" dikhta hai |
| `pending.php` | ~238-242 | Submit pe `datePKToISO()` parse + ghalat format pe alert |
| `stockout.php` | ~56 | Same: date input text banaya |
| `stockout.php` | ~132-154 | Same helpers add kiye |
| `stockout.php` | ~210-213 | Same parsing + validation |

### `datePKToISO()` Logic (dono files mein same):
1. Regex se format check: `day-MonthName-year` (separator: `-`, `/` ya space)
2. Month name (3 letters, case-insensitive) → month number (`nov` → 11)
3. 2-digit year → 2000 add (`26` → `2026`)
4. Day 1-31 validate
5. Return: `YYYY-MM-DD` (DB format) — ghalat input pe `null` → alert dikhata hai

### Tested:
```
26-Nov-2026 → 2026-11-26 ✅
8-sep-26    → 2026-09-08 ✅
01/Jan/2027 → 2027-01-01 ✅
5 DEC 25    → 2025-12-05 ✅
hello       → null (alert) ✅
```

---

## ⏰ NEW: History Mein Time Har Jagah (2026-09-08)

### Kya Change Hua:
Ab **har delivery ke saath time dikhega** — chahe kahan se dekho:

| Jagah | Pehle | Ab |
|-------|-------|-----|
| History box (History button) | `5 Sep-26` | **`5 Sep-26 · 3:59 PM`** |
| Delivery History page | Date sirf numbers mein | **Date Pakistani format + Time column** |
| Stock OUT recent table | ✅ pehle se tha | ✅ (same) |

### Changes (file + line):

| File | Line (approx) | Change |
|------|---------------|--------|
| `get_delivery_log.php` | ~24-28 | Query mein **COALESCE fallback**: `delivery_time` NULL ho (purani entry) toh `created_at` ka time dikhta hai |
| `get_delivery_history.php` | ~21-27 | Same COALESCE fallback |
| `delivery_history.php` | ~37-44 | **Naya Time column** (th + colspan 9→10) |
| `delivery_history.php` | ~51-68 | `formatDatePK()` + `formatTimePK()` helpers add (Pakistani format) |
| `delivery_history.php` | ~94-96 | Table row mein date PK format + time cell |
| `setup_database.php` | ~158-162 | Sample data mein `delivery_time` values add |
| `reset_database.php` | ~133-137 | Same |

### COALESCE Fallback Kya Hai:
```sql
COALESCE(delivery_time, TIME(created_at)) AS delivery_time
```
- **Nayi entries** (Deliver modal / Stock OUT se): user ka diya hua time save hota hai → wahi dikhega
- **Purani entries** (time feature se pehle ki): `delivery_time` NULL thi → automatically `created_at` ka time dikhta hai
- Database mein koi data change NAHI hota — sirf dikhane ka logic hai

### Purani Rows Fix (one-time):
Existing 3 sample rows mein `delivery_time` NULL tha — temp script se
`created_at` ka time bhar diya gaya. Ab fresh data bhi time ke saath aata hai.

---

## 📅 Naya Feature: Date & Time in Deliver Modal (2026-09-08)

Deliver button pe click karne se jo popup khulta hai, usme ab **Delivery Date & Time** section add kiya gaya hai.

### Kya Add Hua:

| Kahan | File | Line (approx) | Kya Hai |
|-------|------|---------------|---------|
| Date & Time inputs (HTML) | `pending.php` | ~41-49 | Modal mein naya section: `<input type="date" id="modalDate">` + `<input type="time" id="modalTime">` |
| Default values (JS) | `pending.php` | ~148-152 | `deliverOrder()` mein — modal khulte hi **aaj ki date + current time** pre-fill hota hai |
| Submit (JS) | `pending.php` | ~175-182 | `submitDeliverModal()` — ab `delivery_date` + `delivery_time` dono POST hote hain, empty pe alert |
| History display (JS) | `pending.php` | ~269-270 | History mein date ke saath **time bhi** dikhta hai (`2026-09-08 · 14:30:00`) |
| Backend (PHP) | `deliver_pending.php` | ~20-31, ~90-95 | `delivery_time` accept + validate karta hai (HH:MM format), `delivery_log` table mein insert |
| Database | `setup_database.php` | ~73, ~105-114 | `delivery_log` table mein **`delivery_time` TIME** column + auto-patch (purani DB pe column add hota hai, data safe) |
| Database | `reset_database.php` | ~78 | Fresh table mein bhi `delivery_time` column |

### Database Change:
```sql
-- delivery_log table mein naya column:
delivery_time TIME DEFAULT NULL
```
- Purani database pe **manually query nahi chalani** — bas `setup_database.php` dobara kholo, yeh khud patch kar dega
- Ya directly yeh query chalao:
```sql
ALTER TABLE delivery_log ADD COLUMN delivery_time TIME DEFAULT NULL AFTER delivery_date;
```

### Kaam Kaise Karta Hai (Flow):
1. **Deliver** button dabao → modal khulta hai
2. Date & Time section mein **aaj ki date + abhi ka time** pehle se bhara hota hai
3. User chahe toh date/time change kar sakta hai (date picker + time picker)
4. Empty chhoda → alert aata hai: "Date select karein" / "Time select karein"
5. **Save** dabao → `deliver_pending.php` ko `delivery_date` + `delivery_time` POST hote hain
6. Backend time format validate karta hai, phir `delivery_log` mein dono save hote hain
7. **History** button pe purani deliveries mein date + time dono dikhte hain

---

## 🔘 BUTTON LOGIC — FULL DETAIL (Kahan Use Hua, Kis File Mein)

### 1️⃣ UPDATE BUTTON

**Kaam:** Order ki booked quantity (booked qty) change karna

| Kahan | File | Line (approx) | Kya Hai |
|-------|------|---------------|---------|
| Button HTML | `pending.php` | ~106 | `<button class="btn-update" onclick="updateOrder(${o.order_id}, ${o.booked_qty})">Update</button>` |
| JS Function | `pending.php` | ~172-193 | `updateOrder(orderId, currentQty)` function |
| Backend AJAX | `update_order.php` | (naya file) | POST request handle karta hai |
| Button Color CSS | `assets/style.css` | ~168-176 | Black bg + white text + black border. Hover: `#E8A33D` (amber) |

**Logic Step-by-Step:**
1. User **Update** button dabata hai → `updateOrder(orderId, currentQty)` call hoti hai
2. Browser prompt khulta hai → purani qty pre-filled dikhti hai
3. User nayi qty likhta hai:
   - `null` (Cancel dabaya) → kuch nahi hota
   - `<= 0 ya empty` → alert: "Sahi quantity likhein"
   - `same as purani` → kuch nahi hota (no update)
4. Nayi qty `FormData` mein daal ke `update_order.php` ko **POST** hoti hai (`order_id` + `booked_qty`)

5. Backend (`update_order.php`) checks:
   - Order exist karta hai? → Nahi: "Order nahi mila"
   - Status COMPLETED hai? → Update block: "Completed order update nahi ho sakta"
   - Nayi qty `dispatched_qty` se kam hai? → Block: "Itna already deliver ho chuka hai"
6. Sab OK → `UPDATE orders SET booked_qty = ?, status = ?` chalta hai
7. Status auto-recalculate hota hai:
   - `dispatched_qty == 0` → **PENDING**
   - `0 < dispatched < booked` → **PARTIAL**
   - `dispatched >= booked` → **COMPLETED**
8. Success pe JS `loadPending()` chalata hai → list refresh

> ### 🆕 CHANGED (2026-09-08): Update Button Ab MODAL Kholta Hai (prompt hat gaya)
> **Pehle:** `prompt('Nayi Booked Quantity likhein:', currentQty)` — browser ka boring prompt
> **Ab:** Same-design **modal popup** jo Deliver button pe khulta hai usi jaisa:
>
> | Kahan | File | Line (approx) | Kya Hai |
> |-------|------|---------------|---------|
> | Modal HTML | `pending.php` | ~74-95 | `<div id="updateModal">` — same classes (`modal-overlay`, `modal-box`, `field`, `modal-actions`) |
> | Modal open | `pending.php` | ~296 | `updateOrder()` — qty pre-filled, sub me "Booked qty: X" |
> | Modal close | `pending.php` | ~303 | `closeUpdateModal()` |
> | Save | `pending.php` | ~312 | `submitUpdateModal()` → `update_order.php` POST → stock adjust → list refresh |
>
> **Design:** Title "Update Order" · sub "Booked qty: 50" · Booked Quantity input (pre-filled) · Cancel + Save buttons (.btn ghost / .btn amber — bilkul Deliver modal jaisa)

---

### 2️⃣ CANCEL BUTTON

**Kaam:** Order cancel karna + **stock WAPIS restore** (stock_out delete)

| Kahan | File | Line (approx) | Kya Hai |
|-------|------|---------------|---------|
| Button HTML | `pending.php` | ~198 | `<button class="btn-cancel" onclick="cancelOrder(${o.order_id})">Cancel</button>` |
| Modal HTML | `pending.php` | ~94-109 | `<div id="cancelModal">` — same classes (modal-overlay, modal-box, modal-actions) |
| JS Function | `pending.php` | ~347 | `cancelOrder()` — ab MODAL kholta hai (confirm hat gaya) |
| Modal Save | `pending.php` | ~361 | `submitCancelModal()` → `cancel_order.php` POST |
| Backend AJAX | `cancel_order.php` | (existing) | POST request handle karta hai |
| Hide Logic | `get_pending_orders.php` | 36 | Order delete hone se khud hi gayab hota hai |
| Button Color CSS | `assets/style.css` | ~168-176 | Black bg + white text + black border. Hover: red (#D32F2F) |

**Logic Step-by-Step:**
1. User **Cancel** button dabata hai → `cancelOrder(orderId)` call hoti hai
2. **Modal popup** khulta hai (browser confirm nahi!): "Cancel Order" + "Order #X" + warning text
3. User "No, Rakho" dabaya → modal band, kuch nahi hota
4. User "Haan, Cancel Karo" dabata hai → `submitCancelModal()` → `cancel_order.php` ko **POST**
5. Backend (`cancel_order.php`) checks:
   - Order exist karta hai? → Nahi: "Order nahi mila"
6. Sab OK → **Transaction start:**
   - **Step 1:** `DELETE FROM stock_out WHERE order_id = ?` → **stock WAPIS restore** (warehouse mein aa gaya)
   - **Step 2:** `DELETE FROM orders WHERE id = ?` → order gayab (delivery_log FK CASCADE se khud delete)
7. Success pe JS `loadPending()` chalata hai → list refresh → order pending list se gayab

**Cancel ke baad stock ka flow:**
```
Total Stock = SUM(stock_in) - SUM(stock_out)
Cancel se: stock_out kam ho gaya → Total Stock BARH gaya ✅ (stock wapis warehouse mein)
```

> **🆕 CHANGED (2026-09-08): Cancel Ab Modal Use Karta Hai**
> **Pehle:** `confirm('Kya aap Order #X cancel karna chahte hain?')` — browser ka popup
> **Ab:** Same-design **modal popup** jo Deliver/Update modal jaisa hai:
>
> | Kahan | File | Line (approx) | Kaya Hai |
> |-------|------|---------------|---------|
> | Modal HTML | `pending.php` | ~94-109 | `<div id="cancelModal">` — same classes (modal-overlay, modal-box) |
> | Modal open | `pending.php` | ~347 | `cancelOrder()` — order id set, modal khulta hai |
> | Modal close | `pending.php` | ~353 | `closeCancelModal()` |
> | Save | `pending.php` | ~361 | `submitCancelModal()` → `cancel_order.php` POST → stock restore → list refresh |
>
> **Design:** Title "Cancel Order" · sub "Order #X" · warning text · "No, Rakho" + "Haan, Cancel Karo" buttons (.btn ghost / .btn amber — bilkul Deliver/Update modal jaisa)
- Order ka data + delivery history database mein safe rehti hai

---

### 3️⃣ DELIVER BUTTON (Original - Pehle Se Tha)

**Kaam:** Order ki pending quantity deliver karna (modal form se)

| Kahan | File | Line (approx) | Kya Hai |
|-------|------|---------------|---------|
| Button HTML | `pending.php` | ~110 | `<button onclick="deliverOrder(${o.order_id}, ${o.pending_qty})">Deliver</button>` |
| JS Function | `pending.php` | ~125+ | `deliverOrder(orderId, pendingQty)` — modal kholta hai |
| Modal Submit | `pending.php` | ~144 | `deliver_pending.php` ko POST |
| Backend AJAX | `deliver_pending.php` | (root file) | Delivery record banata hai |
| History Fetch | `pending.php` | ~169 | `get_delivery_log.php` ko fetch |
| History Backend | `get_delivery_log.php` | (root file) | delivery_log se history deta hai |

**Logic Step-by-Step:**
1. **Deliver** dabao → `deliverOrder(orderId, pendingQty)` → modal khulta hai
2. Modal mein fields: quantity (max = pending), date & time (NEW 2026-09-08), DC no, vehicle no, notes
3. Submit → `deliver_pending.php` POST
4. Backend checks:
   - Order exist? Cancelled? (`CANCELLED` check NEW hai line ~42)
   - Qty pending se zyada? → Block
5. `delivery_log` mein entry insert hoti hai + `orders.dispatched_qty` update
6. `dispatched == booked` hua → status **COMPLETED** → pending list se gayab

---

### Data Flow Diagram (sab buttons ka):
```
pending.php (UI)
    │
    ├── Update button ──→ updateOrder() ──→ update_order.php ──→ orders.booked_qty UPDATE
    ├── Cancel button ──→ cancelOrder()  ──→ cancel_order.php ──→ orders.status = 'CANCELLED'
    └── Deliver button ─→ deliverOrder() ─→ deliver_pending.php → delivery_log INSERT
                                                            └──→ orders.dispatched_qty UPDATE

List refresh: loadPending() ──→ get_pending_orders.php ──→ sirf PENDING/PARTIAL orders
History view: toggleHistory() ─→ get_delivery_log.php ────→ order ki delivery history
```

### Update Button Rules:
- Booked qty delivered se kam nahi ho sakti (jo deliver ho chuka hai)
- Completed order update nahi ho sakta
- Update ke baad status auto-recalculate hota hai (PENDING/PARTIAL/COMPLETED)

### Cancel Button Rules:
- Cancelled order pending list se **hide** ho jata hai (delete nahi hota, history safe)
- Cancelled order pe **delivery nahi ho sakti**
- Completed order cancel nahi ho sakta

### Database Change:
`orders.status` enum: `PENDING, PARTIAL, COMPLETED, CANCELLED`
- Purani database pe yeh query manually chalao:
```sql
ALTER TABLE orders MODIFY status ENUM('PENDING','PARTIAL','COMPLETED','CANCELLED') DEFAULT 'PENDING';
```
- Ya phir `reset_database.php` run karo (fresh database banega)

## Database Info
- **Database Name:** `pendingwithsarim`
- **Tables:** parties, items, orders, delivery_log

## How to Run

### Step 1: Copy to XAMPP
Copy the `pending` folder to `C:\xampp\htdocs\`

### Step 2: Start XAMPP
Start Apache and MySQL from XAMPP Control Panel

### Step 3: Create Database
Open browser and go to:
```
http://localhost/pending/setup_database.php
```
This will create:
- Database `pendingwithsarim`
- 4 tables (parties, items, orders, delivery_log)
- Sample data for testing

### Step 4: Open Project
```
http://localhost/pending/pending.php
```

## Changes Made (2026-09-08)

### Files Created
| File | Purpose |
|------|---------|
| `config/db.php` | Database connection file |
| `get_delivery_log.php` | AJAX endpoint for delivery history |
| `setup_database.php` | One-time setup - creates DB + tables + sample data |
| `README.md` | Documentation |

### Files Modified
| File | Change |
|------|--------|
| `get_pending_orders.php` | Line 3: `../config/db.php` → `config/db.php` |
| `deliver_pending.php` | Line 3: `../config/db.php` → `config/db.php` |
| `pending.php` | Line 66: `ajax/get_pending_orders.php` → `get_pending_orders.php` |
| `pending.php` | Line 144: `ajax/deliver_pending.php` → `deliver_pending.php` |
| `pending.php` | Line 169: `ajax/get_delivery_log.php` → `get_delivery_log.php` |

### Folders Created
| Folder | Purpose |
|--------|---------|
| `assets/` | Contains style.css |
| `config/` | Contains db.php |
| `includes/` | Contains sidebar.php |

## Database Tables

### parties
| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto increment |
| name | VARCHAR(255) | Party name |
| phone | VARCHAR(50) | Contact number |
| address | TEXT | Address |

### items
| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto increment |
| name | VARCHAR(255) | Item name |
| category | VARCHAR(100) | Category |
| unit_price | DECIMAL(10,2) | Price per unit |

### orders
| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto increment |
| party_id | INT (FK) | Reference to parties |
| item_id | INT (FK) | Reference to items |
| booked_qty | INT | Total quantity booked |
| dispatched_qty | INT | Quantity dispatched so far |
| status | ENUM | PENDING/PARTIAL/COMPLETED |
| ref_no | VARCHAR(100) | Order reference |
| order_date | DATE | Order date |
| item_condition | VARCHAR(50) | Fresh/etc |

### delivery_log
| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Auto increment |
| order_id | INT (FK) | Reference to orders |
| qty_delivered | INT | Quantity delivered |
| dc_no | VARCHAR(100) | Delivery Challan number |
| vehicle_no | VARCHAR(50) | Vehicle number |
| notes | TEXT | Additional notes |
| delivery_date | DATE | Delivery date |
| delivery_time | TIME | Delivery time (NEW 2026-09-08) |

## Files Description

### Frontend (Pages + UI)
| File | Purpose |
|------|---------|
| `pending.php` | Main page — pending orders, Deliver/Update/Cancel modals |
| `stockout.php` | Stock Out form + Stock Out list table |
| `delivery_history.php` | All deliveries history page |
| `assets/style.css` | All styling |
| `includes/sidebar.php` | Sidebar navigation |

### Backend (Single API Router)
| File | Purpose |
|------|---------|
| `api.php` | **Single router file** — all 9 actions (GET/POST) |
| `config/db.php` | DB connection + `stock_in_minus()` FIFO helper |
| `setup_database.php` | One-time setup — creates DB + tables + sample data |
| `reset_database.php` | Reset all data (for testing) |

### API Actions (`api.php?action=...`)
| Action | Method | Purpose |
|--------|--------|---------|
| `get_pending_orders` | GET | Pending orders grouped by party |
| `deliver` | POST | Save delivery entry (no stock change) |
| `update_order` | POST | Adjust booked qty (stock_in minus/plus) |
| `cancel_order` | POST | Cancel PENDING order (stock_in wapis) |
| `stockout_save` | POST | Stock Out form (stock_in minus + PENDING order) |
| `get_items` | GET | Items list + live available stock |
| `get_delivery_log` | GET | Single order ki delivery entries |
| `get_delivery_history` | GET | Saari deliveries (JOIN parties/items) |
| `get_stock_out_list` | GET | Stock Out records |

## Security Note
Delete `setup_database.php` after running it once!
