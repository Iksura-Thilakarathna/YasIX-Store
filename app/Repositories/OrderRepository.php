<?php

namespace App\Repositories;

use PDO;
use Throwable;
use Exception;

class OrderRepository extends BaseRepository
{
    protected string $table = 'orders';

    public function createOrderWithTransaction(
        array $orderData,
        array $orderItems,
        array $paymentData,
        int $cartId
    ): array {
        $this->db->beginTransaction();

        try {
            // 1. Verify and lock product stock & status
            foreach ($orderItems as $item) {
                $productId = (int)$item['product_id'];
                $requiredQty = (int)$item['quantity'];

                $stmt = $this->db->prepare("SELECT stock_quantity, status, name, price FROM products WHERE id = :id FOR UPDATE");
                $stmt->execute(['id' => $productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product || $product['status'] !== 'AVAILABLE') {
                    throw new Exception("Product '{$item['product_name']}' is no longer available.");
                }

                if ((int)$product['stock_quantity'] < $requiredQty) {
                    throw new Exception("Insufficient stock for '{$product['name']}'. Only {$product['stock_quantity']} units remaining.");
                }

                // Decrement stock
                $decStmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id");
                $decStmt->execute([
                    'qty' => $requiredQty,
                    'id' => $productId
                ]);
            }

            // 2. Save Address Record if provided
            $addressId = null;
            if (!empty($orderData['address'])) {
                $addr = $orderData['address'];
                $addrStmt = $this->db->prepare(
                    "INSERT INTO addresses (user_id, address_line1, address_line2, city, state, postal_code, country, created_at)
                     VALUES (:user_id, :address_line1, :address_line2, :city, :state, :postal_code, :country, NOW())"
                );
                $addrStmt->execute([
                    'user_id' => $orderData['user_id'],
                    'address_line1' => trim($addr['address_line1']),
                    'address_line2' => trim($addr['address_line2'] ?? ''),
                    'city' => trim($addr['city']),
                    'state' => trim($addr['state']),
                    'postal_code' => trim($addr['postal_code'] ?? ''),
                    'country' => 'Sri Lanka'
                ]);
                $addressId = (int)$this->db->lastInsertId();
            }

            // 3. Insert Order
            $orderStmt = $this->db->prepare(
                "INSERT INTO orders (order_number, user_id, address_id, total_amount, shipping_fee, status, payment_method, payment_status, created_at, updated_at)
                 VALUES (:order_number, :user_id, :address_id, :total_amount, :shipping_fee, :status, :payment_method, :payment_status, NOW(), NOW())"
            );
            $orderStmt->execute([
                'order_number' => $orderData['order_number'],
                'user_id' => $orderData['user_id'],
                'address_id' => $addressId,
                'total_amount' => $orderData['total_amount'],
                'shipping_fee' => $orderData['shipping_fee'],
                'status' => $orderData['status'] ?? 'PENDING',
                'payment_method' => $orderData['payment_method'] ?? 'CASH_ON_DELIVERY',
                'payment_status' => $orderData['payment_status'] ?? 'PENDING'
            ]);
            $orderId = (int)$this->db->lastInsertId();

            // 4. Insert Order Items (with historical price and warranty snapshot)
            $itemStmt = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal, warranty_months, created_at)
                 VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity, :subtotal, :warranty_months, NOW())"
            );

