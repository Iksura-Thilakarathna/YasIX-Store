# Changelog — YasIX Store

All notable changes to the **YasIX Store** project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - Final Portfolio Release

### Added
- **Automated Release Readiness Suite**: Added [`tests/verify_release_readiness.php`](tests/verify_release_readiness.php) auditing 53 checkpoints spanning relational schema integrity, the full customer journey, admin operations, state machines, and defensive security.
- **Comprehensive Project Documentation**:
  - [`README.md`](README.md): Portfolio-quality project overview, problem statement, key features, technology stack, and setup guides.
  - [`docs/installation.md`](docs/installation.md): Detailed local installation, XAMPP configuration, database initialization, and troubleshooting guide.
  - [`docs/architecture.md`](docs/architecture.md): Modular Monolith MVC layer descriptions, request lifecycle flow, and Mermaid architecture diagrams.
  - [`docs/database.md`](docs/database.md): 18-table relational Entity-Relationship Diagram (ERD) and complete data dictionary.
  - [`docs/workflows.md`](docs/workflows.md): Customer checkout sequence, forward-only fulfilment state machine, decoupled COD accounting, and volumetric shipping algorithm.
- **Authentic Portfolio Screenshots**: Captured 9 high-resolution screenshots of the working application (`docs/screenshots/01_storefront_homepage.png` through `09_admin_orders.png`) using headless browser automation.
- **Production Error Handling**: Added branded cyber-themed [`views/500.php`](views/500.php) internal server error screen and centralized exception logging in `public/index.php`.
- **Apache URL Rewriting**: Added `public/.htaccess` and root `.htaccess` for smooth XAMPP Apache URL routing to front controller.

### Changed
- Hardened `.env.example` with clear parameter descriptions and safe default placeholders.
- Updated `.gitignore` to protect runtime logs, session caches, and test artifacts.
- Configured dynamic error reporting based on `APP_DEBUG` environment setting.

---

## [0.7.0] - Complete UI Integration & Professional Polish

### Added
- Dynamic database-backed catalog integration across homepage hero, flash deals, categories, and product detail views.
- Authentication interfaces with preserved return URLs, field-level feedback, and multi-error banners.
- Dynamic shopping cart integration with real-time stock sync and volumetric delivery fee estimation.
- Order tracking milestone radar and delivery-based RMA warranty claim lodgment interface.
- Admin operational controls with mandatory confirmation prompts on destructive actions.
- Responsive mobile drawer navigation (`#nav-toggle`) in cyber navbar.
- High-contrast `:focus-visible` accessible keyboard rings across all interactive controls.
- Pointer-capability media query detection (`(hover: none) and (pointer: coarse)`) to disable custom cursor on touch devices.
- Support for `@media (prefers-reduced-motion: reduce)`.
- End-to-end integration test suite [`tests/verify_day7_complete.php`](tests/verify_day7_complete.php).

### Changed
- Hardened `ProductService::updateProduct` to preserve physical package dimensions on partial updates.

---

## [0.6.0] - Admin Dashboard & Management System

### Added
- Dedicated administrative command hub (`views/layouts/admin_header.php`, `views/layouts/admin_footer.php`).
- Server-side `RoleMiddleware` enforcing `ADMIN` role with dedicated `403 Forbidden` response (`views/403.php`).
- Real-time database KPI analytics (orders, delivered revenue, collected COD cash, low stock, open claims).
- Product and category CRUD with soft-deactivation (`INACTIVE`) and foreign-key deletion guards.
- Real-time stock ledger (`/admin/inventory`) with immutable audit trails in `inventory_logs`.
- Order fulfilment terminal (`/admin/orders`) enforcing forward-only state progression.
- Decoupled COD payment settlement ledger (`/admin/payments`).
- Customer management terminal (`/admin/customers`) with account suspension and administrator self-suspension shield.
- Warranty claim adjudication terminal (`/admin/warranties`) with confidential staff notes.
- Performance intelligence reports (`/admin/reports`) with CSV export protected against formula injection (CWE-1236).
- Secure CLI administrator provisioning tool [`scripts/provision_admin.php`](scripts/provision_admin.php).

---

## [0.5.0] - Order Management & Warranty Handling

### Added
- Customer order history registry (`/orders`) with LKR currency formatting and pagination.
- Detailed historical order receipts (`/orders/{order_number}`) preserving purchase-time price snapshots and address snapshots.
- Courier tracking milestone radar (`/orders/{order_number}/track`) with lifecycle audit log.
- Customer pre-fulfilment order cancellation with atomic stock restoration in database transactions.
- Delivery-based warranty duration calculation starting strictly on `orders.delivered_at`.
- RMA technical claim lodgment (`/warranties/claims`) with anti-duplicate claim shield.
- Strict server-side IDOR defenses preventing cross-tenant access to orders and warranty claims.

---

## [0.4.0] - Shopping Cart & Checkout System

### Added
- Persistent database shopping cart (`carts`, `cart_items`) with real-time warehouse inventory sync.
- Island-wide volumetric delivery calculation engine (`delivery_rules`) supporting multi-tier regions without paid external APIs.
- Cash on Delivery (COD) transactional checkout wrapping order creation, inventory decrements, and cart clearance in InnoDB transactions with row-level locks (`SELECT ... FOR UPDATE`).
- Single-use cryptographic idempotency tokens preventing accidental double-order submissions.

---

## [0.3.0] - Product Catalog & Search

### Added
- Filterable storefront catalog with multi-criteria search (keyword, category, brand, condition, in-stock only, price range).
- SQL-injection-safe parameterized sorting (`price_asc`, `price_desc`, `newest`) and pagination.
- Dynamic product detail views with related hardware suggestions and safe 404 handling.

---

## [0.2.0] - Authentication & User Management

### Added
- Customer registration and login with `PASSWORD_BCRYPT` password hashing.
- Session-based authentication with `AuthMiddleware` and `RoleMiddleware`.
- Customer account profile management and password modification.

---

## [0.1.0] - Backend Foundation

### Added
- Modular Monolith MVC directory structure and PSR-4 autoloader.
- Relational MySQL schema with 18 InnoDB tables, foreign keys, and indexes (`database/schema.sql`).
- PDO database connection singleton (`App\Database\Connection`).
- Base HTTP router (`App\Router`) and initial frontend cyber layout.
- Initial seed data for hardware products, categories, and delivery rules (`database/seed.sql`).
