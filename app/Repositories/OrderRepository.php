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

            // 4. Insert Order Items (with historical price snapshot)
            $itemStmt = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal, created_at)
                 VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity, :subtotal, NOW())"
            );

            foreach ($orderItems as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => (int)$item['product_id'],
                    'product_name' => $item['product_name'],
                    'unit_price' => (float)$item['unit_price'],
                    'quantity' => (int)$item['quantity'],
                    'subtotal' => (float)$item['subtotal']
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

            // 6. Clear Cart Items
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

        // Fetch Order Items
        $itemsSql = "SELECT oi.*, p.slug AS product_slug, c.name AS category_name, c.icon_class AS category_icon
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     JOIN categories c ON p.category_id = c.id
                     WHERE oi.order_id = :order_id
                     ORDER BY oi.id ASC";

        $itemStmt = $this->db->prepare($itemsSql);
        $itemStmt->execute(['order_id' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

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
}
