# YasIX-Store — Cyber-Themed E-Commerce Web Application

**YasIX-Store** is a full-stack e-commerce portfolio application engineered to demonstrate software engineering and Information Systems proficiency. The platform specializes in selling overseas-sourced quantum hardware, high-end PC rigs, and cybernetic accessories in Sri Lanka, developed without commercial or paid third-party APIs.

---

## 🛠️ Technology Stack & Architecture

- **Backend**: PHP 8.x (Strict Types, PSR-4 Autoloading)
- **Database**: MySQL with PDO (Prepared Statements, InnoDB Transactions, Strict Row Locking `FOR UPDATE`)
- **Architecture**: Modular Monolith MVC with Service and Repository Layers
  - **Controllers**: Thin HTTP entrypoints handling input sanitization, CSRF tokens, and view rendering.
  - **Services**: Pure business logic (e.g. cart subtotaling, local volumetric delivery algorithms, transactional order placement).
  - **Repositories**: Direct data access with strict parameter binding (Cart, Product, DeliveryRule, Order, User).
- **Frontend**: Custom cyber-themed UI preserved with neon cyber aesthetics, Font Awesome 6 icons, responsive layouts, and interactive components.
- **Payment Method**: Cash on Delivery (COD) with local currency calculations (USD / LKR support).
- **Zero Paid APIs**: Weight, dimensions, and regional distance rules are computed entirely locally via the database-driven `DeliveryCalculatorService`.

---

## 📅 9-Day Development Roadmap & Milestones

- **Day 1**: Backend Foundation (Modular Monolith MVC, PDO singleton, 16-table relational MySQL schema, seed data, base routing).
- **Day 2**: Authentication & User Management (Registration, bcrypt password hashing, session management, Auth & Role middleware, customer profile).
- **Day 3**: Product Catalog & Search (Category filters, price boundaries, in-stock filtering, keyword search, SQL-safe sorting, pagination, cyber product details).
- **Day 4**: Shopping Cart & Checkout System:
  1. Persistent database shopping cart (`carts`, `cart_items`) with real-time stock sync.
  2. Local weight-based and volumetric delivery calculation system (`delivery_rules`).
  3. Transactional Cash on Delivery (COD) checkout with atomic inventory decrement (`orders`, `order_items`, `payments`).
  4. End-to-end security hardening, idempotency token validation, responsive layouts, and regression test suites.
- **Day 5**: Order Management and Warranty Handling:
  1. Authenticated customer order history (`/orders`), detailed historical specification receipts (`/orders/{order_number}`), and live courier tracking radar (`/orders/{order_number}/track`).
  2. Order status lifecycle management (`order_status_history`), customer cancellation with atomic stock restoration, and Cash on Delivery payment decoupling.
  3. Hardware warranty coverage registry (`/warranties`), delivery-based warranty calculation, and RMA technical claim handling (`/warranties/claims`).
  4. End-to-end security hardening against IDOR, CSRF, and concurrent duplicate submissions with zero paid external APIs.

---

## 📦 Day 5 Key Features & Business Policies

### 1. Customer Order History & Courier Tracking
- **Order History Registry (`/orders`)**: Authenticated customer view with pagination, order date, hardware unit count, LKR currency formatting, payment method, payment status, and order status badge.
- **Specification Receipts (`/orders/{order_number}`)**: Preserves historical snapshots of unit prices, line totals, delivery fee, and customer shipping address. Historical records remain unchanged if current catalog prices or profiles change.
- **Courier Radar & Lifecycle Tracking (`/orders/{order_number}/track`)**: Displays sequential dispatch milestones:
  $$\text{Pending Confirmation} \longrightarrow \text{Confirmed} \longrightarrow \text{Processing} \longrightarrow \text{Shipped} \longrightarrow \text{Out for Delivery} \longrightarrow \text{Delivered}$$
  Along with terminal states (`CANCELLED`, `DELIVERY_FAILED`, `RETURNED`) and complete audit history logs.
