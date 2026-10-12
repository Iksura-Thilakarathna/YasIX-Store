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

---

## 🛒 Day 4 Key Features

### 1. Persistent Database Shopping Cart
- User-associated shopping cart persisted across sessions in `carts` and `cart_items`.
- Real-time stock validation ensuring users cannot add more items than physical stock allows.
- Automatic recalculation of line totals and cart subtotals.
- Dedicated cyber-themed cart interface (`/cart`) with dynamic item cards, quantity modifiers, and removal actions.

### 2. Local Delivery Fee Calculation (Zero External APIs)
- Computes volumetric weight using industry standard logistics formula:
  $$\text{Volumetric Weight (kg)} = \frac{\text{Length (cm)} \times \text{Width (cm)} \times \text{Height (cm)}}{5000}$$
- Chargeable weight determined by $\max(\text{Actual Weight}, \text{Volumetric Weight})$, rounded up to the nearest 0.5 kg increment.
- Configurable regional tiers in Sri Lanka (`delivery_rules`):
  - **Western Province**: Base fee LKR 350.00 (up to 1.0 kg), LKR 50.00 per additional kg.
  - **Outstation Central**: Base fee LKR 450.00 (up to 1.0 kg), LKR 75.00 per additional kg.
  - **Northern & Eastern**: Base fee LKR 600.00 (up to 1.0 kg), LKR 100.00 per additional kg.
- Courier threshold validation preventing oversized dispatches over max allowed weight.
- Fixed 5–7 working days overseas shipping timeline estimate.

### 3. Transactional Cash on Delivery Checkout
- Requires customer authentication (`AuthMiddleware`) before checkout dispatch.
- Atomic InnoDB database transaction:
  1. Row-level stock locking using `SELECT ... FOR UPDATE`.
  2. Stock decrement with safety check (`stock_quantity >= quantity`).
  3. Snapshot recording of unit prices in `order_items` (preserving historical order values).
  4. Generation of unique human-readable tracking number (`YAS-YYYYMMDD-XXXXX`).
  5. Automatic creation of Cash on Delivery record in `payments` table with `PENDING` status.
  6. Cart automatically cleared post-checkout.
- Idempotency & duplicate submission shield using single-use `checkout_token`.
- Cyber-themed order confirmation receipt page (`/checkout/success/{order_number}`) restricted to the purchasing customer.

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

# Front Controller Route & View Dispatch Verification
php tests/test_dispatch.php
```

All suites execute cleanly with zero external dependencies or paid services.
