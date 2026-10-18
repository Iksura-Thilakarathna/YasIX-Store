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

    /**
     * Get report data aggregated by period
     */
    public function getReportData(string $period = 'all', ?string $dateFrom = null, ?string $dateTo = null): array
    {
        [$whereClauseOrders, $params] = $this->buildPeriodFilter($period, $dateFrom, $dateTo);

        // Period Summary
        $summarySql = "
            SELECT 
                COUNT(*) AS period_orders,
                SUM(CASE WHEN o.status = 'DELIVERED' THEN 1 ELSE 0 END) AS delivered_orders,
                SUM(CASE WHEN o.status = 'DELIVERED' THEN o.total_amount ELSE 0 END) AS delivered_revenue,
                SUM(CASE WHEN o.status NOT IN ('CANCELLED', 'DELIVERY_FAILED') THEN o.total_amount ELSE 0 END) AS total_order_value,
                SUM(CASE WHEN o.status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled_orders
            FROM orders o
            {$whereClauseOrders}
        ";
        $stmt = $this->db->prepare($summarySql);
        $stmt->execute($params);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Payments in period
        $paySql = "
            SELECT 
                COALESCE(SUM(CASE WHEN p.status = 'COMPLETED' THEN p.amount ELSE 0 END), 0) AS collected_cod_cash,
                COALESCE(SUM(CASE WHEN p.status = 'PENDING' THEN p.amount ELSE 0 END), 0) AS pending_cod_cash,
                COUNT(CASE WHEN p.status = 'COMPLETED' THEN 1 END) AS completed_payments_count
            FROM payments p
            JOIN orders o ON p.order_id = o.id
            {$whereClauseOrders}
        ";
        $payStmt = $this->db->prepare($paySql);
        $payStmt->execute($params);
        $payData = $payStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Order distribution by status in period
        $distSql = "
            SELECT o.status, COUNT(*) AS count, COALESCE(SUM(o.total_amount), 0) AS total_amount
            FROM orders o
            {$whereClauseOrders}
            GROUP BY o.status
            ORDER BY count DESC
        ";
        $distStmt = $this->db->prepare($distSql);
        $distStmt->execute($params);
        $statusDistribution = $distStmt->fetchAll(PDO::FETCH_ASSOC);

        // Top products in period
        $topProdSql = "
            SELECT oi.product_id, oi.product_name, SUM(oi.quantity) AS total_units_sold, SUM(oi.subtotal) AS total_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            {$whereClauseOrders} AND o.status NOT IN ('CANCELLED', 'DELIVERY_FAILED')
            GROUP BY oi.product_id, oi.product_name
            ORDER BY total_units_sold DESC
            LIMIT 10
        ";
        $topProdStmt = $this->db->prepare($topProdSql);
        $topProdStmt->execute($params);
        $topProducts = $topProdStmt->fetchAll(PDO::FETCH_ASSOC);

        // Warranty claims count in period
        $claimsWhere = str_replace('o.created_at', 'wc.created_at', $whereClauseOrders);
        $claimsSql = "
            SELECT COUNT(*) AS total_claims,
                   SUM(CASE WHEN wc.status = 'APPROVED' THEN 1 ELSE 0 END) AS approved_claims,
                   SUM(CASE WHEN wc.status = 'RESOLVED' THEN 1 ELSE 0 END) AS resolved_claims
            FROM warranty_claims wc
            {$claimsWhere}
        ";
        $claimsStmt = $this->db->prepare($claimsSql);
        $claimsStmt->execute($params);
        $claimsData = $claimsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'period' => $period,
            'summary' => [
                'period_orders' => (int)($summary['period_orders'] ?? 0),
                'delivered_orders' => (int)($summary['delivered_orders'] ?? 0),
                'delivered_revenue' => (float)($summary['delivered_revenue'] ?? 0.0),
                'total_order_value' => (float)($summary['total_order_value'] ?? 0.0),
                'cancelled_orders' => (int)($summary['cancelled_orders'] ?? 0),
                'collected_cod_cash' => (float)($payData['collected_cod_cash'] ?? 0.0),
                'pending_cod_cash' => (float)($payData['pending_cod_cash'] ?? 0.0),
                'completed_payments_count' => (int)($payData['completed_payments_count'] ?? 0),
                'total_claims' => (int)($claimsData['total_claims'] ?? 0),
                'approved_claims' => (int)($claimsData['approved_claims'] ?? 0),
                'resolved_claims' => (int)($claimsData['resolved_claims'] ?? 0),
            ],
            'status_distribution' => $statusDistribution,
            'top_products' => $topProducts
        ];
    }

    private function buildPeriodFilter(string $period, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $conditions = ["1=1"];
        $params = [];

        switch ($period) {
            case 'today':
                $conditions[] = "DATE(o.created_at) = CURDATE()";
                break;
            case '7days':
                $conditions[] = "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case '30days':
                $conditions[] = "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'ytd':
                $conditions[] = "o.created_at >= MAKEDATE(YEAR(CURDATE()), 1)";
                break;
            case 'custom':
                if (!empty($dateFrom)) {
                    $conditions[] = "o.created_at >= :date_from";
                    $params['date_from'] = trim($dateFrom) . ' 00:00:00';
                }
                if (!empty($dateTo)) {
                    $conditions[] = "o.created_at <= :date_to";
                    $params['date_to'] = trim($dateTo) . ' 23:59:59';
                }
                break;
            case 'all':
            default:
                break;
        }

        return ["WHERE " . implode(" AND ", $conditions), $params];
    }

    public function getOrdersForExport(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $conditions = ["1=1"];
        $params = [];
        if (!empty($dateFrom)) {
            $conditions[] = "o.created_at >= :date_from";
            $params['date_from'] = trim($dateFrom) . ' 00:00:00';
        }
        if (!empty($dateTo)) {
            $conditions[] = "o.created_at <= :date_to";
            $params['date_to'] = trim($dateTo) . ' 23:59:59';
        }
        $where = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT o.order_number, o.created_at, u.name AS customer_name, u.email AS customer_email,
                       o.total_amount, o.shipping_fee, o.status AS order_status, o.payment_method,
                       o.payment_status, o.delivered_at
                FROM orders o
                JOIN users u ON o.user_id = u.id
                {$where}
                ORDER BY o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaymentsForExport(): array
    {
        $sql = "SELECT p.id AS payment_id, o.order_number, u.name AS customer_name, p.amount,
                       p.payment_method, p.status AS payment_status, p.collected_at,
                       u_col.name AS collected_by_admin, p.notes
                FROM payments p
                JOIN orders o ON p.order_id = o.id
                JOIN users u ON o.user_id = u.id
                LEFT JOIN users u_col ON p.collected_by_user_id = u_col.id
                ORDER BY p.id DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInventoryForExport(): array
    {
        $sql = "SELECT p.id AS sku_id, p.name AS product_name, p.brand, c.name AS category_name,
                       p.price, p.stock_quantity, p.warranty_months, p.status
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.id ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWarrantyClaimsForExport(): array
    {
        $sql = "SELECT wc.claim_number, wc.created_at, u.name AS customer_name, oi.product_name,
                       o.order_number, wc.claim_reason, wc.status, wc.admin_decision,
                       u_rev.name AS reviewer_name, wc.resolved_at
                FROM warranty_claims wc
                JOIN warranties w ON wc.warranty_id = w.id
                JOIN order_items oi ON w.order_item_id = oi.id
                JOIN orders o ON oi.order_id = o.id
                JOIN users u ON wc.user_id = u.id
                LEFT JOIN users u_rev ON wc.reviewed_by_user_id = u_rev.id
                ORDER BY wc.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
