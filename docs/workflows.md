# YasIX Store — Business Workflows & State Machines

This document illustrates the operational business workflows, state machines, and sequence lifecycles governing customer commerce and administrative operations in **YasIX Store**.

---

## 🛒 1. Customer Shopping & Atomic Checkout Lifecycle

```mermaid
sequenceDiagram
    autonumber
    actor C as Customer
    participant Cart as CartService
    participant Calc as DeliveryCalculator
    participant Check as CheckoutService
    participant DB as MySQL (InnoDB)

    C->>Cart: Add Product to Cart (qty: 2)
    Cart->>DB: Check stock >= 2 (Row lock)
    Cart->>DB: Insert / Update cart_items
    Cart-->>C: Cart updated with live subtotal

    C->>Check: Proceed to Checkout
    Check->>Calc: calculateDelivery(cartItems, regionId)
    Calc-->>Check: Chargeable weight & delivery fee (LKR)
    Check->>Check: Generate single-use idempotency checkout_token
    Check-->>C: Display order summary, shipping fee & checkout token

    C->>Check: Submit Order (token, address, COD)
    Check->>DB: BEGIN TRANSACTION
    Check->>DB: SELECT stock FROM products WHERE id IN (...) FOR UPDATE
    alt Stock Available
        Check->>DB: Decrement product stock_quantity
        Check->>DB: INSERT INTO orders (status='PENDING', payment_status='PENDING')
        Check->>DB: INSERT INTO order_items (snapshot unit_price & warranty_months)
        Check->>DB: INSERT INTO payments (status='PENDING', method='COD')
        Check->>DB: DELETE FROM cart_items WHERE cart_id = ...
        Check->>DB: COMMIT
        Check-->>C: Order Confirmed (Order #YAS-XXXXX)
    else Stock Insufficient (Race Condition)
        Check->>DB: ROLLBACK
        Check-->>C: Error: Hardware stock changed during checkout. Please review cart.
    end
```

---

## 🚚 2. Order Fulfilment State Machine vs. COD Decoupled Settlement

In cash-on-delivery e-commerce, order fulfilment and financial cash collection are two **strictly decoupled lifecycles**. Reaching the `DELIVERED` status does **not** mark cash as received.

### Order Fulfilment State Machine
The state machine strictly enforces forward-only progression. Backward transitions (e.g. from `SHIPPED` back to `PENDING`) are rejected by the domain validator.

```mermaid
stateDiagram-v2
    [*] --> PENDING: Order Placed by Customer
    
    PENDING --> CONFIRMED: Admin Verifies Customer Phone
    PENDING --> CANCELLED: Customer / Admin Early Cancel
    
    CONFIRMED --> PROCESSING: Warehouse Picks & Packs Hardware
    CONFIRMED --> CANCELLED: Customer / Admin Early Cancel
    
    PROCESSING --> SHIPPED: Handed to Courier (Tracking Code)
    
    SHIPPED --> OUT_FOR_DELIVERY: Courier Out on Van Route
    
    OUT_FOR_DELIVERY --> DELIVERED: Package Handed to Customer (Doorstep)
    OUT_FOR_DELIVERY --> DELIVERY_FAILED: Customer Unavailable / Address Missing
    
    DELIVERY_FAILED --> OUT_FOR_DELIVERY: Re-attempt Dispatch
    DELIVERY_FAILED --> RETURNED: Returned to Warehouse
    
    DELIVERED --> [*]: Terminal State
    CANCELLED --> [*]: Terminal State (Stock Restored)
    RETURNED --> [*]: Terminal State
```

### Decoupled COD Cash Collection Lifecycle
```mermaid
stateDiagram-v2
    [*] --> PENDING: Order Created (Expected COD Cash)
    
    PENDING --> COMPLETED: Courier Remits Cash (Admin Verifies Slip)
    PENDING --> FAILED: Order Cancelled / Delivery Returned
    
    COMPLETED --> [*]: Cash Deposited & Reconciled
    FAILED --> [*]: Zero Cash Collected
```

> [!NOTE]
> When `orders.status` reaches `DELIVERED`, `orders.delivered_at` is timestamped. However, `orders.payment_status` remains `PENDING` until store staff physically verifies the courier cash deposit in `/admin/payments`, advancing `payments.status` to `COMPLETED` and `orders.payment_status` to `PAID`.

---

## 🔄 3. Controlled Order Cancellation & Stock Restoration

Customers are permitted to cancel orders only prior to physical warehouse processing.

