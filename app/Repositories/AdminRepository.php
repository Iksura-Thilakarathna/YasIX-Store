<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class AdminRepository extends BaseRepository
{
    /**
     * Get aggregate statistics for the admin dashboard cards
     */
    public function getDashboardStats(): array
    {
        // 1. Order status counts
        $orderStmt = $this->db->query("
            SELECT 
                COUNT(*) AS total_orders,
                SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) AS pending_orders,
                SUM(CASE WHEN status IN ('CONFIRMED', 'PROCESSING') THEN 1 ELSE 0 END) AS processing_orders,
                SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) AS delivered_orders,
                SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled_orders,
                SUM(CASE WHEN status NOT IN ('CANCELLED', 'DELIVERY_FAILED') THEN total_amount ELSE 0 END) AS total_order_value,
                SUM(CASE WHEN status = 'DELIVERED' THEN total_amount ELSE 0 END) AS delivered_revenue
            FROM orders
        ");
        $orderMetrics = $orderStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 2. Customer count
        $customerStmt = $this->db->query("
            SELECT COUNT(*) AS total_customers 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.name = 'CUSTOMER'
        ");
        $customerCount = (int)($customerStmt->fetchColumn() ?: 0);

        // 3. Product inventory counts
        $productStmt = $this->db->query("
            SELECT 
                COUNT(*) AS total_products,
                SUM(CASE WHEN status = 'AVAILABLE' AND stock_quantity > 0 THEN 1 ELSE 0 END) AS active_products,
                SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= 5 THEN 1 ELSE 0 END) AS low_stock_products,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock_products,
                COALESCE(SUM(stock_quantity), 0) AS total_inventory_units
            FROM products
        ");
        $productMetrics = $productStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 4. Payment metrics (COD pending vs collected cash)
        $paymentStmt = $this->db->query("
            SELECT 
                COUNT(CASE WHEN status = 'PENDING' THEN 1 END) AS pending_cod_count,
                COALESCE(SUM(CASE WHEN status = 'PENDING' THEN amount ELSE 0 END), 0) AS pending_cod_amount,
                COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) AS collected_cod_count,
                COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN amount ELSE 0 END), 0) AS collected_cash_amount
            FROM payments
        ");
        $paymentMetrics = $paymentStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 5. Warranty claims count
        $warrantyStmt = $this->db->query("
            SELECT 
                COUNT(*) AS total_claims,
                SUM(CASE WHEN status IN ('SUBMITTED', 'UNDER_REVIEW') THEN 1 ELSE 0 END) AS open_claims,
                SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END) AS approved_claims,
                SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) AS resolved_claims
            FROM warranty_claims
        ");
        $warrantyMetrics = $warrantyStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_orders' => (int)($orderMetrics['total_orders'] ?? 0),
            'pending_orders' => (int)($orderMetrics['pending_orders'] ?? 0),
            'processing_orders' => (int)($orderMetrics['processing_orders'] ?? 0),
            'delivered_orders' => (int)($orderMetrics['delivered_orders'] ?? 0),
            'cancelled_orders' => (int)($orderMetrics['cancelled_orders'] ?? 0),
            'total_order_value' => (float)($orderMetrics['total_order_value'] ?? 0.0),
            'delivered_revenue' => (float)($orderMetrics['delivered_revenue'] ?? 0.0),
            
            'total_customers' => $customerCount,
            
            'total_products' => (int)($productMetrics['total_products'] ?? 0),
            'active_products' => (int)($productMetrics['active_products'] ?? 0),
            'low_stock_products' => (int)($productMetrics['low_stock_products'] ?? 0),
            'out_of_stock_products' => (int)($productMetrics['out_of_stock_products'] ?? 0),
            'total_inventory_units' => (int)($productMetrics['total_inventory_units'] ?? 0),
            
            'pending_cod_count' => (int)($paymentMetrics['pending_cod_count'] ?? 0),
            'pending_cod_amount' => (float)($paymentMetrics['pending_cod_amount'] ?? 0.0),
            'collected_cod_count' => (int)($paymentMetrics['collected_cod_count'] ?? 0),
            'collected_cash_amount' => (float)($paymentMetrics['collected_cash_amount'] ?? 0.0),
            
            'total_claims' => (int)($warrantyMetrics['total_claims'] ?? 0),
            'open_claims' => (int)($warrantyMetrics['open_claims'] ?? 0),
            'approved_claims' => (int)($warrantyMetrics['approved_claims'] ?? 0),
            'resolved_claims' => (int)($warrantyMetrics['resolved_claims'] ?? 0),
        ];
    }

    /**
     * Get recent orders for dashboard view
     */
    public function getRecentOrders(int $limit = 8): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                o.id,
                o.order_number,
                o.total_amount,
                o.status,
                o.payment_method,
                o.payment_status,
                o.created_at,
                u.name AS customer_name,
                u.email AS customer_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get products running low on stock or out of stock
     */
    public function getLowStockAlerts(int $threshold = 5, int $limit = 8): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.name,
                p.brand,
                p.price,
                p.stock_quantity,
                p.status,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock_quantity <= :threshold
            ORDER BY p.stock_quantity ASC, p.name ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get top-selling products based on recorded order items
     */
    public function getTopSellingProducts(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                oi.product_id,
                oi.product_name,
                SUM(oi.quantity) AS total_units_sold,
                SUM(oi.subtotal) AS total_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status NOT IN ('CANCELLED', 'DELIVERY_FAILED')
            GROUP BY oi.product_id, oi.product_name
            ORDER BY total_units_sold DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get order distribution grouped by status
     */
    public function getOrdersByStatus(): array
    {
        $stmt = $this->db->query("
            SELECT status, COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS total_amount
            FROM orders
            GROUP BY status
            ORDER BY count DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
