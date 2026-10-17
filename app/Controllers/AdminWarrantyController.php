<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\WarrantyService;
use App\Helpers\Session;
use App\Helpers\CSRF;

class AdminWarrantyController extends BaseController
{
    private WarrantyService $warrantyService;

    public function __construct()
    {
        $this->warrantyService = new WarrantyService();
    }

    public function index(): void
    {
        $data = $this->warrantyService->getAdminClaims($_GET);

        $this->render('admin/warranties/index', [
            'title' => 'Warranty Claims Administration | YasIX Admin',
            'headerTitle' => 'RMA & Warranty Claims Adjudication',
            'claims' => $data['claims'],
            'filters' => $data['filters'],
            'pagination' => $data['pagination']
        ], 'admin');
    }

    public function detail(string $id): void
    {
        $claimId = (int)$id;
        $claim = $this->warrantyService->getAdminClaimById($claimId);

        if (!$claim) {
            Session::setFlash('error', "Warranty claim #{$claimId} not found in claims registry.");
            header('Location: ' . getenv('APP_URL') . '/admin/warranties');
            exit;
        }

        // Define valid transitions for the admin interface
        $allowedTransitions = [
            WarrantyService::CLAIM_STATUS_SUBMITTED => [
                WarrantyService::CLAIM_STATUS_UNDER_REVIEW,
                WarrantyService::CLAIM_STATUS_APPROVED,
                WarrantyService::CLAIM_STATUS_REJECTED
            ],
            WarrantyService::CLAIM_STATUS_UNDER_REVIEW => [
                WarrantyService::CLAIM_STATUS_APPROVED,
                WarrantyService::CLAIM_STATUS_REJECTED
            ],
            WarrantyService::CLAIM_STATUS_APPROVED => [
                WarrantyService::CLAIM_STATUS_RESOLVED,
                WarrantyService::CLAIM_STATUS_CLOSED
            ],
            WarrantyService::CLAIM_STATUS_REJECTED => [
                WarrantyService::CLAIM_STATUS_UNDER_REVIEW
            ],
            WarrantyService::CLAIM_STATUS_RESOLVED => [
                WarrantyService::CLAIM_STATUS_CLOSED
            ],
            WarrantyService::CLAIM_STATUS_CLOSED => []
        ];

        $nextPossibleStatuses = $allowedTransitions[$claim['status']] ?? [];

        $this->render('admin/warranties/detail', [
            'title' => "Claim {$claim['claim_number']} | YasIX Admin",
            'headerTitle' => "Claim Terminal: {$claim['claim_number']}",
            'claim' => $claim,
            'nextStatuses' => $nextPossibleStatuses
        ], 'admin');
    }

    public function decide(string $id): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Adjudication rejected.');
            header('Location: ' . getenv('APP_URL') . '/admin/warranties/' . urlencode($id));
            exit;
        }

        $claimId = (int)$id;
        $adminUser = Session::get('user');
        $adminId = (int)($adminUser['id'] ?? 0);

        $newStatus = trim($_POST['status'] ?? '');
        $adminDecision = trim($_POST['admin_decision'] ?? '');
        $resolutionDetails = trim($_POST['resolution_details'] ?? '');
        $internalNotes = trim($_POST['internal_notes'] ?? '');

        $result = $this->warrantyService->updateClaimStatus(
            $claimId,
            $newStatus,
            $adminId,
            'ADMIN',
            $adminDecision ?: null,
            $resolutionDetails ?: null,
            $internalNotes ?: null
        );

        if ($result['success']) {
            Session::setFlash('success', "Warranty claim adjudication saved successfully. Status is now {$newStatus}.");
        } else {
            Session::setFlash('error', "Adjudication failed: " . ($result['message'] ?? 'Unknown error.'));
        }

        header('Location: ' . getenv('APP_URL') . '/admin/warranties/' . urlencode($id));
        exit;
    }
}
