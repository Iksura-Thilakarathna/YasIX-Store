<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AdminService;
use App\Helpers\Session;

class AdminReportController extends BaseController
{
    private AdminService $adminService;

    public function __construct()
    {
        $this->adminService = new AdminService();
    }

    public function index(): void
    {
        $reportData = $this->adminService->getReportData($_GET);

        $this->render('admin/reports/index', [
            'title' => 'Store Performance & Reports | YasIX Admin',
            'headerTitle' => 'Store Intelligence & Reporting Matrix',
            'report' => $reportData,
            'filters' => [
                'period' => $_GET['period'] ?? '30days',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? ''
            ]
        ], 'admin');
    }

    public function export(): void
    {
        $type = trim($_GET['type'] ?? 'orders');

        try {
            $export = $this->adminService->exportCsv($type, $_GET);

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo $export['content'];
            exit;
        } catch (\Throwable $e) {
            Session::start();
            Session::setFlash('error', "Export failed: " . $e->getMessage());
            header('Location: ' . getenv('APP_URL') . '/admin/reports');
            exit;
        }
    }
}