            foreach ($orderItems as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => (int)$item['product_id'],
                    'product_name' => $item['product_name'],
                    'unit_price' => (float)$item['unit_price'],
                    'quantity' => (int)$item['quantity'],
                    'subtotal' => (float)$item['subtotal'],
                    'warranty_months' => (int)($item['warranty_months'] ?? 0)
                ]);
            }

            // 5. Insert Payment Record
            $paymentStmt = $this->db->prepare(
                "INSERT INTO payments (order_id, payment_method, amount, status, transaction_reference, created_at)
                 VALUES (:order_id, :payment_method, :amount, :status, :transaction_reference, NOW())"
            );
            $paymentStmt->execute([
                'order_id' => $orderId,
                'payment_method' => $paymentData['payment_method'] ?? 'CASH_ON_DELIVERY',
                'amount' => $orderData['total_amount'],
                'status' => $paymentData['status'] ?? 'PENDING',
                'transaction_reference' => $paymentData['transaction_reference'] ?? ('COD-' . $orderData['order_number'])
            ]);

            // 6. Record Initial Order Status in History
            $historyStmt = $this->db->prepare(
                "INSERT INTO order_status_history (order_id, previous_status, new_status, changed_by_user_id, actor_role, reason, created_at)
                 VALUES (:order_id, NULL, :new_status, :changed_by_user_id, 'CUSTOMER', 'Order placed by customer via Cash on Delivery checkout', NOW())"
            );
            $historyStmt->execute([
                'order_id' => $orderId,
                'new_status' => $orderData['status'] ?? 'PENDING',
                'changed_by_user_id' => $orderData['user_id']
            ]);

            // 7. Clear Cart Items
            if ($cartId > 0) {
                $clearStmt = $this->db->prepare("DELETE FROM cart_items WHERE cart_id = :cart_id");
                $clearStmt->execute(['cart_id' => $cartId]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderData['order_number']
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getUserOrders(int $userId, int $page = 1, int $limit = 10): array
    {
        $offset = max(0, ($page - 1) * $limit);

        // Count total orders
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id");
        $countStmt->execute(['user_id' => $userId]);
        $total = (int)$countStmt->fetchColumn();

        // Fetch paginated orders with item count and summary
        $sql = "SELECT o.*, 
                       COUNT(oi.id) AS total_items,
                       COALESCE(SUM(oi.quantity), 0) AS total_units,
                       p.status AS payment_record_status
                FROM orders o
                LEFT JOIN order_items oi ON oi.order_id = o.id
                LEFT JOIN payments p ON p.order_id = o.id
                WHERE o.user_id = :user_id
                GROUP BY o.id
                ORDER BY o.created_at DESC, o.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

        return [
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages
        ];
    }

    public function getOrderStatusHistory(int $orderId): array
    {
        $sql = "SELECT h.*, u.name AS actor_name
                FROM order_status_history h
                LEFT JOIN users u ON h.changed_by_user_id = u.id
                WHERE h.order_id = :order_id
                ORDER BY h.created_at ASC, h.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderWithDetails(string $orderNumber, int $userId): ?array
    {
        $sql = "SELECT o.*, a.address_line1, a.address_line2, a.city, a.state, a.postal_code, a.country,
                       u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                       p.status AS payment_record_status, p.transaction_reference
                FROM orders o
                LEFT JOIN addresses a ON o.address_id = a.id
                JOIN users u ON o.user_id = u.id
                LEFT JOIN payments p ON p.order_id = o.id
                WHERE o.order_number = :order_number AND o.user_id = :user_id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_number' => $orderNumber,
            'user_id' => $userId
        ]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        // Fetch Order Items (preserves historical snapshots)
        $itemsSql = "SELECT oi.*, p.slug AS product_slug, c.name AS category_name, c.icon_class AS category_icon
                     FROM order_items oi
                     LEFT JOIN products p ON oi.product_id = p.id
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE oi.order_id = :order_id
                     ORDER BY oi.id ASC";

        $itemStmt = $this->db->prepare($itemsSql);
        $itemStmt->execute(['order_id' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Status History
        $order['history'] = $this->getOrderStatusHistory((int)$order['id']);

        return $order;
    }

    public function findByOrderNumber(string $orderNumber): ?array
    {
        $sql = "SELECT * FROM orders WHERE order_number = :order_number LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_number' => $orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        return $order ?: null;
    }

    public function cancelOrderWithTransaction(
        int $orderId,
        int $actorUserId,
        string $actorRole,
        ?string $reason = null
    ): array {
        $this->db->beginTransaction();

        try {
            // 1. Lock the order row to prevent concurrent race conditions
            $stmt = $this->db->prepare("SELECT id, user_id, status, payment_status FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new \Exception("Order not found.");
            }

            $currentStatus = $order['status'];

            // 2. Prevent repeated or invalid cancellations
            if ($currentStatus === 'CANCELLED') {
                throw new \Exception("Order is already cancelled.");
            }

            // 3. Check role-based cancellation eligibility
            if ($actorRole === 'CUSTOMER') {
                // Ensure customer owns the order
                if ((int)$order['user_id'] !== $actorUserId) {
                    throw new \Exception("Unauthorized: You may only cancel your own orders.");
                }

                // Customer cancellation is strictly allowed only in early stages
                if (!in_array($currentStatus, ['PENDING', 'CONFIRMED'], true)) {
                    throw new \Exception("Order cannot be cancelled once fulfilment processing has commenced (Current status: {$currentStatus}).");
                }
            } else {
                // Admin cancellation: not allowed if order is already delivered or returned
                if (in_array($currentStatus, ['DELIVERED', 'RETURNED'], true)) {
                    throw new \Exception("Cannot cancel an order that has already been delivered or returned.");
                }
            }

            // 4. Retrieve order items to restore stock
            $itemsStmt = $this->db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :order_id");
            $itemsStmt->execute(['order_id' => $orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            // 5. Restore stock for each item atomically
            $stockStmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :id");
            foreach ($items as $item) {
                $stockStmt->execute([
                    'qty' => (int)$item['quantity'],
                    'id' => (int)$item['product_id']
                ]);
            }

            // 6. Update order status
            $updateOrderStmt = $this->db->prepare("UPDATE orders SET status = 'CANCELLED', updated_at = NOW() WHERE id = :id");
            $updateOrderStmt->execute(['id' => $orderId]);

            // 7. Payment status: Unpaid COD orders remain unpaid/failed, never marked refunded or paid
            $paymentStmt = $this->db->prepare("UPDATE payments SET status = 'FAILED' WHERE order_id = :order_id AND status = 'PENDING'");
            $paymentStmt->execute(['order_id' => $orderId]);

            // 8. Record audit log entry in order_status_history
            $cancelReason = !empty($reason) ? trim($reason) : ($actorRole === 'CUSTOMER' ? 'Cancelled by customer' : 'Cancelled by administrator');
            $historyStmt = $this->db->prepare(
                "INSERT INTO order_status_history (order_id, previous_status, new_status, changed_by_user_id, actor_role, reason, created_at)
                 VALUES (:order_id, :prev_status, 'CANCELLED', :user_id, :role, :reason, NOW())"
            );
            $historyStmt->execute([
                'order_id' => $orderId,
                'prev_status' => $currentStatus,
                'user_id' => $actorUserId,
                'role' => $actorRole,
                'reason' => $cancelReason
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Order successfully cancelled and inventory stock restored.',
                'order_id' => $orderId,
                'previous_status' => $currentStatus,
                'new_status' => 'CANCELLED'
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateOrderStatusWithTransaction(
        int $orderId,
        string $newStatus,
        int $actorUserId,
        string $actorRole,
        ?string $reason = null,
        ?string $newPaymentStatus = null
    ): array {
        $validStatuses = ['PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'OUT_FOR_DELIVERY', 'DELIVERED', 'CANCELLED', 'DELIVERY_FAILED', 'RETURNED'];
        if (!in_array($newStatus, $validStatuses, true)) {
            return [
                'success' => false,
                'message' => "Invalid order status '{$newStatus}'."
            ];
        }

        // Only ADMIN can perform general lifecycle transitions
        if ($actorRole !== 'ADMIN') {
            return [
                'success' => false,
                'message' => 'Unauthorized: Only store administrators can perform administrative status transitions.'
            ];
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("SELECT id, status, payment_status FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new \Exception("Order not found.");
            }

            $currentStatus = $order['status'];

            // Transition validation
            $allowedTransitions = [
                'PENDING' => ['CONFIRMED', 'CANCELLED'],
                'CONFIRMED' => ['PROCESSING', 'CANCELLED'],
                'PROCESSING' => ['SHIPPED', 'CANCELLED'],
                'SHIPPED' => ['OUT_FOR_DELIVERY', 'DELIVERY_FAILED', 'RETURNED'],
                'OUT_FOR_DELIVERY' => ['DELIVERED', 'DELIVERY_FAILED', 'RETURNED'],
                'DELIVERY_FAILED' => ['OUT_FOR_DELIVERY', 'RETURNED', 'CANCELLED'],
                'DELIVERED' => [],
                'CANCELLED' => [],
                'RETURNED' => []
            ];

            if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus], true)) {
                throw new \Exception("Invalid transition from '{$currentStatus}' to '{$newStatus}'.");
            }

            // Update delivered_at if transitioning to DELIVERED
            $deliveredAtSql = ($newStatus === 'DELIVERED') ? ', delivered_at = NOW()' : '';

            // Update order status
            $updateSql = "UPDATE orders SET status = :status {$deliveredAtSql}, updated_at = NOW() WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([
                'status' => $newStatus,
                'id' => $orderId
            ]);

            // Handle payment status if specified (e.g. marking COD as PAID upon delivery)
            if ($newPaymentStatus !== null && in_array($newPaymentStatus, ['PENDING', 'PAID', 'FAILED'], true)) {
                $payOrderStmt = $this->db->prepare("UPDATE orders SET payment_status = :p_status WHERE id = :id");
                $payOrderStmt->execute(['p_status' => $newPaymentStatus, 'id' => $orderId]);

                $pRecordStatus = ($newPaymentStatus === 'PAID') ? 'COMPLETED' : $newPaymentStatus;
                $payStmt = $this->db->prepare("UPDATE payments SET status = :p_status WHERE order_id = :id");
                $payStmt->execute(['p_status' => $pRecordStatus, 'id' => $orderId]);
            }

            // Audit history
            $historyStmt = $this->db->prepare(
                "INSERT INTO order_status_history (order_id, previous_status, new_status, changed_by_user_id, actor_role, reason, created_at)
                 VALUES (:order_id, :prev_status, :new_status, :user_id, :role, :reason, NOW())"
            );
            $historyStmt->execute([
                'order_id' => $orderId,
                'prev_status' => $currentStatus,
                'new_status' => $newStatus,
                'user_id' => $actorUserId,
                'role' => $actorRole,
                'reason' => $reason ?? "Status advanced to {$newStatus}"
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'previous_status' => $currentStatus,
                'new_status' => $newStatus
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
