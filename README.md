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
- **Day 6**: Admin Dashboard and Management System:
  1. Administrative authentication, dedicated admin portal layout, server-side `RoleMiddleware` with 403 Forbidden handling, and live aggregate analytics KPI cards.
  2. Product and category administration (`/admin/products`, `/admin/categories`, `/admin/inventory`) with non-negative constraints, unique slug validation, safe file uploads, soft-deactivation (`INACTIVE`), atomic inventory adjustments, and `inventory_logs` audit trail.
  3. Fulfilment order lifecycle terminal (`/admin/orders`), COD payment settlement ledger (`/admin/payments`), customer directory & status management (`/admin/customers`), and warranty claim adjudication (`/admin/warranties`) with confidential internal notes vs public resolutions.
  4. Intelligence reports (`/admin/reports`), sanitized CSV data exports with formula injection defense (`=, +, -, @, \t, \r`), and comprehensive regression validation suites.
- **Day 7**: Complete UI Integration & Professional Polish:
  1. Storefront & Backend Product Integration: Database-backed dynamic products, categories, stock availability, search, sorting, and pagination across all storefront views.
  2. Authentication & Account Interfaces: Login redirect preserving return path, field-level and multi-error banners, and session verification.
  3. Shopping Cart & Checkout Experience: Dynamic live cart sync, volumetric delivery fee quoting, and atomic COD checkout.
  4. Order Tracking & Warranty Interfaces: Real-time milestone tracker, delivery-based RMA claim lodgment, and IDOR customer isolation.
  5. Admin Dashboard & Operations Integration: Confirmation prompts for critical admin actions, live database metrics, order status advancement, and COD cash collection.
  6. Responsive Polish & Accessibility: Mobile navigation drawer, graceful touchscreen cursor fallback via pointer media queries, high-contrast `:focus-visible` rings, and `@media (prefers-reduced-motion: reduce)` support.
  7. End-to-End Integration Validation (`tests/verify_day7_complete.php`): Full 53-point verification suite covering all customer and administrator workflows.

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

## ⚡ Day 6 Key Features & Administration Architecture

### 1. Administrative Authentication & Access Control
- **Server-Side Role Protection**: All administrative endpoints (`/admin/*`) are protected by `RoleMiddleware` requiring `ADMIN` role from server session and database verification. Unauthenticated requests redirect to `/admin/login`; unauthorized customers receive a dedicated `403 Forbidden` terminal (`views/403.php`) without leaking administrative views or data.
- **Secure Provisioning (Zero Hardcoded Passwords)**: Store administrators cannot be created via public customer registration. Use the secure CLI provisioning tool:
  ```bash
  php scripts/provision_admin.php "Super Admin" "admin@yasix.local" "YourSecurePassword"
  ```
- **Administrative Portal Layout**: Dedicated cyberpunk command hub layout (`views/layouts/admin_header.php`, `views/layouts/admin_footer.php`) featuring collapsible navigation, active route highlighting, system health status indicator, session pill, and breadcrumbs.

### 2. Product, Category & Inventory Control
- **SKU Administration (`/admin/products`)**: Filterable catalog table with live stock counters, brand/slug management, safe image uploading with MIME validation (`image/jpeg`, `image/png`, `image/webp`), and strict non-negative price/stock validation.
- **Soft Deactivation (`INACTIVE`)**: Soft-deactivating a SKU immediately hides it from the customer storefront and search index while preserving historical order item references and historical receipts intact.
- **Category Taxonomy (`/admin/categories`)**: Create and edit categories with slug validation, status toggle (`ACTIVE`/`INACTIVE`), and foreign-key protection that safely blocks deleting categories that contain active products.
- **Inventory Matrix & Audit Trail (`/admin/inventory`)**: Inline stock adjustments with real-time recalculation, automated status transitions (`OUT_OF_STOCK` when quantity reaches 0), and immutable audit logs in `inventory_logs` recording previous stock, new stock, change quantity, admin user ID, timestamp, and audit justification.

### 3. Fulfilment Lifecycle & Decoupled COD Settlement
- **Order Fulfilment Terminal (`/admin/orders`, `/admin/orders/{order_number}`)**:
  - Live order stream with multi-criteria filters (fulfilment stage, payment state, date range, customer search).
  - Terminal view preserves historical purchase-time prices, line totals, delivery fee, and address snapshots.
  - Forward-only state machine enforces lifecycle progression:
    $$\text{PENDING} \longrightarrow \text{CONFIRMED} \longrightarrow \text{PROCESSING} \longrightarrow \text{SHIPPED} \longrightarrow \text{OUT\_FOR\_DELIVERY} \longrightarrow \text{DELIVERED}$$
  - Terminal states (`CANCELLED`, `RETURNED`) are locked against unauthorized transitions.
  - Full lifecycle audit trail logs every stage transition with previous status, new status, actor name, role, timestamp, and reason.