- **Strict Authorization**: Server strictly enforces `$order['user_id'] === $currentUserId` and returns 404/403 without leaking order existence across accounts.

### 2. Order Status Management & Controlled Cancellation
- **Order Lifecycle vs. COD Payment**:
  - COD orders remain in `PENDING` payment status upon creation and shipment.
  - Payment is updated to `PAID` / `COMPLETED` only upon verified doorstep cash collection.
  - Cancelled COD orders remain unpaid (`FAILED`), never marked refunded or paid.
- **Customer Cancellation Rules**:
  - Eligible only during early lifecycle phases: `PENDING` (Pending Confirmation) or `CONFIRMED`.
  - Disallowed once warehouse fulfilment begins (`PROCESSING`, `SHIPPED`, `OUT_FOR_DELIVERY`, `DELIVERED`).
- **Atomic Inventory Stock Restoration**:
  - Uses InnoDB database transaction with row-level locks (`SELECT ... FOR UPDATE`).
  - Restores decremented item stock only when eligible cancellation commits.
  - Shields against duplicate cancellation and double stock restoration.

### 3. Warranty Coverage & Technical Claim Handling
- **Warranty Commencement Date**: Begins strictly on actual recorded delivery date (`orders.delivered_at`), never at cart creation, order placement, or dispatch.
- **Purchase-Time Warranty Duration Snapshot**: Preserves `warranty_months` (typically 3–6 months for electronics) in `order_items`. If product configuration changes later, historical warranty coverage remains untouched. Products with 0 months warranty are excluded.
- **Expiry Boundary Policy**: Coverage is valid through 23:59:59 on the exact expiration date (`delivered_at + warranty_months months`). Expired warranties are blocked on the server.
- **Claim Workflow (`/warranties/claims`)**:
  - Eligible customers can submit claims with validated reason (`Product not functioning`, `Hardware defect`, `Battery issue`, etc.) and detailed telemetry description.
  - Prevents duplicate active claims for the same hardware item.
  - Claim lifecycle: $\text{SUBMITTED} \rightarrow \text{UNDER\_REVIEW} \rightarrow \text{APPROVED} / \text{REJECTED} \rightarrow \text{RESOLVED} \rightarrow \text{CLOSED}$.
  - Admin authorization foundation for diagnostic findings and replacement resolutions; ordinary customers cannot approve their own claims.

---

## 🗄️ Database Migrations

Apply the Day 5 database enhancements:

```bash
php database/migrate.php
```

Or execute SQL migration file:
`database/migrations/2026_10_13_day5_order_warranty.sql`

---

## 🧪 Testing & Verification Suites

Execute tests using PHP CLI from the project root:

```bash
# Day 1: Backend Foundation & Database Verification
php tests/verify_foundation.php

# Day 2: Authentication & User Management Suite
php tests/verify_auth.php

# Day 3: Catalog, Search, Filtering & Pagination Suite
php tests/verify_catalog.php

# Day 4: Cart Operations Suite (Commit 1)
php tests/verify_cart.php

# Day 4: Delivery Calculator Suite (Commit 2)
php tests/verify_delivery.php

# Day 4: Checkout & COD Transactions Suite (Commit 3)
php tests/verify_checkout.php

# Day 4: Complete End-to-End Cart & Checkout Suite
php tests/verify_day4_complete.php

# Day 5: Customer Order History & Tracking Suite (Commit 1)
php tests/verify_orders_tracking.php

# Day 5: Order Status & Cancellation Suite (Commit 2)
php tests/verify_order_cancellation.php

# Day 5: Warranty Coverage & Claim Management Suite (Commit 3)
php tests/verify_warranty.php

# Day 5: Complete End-to-End Order & Warranty Suite (Commit 4)
php tests/verify_day5_complete.php

# Front Controller Route & View Dispatch Verification
php tests/test_dispatch.php
```

All suites execute cleanly with zero external dependencies or paid services.

