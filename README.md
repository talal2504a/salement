# Inventory Management System — Solar Stock Control

Full-stack inventory app: stock in/out, pallet management, pending deliveries, sales, activity log, documents export (Excel/Word), and user authentication.

**Database:** `pendingwithsarim` | **Stack:** PHP (native), MySQL, vanilla JS + CSS

---

## 🚀 Quick Start

1. Copy folder to `C:\xampp\htdocs\pending\`
2. Start Apache + MySQL from XAMPP
3. (Optional) Run `http://localhost/pending/setup_database.php` — create/seed DB
4. Open `http://localhost/pending/login.php`

**Default login:**
```
Email:    admin@gmail.com
Password: admin123
```

> ⚠️ **Security note:** `login.php` se login hone ke baad app explore karo. Deleted the `setup_database.php`\`reset_database.php` from live server. Register page khul raha hai — doosre users khud register ho sakte hain.

---

## 🧭 Pages / Flow

```
login.php ──→ dashboard.php ──→ (sidebar)
                    │
                    ├── stockin.php          Stock IN (fresh/damaged + pallets)
                    ├── stockout.php         Stock OUT (sale → creates PENDING order)
                    ├── item_ledger.php      Per-item in/out balance
                    ├── pending.php          Pending deliveries (Deliver/Update/Cancel)
                    ├── delivery_history.php All completed deliveries
                    ├── activity_log.php     Every action record (filterable)
                    └── documents.php        Export any list → Excel / Word
```

### Core Stock Flow

```
Available Stock = SUM(stock_in.qty) - SUM(stock_out.qty)   (negative clamp karta hai)

Stock IN  → stock_in table (+qty)           [also: pallets add stock_in rows with notes='Pallet:X']
Stock OUT → stock_out table INSERT  + orders table INSERT (status PENDING)
Delivery  → delivery_log INSERT + orders.dispatched_qty += qty  (stock dobara NAHI kat-ta)
Update    → booked_qty change; available stock check; status auto (PENDING/PARTIAL/COMPLETED)
Cancel    → stock_in wapis (fresh/damaged) + order CANCELLED
```

---

## 🗄️ Database Structure (`pendingwithsarim`)

### users
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| name | VARCHAR(100) | |
| email | VARCHAR(100) UNIQUE | |
| password | VARCHAR(255) | password_hash() |
| reset_code | VARCHAR(10) NULL | forgot password |
| reset_expiry | DATETIME NULL | |
| created_at | TIMESTAMP | |

### parties
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| name | VARCHAR(255) | |
| phone | VARCHAR(50) | |
| address | TEXT | |
| created_at | DATETIME | |

### items
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| name | VARCHAR(255) | |
| category | VARCHAR(100) | |
| unit_price | DECIMAL(10,2) | default 0 |
| pcs_per_plate | INT | updated when pallet added |

### stock_in  (asli godown stock)
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| item_id | INT FK | |
| qty | INT | |
| supplier | VARCHAR(100) | ref / 'Pallet' / 'CANCEL' |
| in_date | DATE | |
| condition | ENUM('fresh','damaged') | |
| notes | VARCHAR(255) | 'Pallet:xyz' or ref |

### stock_out (sale record)
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| item_id | INT FK | |
| order_id | INT FK | |
| qty | INT | |
| plates | INT | |
| pcs_per_plate | INT | |
| item_condition | ENUM('FRESH','DAMAGED') | |
| customer_name | VARCHAR(255) | |
| notes | VARCHAR(255) | |
| out_date | DATE | |

### orders  (pending delivery system)
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| party_id | INT FK | |
| item_id | INT FK | |
| item_condition | ENUM('FRESH','DAMAGED') | |
| booked_qty | INT | |
| dispatched_qty | INT | default 0 |
| status | ENUM('PENDING','PARTIAL','COMPLETED','CANCELLED') | |
| ref_no | VARCHAR(100) | |
| order_date | DATE | |

### delivery_log
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| order_id | INT FK | |
| qty_delivered | INT | |
| dc_no | VARCHAR(100) | |
| vehicle_no | VARCHAR(50) | |
| notes | TEXT | |
| delivery_date | DATE | |

### activity_log  (NEW — every action tracked)
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK AUTO | |
| user_name | VARCHAR(100) | logged-in user |
| action | VARCHAR(100) | LOGIN / STOCK IN / STOCK OUT / PALLET ADD / PALLET DELETE / DELIVERY / ORDER UPDATE / ORDER CANCEL / ITEM ADD / PARTY ADD |
| details | TEXT | human-readable description |
| created_at | DATETIME | |

---

## 📁 Project Structure

```
pending/
├── assets/style.css          # All styling (+ dark mode override block)
├── config/
│   ├── db.php                # DB connection
│   └── smtp.php              # Gmail SMTP (forgot password) — placeholder creds 🔴
├── includes/
│   ├── activity.php          # log_activity() helper (session_start + insert)
│   ├── loader.php            # Full-screen loader (all pages)
│   └── sidebar.php           # Nav + user info + logout + dark-mode toggle
├── ajax/
│   ├── save_stock_in.php     # + activity hook
│   ├── save_stock_out.php    # + activity hook
│   ├── add_pallet.php        # single pallet (no per-pallet log)
│   ├── log_pallet_batch.php  # 1 batch log per pallet add session
│   ├── delete_pallet.php
│   ├── deliver_pending.php   # + activity hook
│   ├── update_order.php      # + activity hook
│   ├── cancel_order.php      # + activity hook
│   ├── add_item.php          # + activity hook
│   ├── add_party.php         # + activity hook
│   ├── get_items.php / get_parties.php / get_pallets.php / get_pending_orders.php
│   ├── get_item_summary.php / get_recent_transactions.php
│   ├── get_delivery_log.php / get_delivery_history.php / get_db_size.php
│   └── get_report_data.php   # documents page data (9 reports incl. activity)
├── login.php / register.php / forgot_password.php / reset_password.php
├── dashboard.php / stockin.php / stockout.php / item_ledger.php
├── pending.php / delivery_history.php / activity_log.php / documents.php
├── export_excel.php          # SpreadsheetML (.xls) — 9 reports
├── export_word.php           # Word (.doc) — 9 reports
├── download_db.php           # DB dump download
├── setup_database.php / reset_database.php   # 🔴 remove after live setup
└── lib/PHPMailer/*           # forgot-password email lib
```

---

## 📊 Documents / Reports (9)

| Report | Available In |
|--------|--------------|
| Stock by Item | Excel + Word |
| Stock IN (Pallets) | Excel + Word |
| Stock OUT (Sales) | Excel + Word |
| Recent Transactions | Excel + Word |
| Stock Ledger | Excel + Word |
| Items List | Excel + Word |
| Parties List | Excel + Word |
| Orders | Excel + Word |
| Delivery History | Excel + Word |
| **Activity Log** (NEW) | Excel + Word |

---

## 🌙 Dark Mode

- Toggle button in sidebar (bottom, below Logout)
- Toggle JS + CSS in `includes/sidebar.php` `<style>` block
- Preference saved in `localStorage['im_theme']`
- Tag/table colors are dark-aware (light shades in dark mode)

---

## 🔐 Known Issues / Notes

1. **`config/smtp.php` is public on GitHub** — placeholder Gmail credentials; use a real App Password before production.
2. `password` for **Admin/user accounts** show as `admin123` (test) — change before live.
3. Ajax files rely on `includes/activity.php` which now calls `session_start()` — session name reads correctly from browser.
4. Pallet bulk-add logs **one batch record** (via `log_pallet_batch.php`) instead of one per pallet, to keep the activity log readable.