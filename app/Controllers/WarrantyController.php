<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\WarrantyService;
use App\Helpers\Session;
use App\Helpers\CSRF;
use App\Helpers\View;

class WarrantyController extends BaseController
{
    private WarrantyService $warrantyService;

    public function __construct(?WarrantyService $warrantyService = null)
    {
        $this->warrantyService = $warrantyService ?? new WarrantyService();
    }

    /**
     * Customer Hardware Warranty Dashboard
     */
    public function index(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $warranties = $this->warrantyService->getUserWarranties($userId);

        View::render('warranties/index', [
            'title' => 'My Hardware Warranties // YasIX Coverage Registry',
            'warranties' => $warranties,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ]);
    }

    /**
     * Customer Submitted Warranty Claims List
     */
    public function claims(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $claims = $this->warrantyService->getUserClaims($userId);

        View::render('warranties/claims', [
            'title' => 'Warranty Claims // YasIX Support Terminal',
            'claims' => $claims,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ]);
    }

    /**
     * Submit Warranty Claim Form for an eligible order item
     */
    public function createForm(string $orderItemId): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');
        $itemId = (int)$orderItemId;

        $eligibility = $this->warrantyService->checkEligibility($itemId, $userId);

        if (!$eligibility['is_eligible']) {
            Session::setFlash('error', $eligibility['message']);
            header('Location: ' . getenv('APP_URL') . '/warranties');
            exit;
        }

        View::render('warranties/create', [
            'title' => 'Submit Warranty Claim // ' . e($eligibility['item']['product_name']),
            'item' => $eligibility['item'],
            'eligibility' => $eligibility,
            'reasons' => WarrantyService::PERMITTED_REASONS,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ]);
    }

    /**
     * Handle Claim Submission POST
     */
    public function submit(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Please try submitting again.');
            header('Location: ' . getenv('APP_URL') . '/warranties');
            exit;
        }

        $orderItemId = (int)($_POST['order_item_id'] ?? 0);
        if ($orderItemId <= 0) {
            Session::setFlash('error', 'Invalid hardware unit specified for warranty claim.');
            header('Location: ' . getenv('APP_URL') . '/warranties');
            exit;
        }

        $result = $this->warrantyService->submitClaim($orderItemId, $userId, $_POST);

        if (!$result['success']) {
            Session::setFlash('error', $result['message']);
            header('Location: ' . getenv('APP_URL') . '/warranties/claim/new/' . $orderItemId);
            exit;
        }

        Session::setFlash('success', 'Warranty claim #' . $result['claim_number'] . ' successfully submitted! Our hardware inspection team will evaluate your telemetry.');
        header('Location: ' . getenv('APP_URL') . '/warranties/claim/' . urlencode($result['claim_number']));
        exit;
    }

    /**
     * View Detailed Warranty Claim Status & Resolution
     */
    public function detail(string $claimNumber): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');

        $claim = $this->warrantyService->getClaimDetails($claimNumber, $userId);

        if (!$claim) {
            (new ErrorController())->notFound();
            return;
        }

        View::render('warranties/detail', [
            'title' => 'Claim #' . e($claim['claim_number']) . ' // YasIX Support Terminal',
            'claim' => $claim,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error')
        ]);
    }
}