- **COD Payment Decoupling (`/admin/payments`)**:
  - Delivering an order does **not** automatically mark Cash on Delivery as collected.
  - Courier cash remittance must be explicitly recorded and verified by an administrator via `/admin/orders/{order_number}/collect-payment` or the `/admin/payments` ledger.
  - Updates `payments.status = 'COMPLETED'`, records `collected_at`, `collected_by_user_id`, and notes, and updates `orders.payment_status = 'PAID'`.
  - Duplicate collection attempts on settled payments are strictly blocked.
- **Order Cancellation Stock Restoration**:
  - Cancelling an order via the admin terminal atomically restores product quantities in `products` and records an inventory audit log.

### 4. Customer Directory & Privilege Shields
- **Customer Dossier (`/admin/customers`, `/admin/customers/{id}`)**: Customer registry with search, status filters, lifetime metrics (total purchases, lifetime spend in LKR, RMA claims count), purchase history, and submitted warranty claims.
- **Account Status Toggles (`ACTIVE` / `SUSPENDED`)**: Suspending a customer account immediately terminates access and blocks future logins (`UserService::login()` throws explicit account suspension error). Reactivating restores access immediately.
- **Security Privilege Shields**:
  - Administrators are strictly blocked from suspending their own active administrative session.
  - Other administrator accounts cannot be modified or suspended through the customer management terminal.

### 5. Warranty & RMA Adjudication
- **Claims Terminal (`/admin/warranties`, `/admin/warranties/{id}`)**: Review customer defect claims against hardware manifests, recorded delivery dates, and coverage dates.
- **Strict Adjudication Lifecycle**:
  $$\text{SUBMITTED} \longrightarrow \text{UNDER\_REVIEW} \longrightarrow \text{APPROVED} / \text{REJECTED} \longrightarrow \text{RESOLVED} \longrightarrow \text{CLOSED}$$
- **Confidential Internal Audit Notes**: Customer-facing decision summaries and resolution instructions are kept separate from confidential internal staff notes (`warranty_claims.internal_notes`), preserving staff discussions and supplier RMA codes private.

### 6. Performance Reports & Safe CSV Export
- **Intelligence Reports (`/admin/reports`)**: Period performance breakdown (Today, Last 7 Days, Last 30 Days, Year to Date, All Time, Custom Date Range) with delivered revenue, collected cash, gross pipeline value, order stage distribution, and top velocity hardware SKUs.
- **CSV Formula Injection Defense**: All exported CSV files (Orders, Inventory, Payments, Warranties) neutralize spreadsheet command injection. Any cell starting with `=, +, -, @, \t, \r` is automatically prefixed with an apostrophe (`'`) and safely double-quote escaped.

---

## 🎨 Day 7 UI Integration & Accessibility Highlights

- **Storefront & Catalog Dynamic Integration**: Live database product bindings, LKR formatting, category tabs, and real-time stock indicators across homepage, catalog, and product details.
- **Form Hardening & Field Feedback**: Consistent field-level validation, alert banners, and CSRF protection across customer and administrative interfaces.
- **Responsive Navigation & Layouts**: Responsive mobile drawer menu (`#nav-toggle`), stackable order/checkout summary panels, and responsive admin split layouts.
- **Graceful Touchscreen Handling**: Device-aware cursor logic detects touch pointer capability via `(hover: none) and (pointer: coarse)` and gracefully restores standard touch scrolling and tap feedback.
- **Accessibility & Motion Preferences**: High-contrast `:focus-visible` keyboard rings across all interactive controls and respect for `@media (prefers-reduced-motion: reduce)`.

---

## 🗄️ Database Migrations

Apply database enhancements:

```bash
php database/migrate.php
```

Includes:
- `database/migrations/2026_10_13_day5_order_warranty.sql`
- `database/migrations/2026_10_16_day6_admin_management.sql`

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

# Day 6: Admin Authentication & Analytics Dashboard Suite (Commit 1)
php tests/verify_admin_dashboard.php

# Day 6: Product & Category Administration Suite (Commit 2)
php tests/verify_admin_catalog.php

# Day 6: Order, Customer & Warranty Administration Suite (Commit 3)
php tests/verify_admin_management.php

# Day 6: Complete End-to-End Admin Management & Security Suite (Commit 4)
php tests/verify_day6_complete.php

# Day 7: Complete End-to-End UI Integration & Polish Suite
php tests/verify_day7_complete.php

# Front Controller Route & View Dispatch Verification
php tests/test_dispatch.php
```

All suites execute cleanly with zero external dependencies or paid services.