```mermaid
flowchart TD
    Req["Customer Requests Order Cancellation"] --> CheckStatus{"Is Status PENDING or CONFIRMED?"}
    
    CheckStatus -->|No (PROCESSING, SHIPPED, DELIVERED)| Reject["Reject: Order has entered physical dispatch queue"]
    
    CheckStatus -->|Yes| Lock["BEGIN TRANSACTION (InnoDB)"]
    Lock --> UpdOrder["Update orders.status = 'CANCELLED', cancelled_at = NOW()"]
    UpdOrder --> LoopItems["For each item in order_items"]
    LoopItems --> RestoreStock["UPDATE products SET stock_quantity = stock_quantity + qty WHERE id = ?"]
    RestoreStock --> Audit["INSERT INTO inventory_logs (reason='Customer Cancellation')"]
    Audit --> UpdPay["UPDATE payments SET status = 'FAILED'"]
    UpdPay --> Commit["COMMIT TRANSACTION"]
    Commit --> Success["Return Success & Notify Customer"]
```

---

## 🛡️ 4. Delivery-Based Warranty & RMA Claim Lifecycle

Warranties begin on the **actual doorstep delivery date**, never on the checkout date.

```mermaid
flowchart TD
    Cust["Customer Navigates to /warranties"] --> CheckDelivered{"Has Order Item Reached DELIVERED?"}
    
    CheckDelivered -->|No| Ineligible1["Ineligible: Hardware has not completed doorstep delivery"]
    
    CheckDelivered -->|Yes| CheckDuration{"Has Warranty Expired? (NOW <= delivered_at + warranty_months)"}
    CheckDuration -->|No (Expired)| Ineligible2["Ineligible: Coverage expired on calculated boundary date"]
    
    CheckDuration -->|Yes| CheckActiveClaim{"Is an active claim already pending for this item?"}
    CheckActiveClaim -->|Yes| Ineligible3["Blocked: Claim is already actively undergoing review"]
    
    CheckActiveClaim -->|No| ShowForm["Render Claim Submission Terminal"]
    ShowForm --> Submit["Customer Submits Reason & Fault Telemetry"]
    Submit --> SubState["Status: SUBMITTED"]
    
    SubState --> StaffRev["Admin Transitions to UNDER_REVIEW (Logs diagnostic notes)"]
    StaffRev --> Decision{"Staff Adjudication Decision"}
    
    Decision -->|Rejected| Rej["Status: REJECTED (Reason communicated to customer)"]
    Decision -->|Approved| App["Status: APPROVED (RMA supplier credit issued)"]
    App --> Res["Status: RESOLVED (Replacement hardware dispatched via courier)"]
    Res --> Close["Status: CLOSED (Claim archived)"]
```

---

## ⚖️ 5. Volumetric Weight Delivery Fee Algorithm

Couriers operating across Sri Lanka charge based on the greater of actual scale weight vs. dimensional bulkiness:

```mermaid
flowchart LR
    Items["Cart Items & Quantities"] --> CalcAct["Sum Physical Weights: Gross (kg)"]
    Items --> CalcVol["Sum Box Volumes: (L x W x H x Qty) / 5000 (kg)"]
    
    CalcAct --> Comp{"Compare Weights"}
    CalcVol --> Comp
    
    Comp -->|Actual > Volumetric| Chrg1["Chargeable Weight = Actual Weight"]
    Comp -->|Volumetric >= Actual| Chrg2["Chargeable Weight = Volumetric Weight"]
    
    Chrg1 --> Formula["Fee = Base Fee + ceil(max(0, Chargeable - Included)) x Surcharge/kg"]
    Chrg2 --> Formula
    
    Formula --> Quote["Quote Final Shipping Fee (LKR)"]
```

### Reference Tariff Rules (Configured in `delivery_rules`)
1. **Western Province (ID: 1)**: Base Fee: LKR 350.00 (up to 1.0 kg), Surcharge: LKR 50.00/extra kg.
2. **Outstation Central (ID: 2)**: Base Fee: LKR 450.00 (up to 1.0 kg), Surcharge: LKR 75.00/extra kg.
3. **Express Drone Delivery (ID: 3)**: Base Fee: LKR 950.00 (up to 1.0 kg), Surcharge: LKR 100.00/extra kg.

---

## 🔐 6. Administrator Privilege Shield

Administrative routes (`/admin/*`) enforce two-tiered protection:

```mermaid
flowchart TD
    Req["Request to /admin/*"] --> CheckAuth{"Is Session Logged In?"}
    
    CheckAuth -->|No| Redir["302 Redirect to /admin/login"]
    
    CheckAuth -->|Yes| CheckRole{"Is role_name === 'ADMIN'?"}
    CheckRole -->|No (CUSTOMER)| Block["403 Forbidden Terminal (Access Denied)"]
    
    CheckRole -->|Yes| Action{"Is Admin Performing Action?"}
    
    Action -->|Suspend Self| BlockSelf["Security Policy: Cannot suspend active administrative session"]
    Action -->|Alter other Admin| BlockAdmin["Security Policy: Cannot modify peer administrator via customer terminal"]
    Action -->|Standard Task| Allow["Execute Admin Operation (Audit Logged)"]
```
