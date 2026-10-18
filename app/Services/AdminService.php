<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdminRepository;
use App\Helpers\Session;

class AdminService extends BaseService
{
    private AdminRepository $adminRepository;

    public function __construct()
    {
        $this->adminRepository = new AdminRepository();
    }

    /**
     * Compile complete metrics payload for the admin dashboard view
     */
    public function getDashboardData(): array
    {
        $stats = $this->adminRepository->getDashboardStats();
        $recentOrders = $this->adminRepository->getRecentOrders(8);
        $lowStock = $this->adminRepository->getLowStockAlerts(5, 8);
        $topSelling = $this->adminRepository->getTopSellingProducts(5);
        $ordersByStatus = $this->adminRepository->getOrdersByStatus();

        return [
            'stats' => $stats,
            'recent_orders' => $recentOrders,
            'low_stock_items' => $lowStock,
            'top_selling' => $topSelling,
            'orders_by_status' => $ordersByStatus
        ];
    }

    /**
     * Retrieve aggregated report data
     */
    public function getReportData(array $params): array
    {
        $period = trim($params['period'] ?? '30days');
        $validPeriods = ['today', '7days', '30days', 'ytd', 'all', 'custom'];
        if (!in_array($period, $validPeriods, true)) {
            $period = '30days';
        }

        $dateFrom = !empty($params['date_from']) ? trim($params['date_from']) : null;
        $dateTo = !empty($params['date_to']) ? trim($params['date_to']) : null;

        return $this->adminRepository->getReportData($period, $dateFrom, $dateTo);
    }

    /**
     * Export sanitized CSV dataset
     */
    public function exportCsv(string $type, array $params = []): array
    {
        $dateFrom = !empty($params['date_from']) ? trim($params['date_from']) : null;
        $dateTo = !empty($params['date_to']) ? trim($params['date_to']) : null;

        switch (strtolower(trim($type))) {
            case 'orders':
                $filename = 'yasix_orders_export_' . date('Ymd_His') . '.csv';
                $headers = ['Order Number', 'Date Placed', 'Customer Name', 'Customer Email', 'Total Amount (LKR)', 'Shipping Fee (LKR)', 'Fulfilment Status', 'Payment Method', 'Payment Status', 'Delivered At'];
                $rows = $this->adminRepository->getOrdersForExport($dateFrom, $dateTo);
                break;

            case 'inventory':
                $filename = 'yasix_inventory_export_' . date('Ymd_His') . '.csv';
                $headers = ['SKU ID', 'Product Name', 'Brand', 'Category', 'Unit Price (LKR)', 'Stock Quantity', 'Warranty Months', 'Status'];
                $rows = $this->adminRepository->getInventoryForExport();
                break;

            case 'payments':
                $filename = 'yasix_cod_payments_export_' . date('Ymd_His') . '.csv';
                $headers = ['Payment ID', 'Order Number', 'Customer Name', 'Amount (LKR)', 'Payment Method', 'Payment Status', 'Collected At', 'Collected By Admin', 'Audit Notes'];
                $rows = $this->adminRepository->getPaymentsForExport();
                break;

            case 'warranties':
                $filename = 'yasix_warranty_claims_export_' . date('Ymd_His') . '.csv';
                $headers = ['Claim Number', 'Date Lodged', 'Customer Name', 'Product Hardware', 'Order Number', 'Issue Reason', 'Claim Status', 'Admin Decision', 'Reviewer', 'Resolved At'];
                $rows = $this->adminRepository->getWarrantyClaimsForExport();
                break;

            default:
                throw new \InvalidArgumentException("Invalid export type '{$type}'.");
        }

        $content = $this->generateCsvString($headers, $rows);

        return [
            'filename' => $filename,
            'content' => $content
        ];
    }

    /**
     * Neutralize CSV formula injection attacks
     */
    public function sanitizeCsvCell(mixed $value): string
    {
        if ($value === null) {
            return '""';
        }

        $str = (string)$value;

        // Formula Injection Defense:
        // Excel / Calc interpret cells beginning with =, +, -, @, \t, \r as executable formulas.
        // We neutralize this by prefixing with an apostrophe (').
        if (preg_match('/^[\=\+\-\@\t\r]/', $str)) {
            $str = "'" . $str;
        }

        return '"' . str_replace('"', '""', $str) . '"';
    }

    public function generateCsvString(array $headers, array $rows): string
    {
        $lines = [];

        // Header line
        $headerCells = array_map([$this, 'sanitizeCsvCell'], $headers);
        $lines[] = implode(',', $headerCells);

        // Data lines
        foreach ($rows as $row) {
            $rowCells = array_map([$this, 'sanitizeCsvCell'], array_values($row));
            $lines[] = implode(',', $rowCells);
        }

        return implode("\r\n", $lines);
    }

    /**
     * Ensure current session belongs to an active administrator
     */
    public function isCurrentAdmin(): bool
    {
        Session::start();
        if (!Session::has('user_id')) {
            return false;
        }
        $role = strtoupper((string)Session::get('role_name', ''));
        return $role === 'ADMIN';
    }
}
