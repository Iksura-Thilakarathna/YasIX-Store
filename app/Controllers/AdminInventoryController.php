<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProductService;
use App\Helpers\Session;
use App\Helpers\CSRF;

class AdminInventoryController extends BaseController
{
    private ProductService $productService;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    public function index(): void
    {
        $productsData = $this->productService->getAdminProducts($_GET);
        $logs = $this->productService->getInventoryLogs(null, 25);

        $this->render('admin/inventory/index', [
            'title' => 'Warehouse & Inventory Control | YasIX Admin',
            'headerTitle' => 'Warehouse Stock Balances & Inventory Audit Log',
            'products' => $productsData['products'],
            'filters' => $productsData['filters'],
            'pagination' => $productsData['pagination'],
            'logs' => $logs
        ], 'admin');
    }

    public function adjust(): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/admin/inventory');
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $newStock = isset($_POST['new_stock']) ? (int)$_POST['new_stock'] : -1;
        $reason = trim($_POST['reason'] ?? '');
        $userId = (int)Session::get('user_id');

        if ($productId <= 0) {
            Session::setFlash('error', 'Invalid product SKU selection.');
            header('Location: ' . getenv('APP_URL') . '/admin/inventory');
            exit;
        }

        if ($newStock < 0) {
            Session::setFlash('error', 'Stock quantity cannot be negative.');
            header('Location: ' . getenv('APP_URL') . '/admin/inventory');
            exit;
        }

        if (empty($reason)) {
            Session::setFlash('error', 'An audit reason is mandatory for inventory adjustments.');
            header('Location: ' . getenv('APP_URL') . '/admin/inventory');
            exit;
        }

        $result = $this->productService->adjustStock($productId, $newStock, $userId, $reason);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
        } else {
            $changeStr = ($result['change'] >= 0 ? '+' : '') . $result['change'];
            Session::setFlash('success', "Stock updated successfully for SKU #{$productId}: Previous: {$result['previous_stock']}, New: {$result['new_stock']} ({$changeStr} units). Reason logged.");
        }

        header('Location: ' . getenv('APP_URL') . '/admin/inventory');
        exit;
    }
}
