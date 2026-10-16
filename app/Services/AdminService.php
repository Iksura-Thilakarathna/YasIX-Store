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
