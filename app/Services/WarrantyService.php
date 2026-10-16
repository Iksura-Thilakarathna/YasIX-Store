<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\WarrantyRepository;

class WarrantyService extends BaseService
{
    private WarrantyRepository $warrantyRepository;

    public const PERMITTED_REASONS = [
        'Product not functioning',
        'Hardware defect',
        'Battery issue',
        'Charging issue',
        'Damaged product discovered after delivery',
        'Other'
    ];

    public const CLAIM_STATUS_SUBMITTED = 'SUBMITTED';
    public const CLAIM_STATUS_UNDER_REVIEW = 'UNDER_REVIEW';
    public const CLAIM_STATUS_APPROVED = 'APPROVED';
    public const CLAIM_STATUS_REJECTED = 'REJECTED';
    public const CLAIM_STATUS_RESOLVED = 'RESOLVED';
    public const CLAIM_STATUS_CLOSED = 'CLOSED';

    public function __construct(?WarrantyRepository $warrantyRepository = null)
    {
        $this->warrantyRepository = $warrantyRepository ?? new WarrantyRepository();
    }

    /**
     * Check warranty eligibility with strict business rule evaluation
     */
    public function checkEligibility(int $orderItemId, int $userId): array
    {
        if ($userId <= 0) {
            return [
                'is_eligible' => false,
                'status' => 'UNAUTHENTICATED',
                'message' => 'Customer must be authenticated to check warranty eligibility.'
            ];
        }

        $item = $this->warrantyRepository->getOrderItemWithWarranty($orderItemId, $userId);

        if (!$item) {
            return [
                'is_eligible' => false,
                'status' => 'NOT_FOUND',
                'message' => 'Hardware purchase not found or does not belong to your account.'
            ];
        }

        $warrantyMonths = (int)($item['warranty_months'] ?? 0);

        // 1. Check if product was sold with a warranty
        if ($warrantyMonths <= 0) {
            return [
                'is_eligible' => false,
                'status' => 'NO_WARRANTY',
                'message' => 'This product was purchased without warranty coverage (0 months duration).',
                'item' => $item
            ];
        }

        // 2. Check if order has a legitimate recorded delivery date
        if (empty($item['delivered_at']) || $item['order_status'] !== 'DELIVERED') {
            return [
                'is_eligible' => false,
                'status' => 'UNDELIVERED',
                'message' => 'Warranty commences upon actual courier doorstep delivery. This order has not been recorded as delivered.',
                'item' => $item
            ];
        }

        // 3. Compute warranty dates based on delivery date
        $deliveredTimestamp = strtotime($item['delivered_at']);
        $startDateStr = date('Y-m-d', $deliveredTimestamp);
        // Expiry date calculation: start date + X months
        $endDateStr = date('Y-m-d', strtotime("+{$warrantyMonths} months", $deliveredTimestamp));
        $expiryTimestamp = strtotime($endDateStr . ' 23:59:59');

        $currentTimestamp = time();

        // 4. Expiry boundary check
        if ($currentTimestamp > $expiryTimestamp) {
            return [
                'is_eligible' => false,
                'status' => 'EXPIRED',
                'message' => "Warranty coverage of {$warrantyMonths} months expired on " . date('F j, Y', $expiryTimestamp) . ".",
                'start_date' => $startDateStr,
                'end_date' => $endDateStr,
                'item' => $item
            ];
        }

        // 5. Check if an active open claim is already in progress
        if (!empty($item['active_claim_id'])) {
            return [
                'is_eligible' => false,
                'status' => 'CLAIM_ACTIVE',
                'message' => "An active warranty claim ({$item['active_claim_number']}) is already in progress (Status: {$item['active_claim_status']}).",
                'start_date' => $startDateStr,
                'end_date' => $endDateStr,
                'active_claim_number' => $item['active_claim_number'],
                'item' => $item
            ];
        }

        $daysRemaining = max(0, (int)ceil(($expiryTimestamp - $currentTimestamp) / 86400));

        return [
            'is_eligible' => true,
            'status' => 'ELIGIBLE',
            'message' => "Hardware is eligible for warranty claim ({$daysRemaining} days remaining).",
            'start_date' => $startDateStr,
            'end_date' => $endDateStr,
            'warranty_months' => $warrantyMonths,
            'days_remaining' => $daysRemaining,
            'item' => $item
        ];
    }

    /**
     * Submit a customer warranty claim
     */
    public function submitClaim(int $orderItemId, int $userId, array $formData): array
    {
        // 1. Verify eligibility on server
        $eligibility = $this->checkEligibility($orderItemId, $userId);
        if (!$eligibility['is_eligible']) {
            return [
                'success' => false,
                'message' => $eligibility['message']
            ];
        }

        $item = $eligibility['item'];

        // 2. Validate claim reason
        $reason = trim($formData['claim_reason'] ?? '');
        if (!in_array($reason, self::PERMITTED_REASONS, true)) {
            return [
                'success' => false,
                'message' => 'Please select a valid claim reason from the approved options.'
            ];
        }

        // 3. Validate description
        $description = trim($formData['issue_description'] ?? '');
        if (strlen($description) < 10) {
            return [
                'success' => false,
                'message' => 'Please provide a detailed description of the issue (at least 10 characters).'
            ];
        }

        if (strlen($description) > 3000) {
            return [
                'success' => false,
                'message' => 'Issue description cannot exceed 3,000 characters.'
            ];
        }

        // 4. Find or create registered warranty record
        $warrantyId = $this->warrantyRepository->findOrCreateWarranty(
            $orderItemId,
            $userId,
            (int)$item['product_id'],
            $eligibility['start_date'],
            $eligibility['end_date']
        );

        // 5. Generate secure claim number
        $claimNumber = 'CLM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        // 6. Create claim in database with transaction
        $result = $this->warrantyRepository->createClaimWithTransaction(
            $warrantyId,
            $userId,
            $claimNumber,
            $reason,
            $description
        );

        return $result;
    }

    /**
     * Retrieve all customer purchases with warranty status
     */
    public function getUserWarranties(int $userId): array
    {
        $items = $this->warrantyRepository->getUserPurchasedItems($userId);

        $warranties = [];
        foreach ($items as $item) {
            $eligibility = $this->checkEligibility((int)$item['order_item_id'], $userId);
            $item['eligibility'] = $eligibility;
            $item['status_meta'] = $this->getEligibilityMetadata($eligibility['status']);
            $warranties[] = $item;
        }

        return $warranties;
    }

    /**
     * Retrieve all customer submitted claims
     */
    public function getUserClaims(int $userId): array
    {
        $claims = $this->warrantyRepository->getUserClaims($userId);

        foreach ($claims as &$claim) {
            $claim['status_meta'] = $this->getClaimStatusMetadata($claim['status']);
            $claim['formatted_date'] = date('M d, Y - h:i A', strtotime($claim['created_at']));
            $claim['formatted_start_date'] = date('M d, Y', strtotime($claim['start_date']));
            $claim['formatted_end_date'] = date('M d, Y', strtotime($claim['end_date']));
        }
        unset($claim);

        return $claims;
    }

    /**
     * Retrieve single claim details ensuring customer ownership
     */
    public function getClaimDetails(string $claimNumber, int $userId): ?array
    {
        $claim = $this->warrantyRepository->getClaimByNumber($claimNumber, $userId);

        if (!$claim) {
            return null;
        }

        $claim['status_meta'] = $this->getClaimStatusMetadata($claim['status']);
        $claim['formatted_date'] = date('F j, Y - h:i A', strtotime($claim['created_at']));
        $claim['formatted_updated_date'] = date('F j, Y - h:i A', strtotime($claim['updated_at']));
        $claim['formatted_start_date'] = date('F j, Y', strtotime($claim['start_date']));
        $claim['formatted_end_date'] = date('F j, Y', strtotime($claim['end_date']));

        return $claim;
    }

    /**
     * Admin claim review decision foundation
     */
    public function updateClaimStatus(
        int $claimId,
        string $status,
        int $adminUserId,
        string $adminRole = 'ADMIN',
        ?string $decision = null,
        ?string $resolution = null
    ): array {
        return $this->warrantyRepository->updateClaimStatusWithTransaction(
            $claimId,
            $status,
            $adminUserId,
            $adminRole,
            $decision,
            $resolution
        );
    }

    /**
     * Helper for warranty eligibility badge presentation
     */
    public function getEligibilityMetadata(string $status): array
    {
        $map = [
            'ELIGIBLE' => [
                'label' => 'Active & Eligible',
                'class' => 'status-eligible',
                'badge_style' => 'background: rgba(0, 255, 136, 0.15); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.4);',
                'icon' => 'fa-shield-halved'
            ],
            'EXPIRED' => [
                'label' => 'Warranty Expired',
                'class' => 'status-expired',
                'badge_style' => 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);',
                'icon' => 'fa-calendar-xmark'
            ],
            'UNDELIVERED' => [
                'label' => 'Awaiting Delivery',
                'class' => 'status-undelivered',
                'badge_style' => 'background: rgba(255, 215, 0, 0.15); color: #ffd700; border: 1px solid rgba(255, 215, 0, 0.4);',
                'icon' => 'fa-truck-clock'
            ],
            'NO_WARRANTY' => [
                'label' => 'No Warranty',
                'class' => 'status-no-warranty',
                'badge_style' => 'background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.4);',
                'icon' => 'fa-shield-xmark'
            ],
            'CLAIM_ACTIVE' => [
                'label' => 'Claim in Progress',
                'class' => 'status-claimed',
                'badge_style' => 'background: rgba(184, 41, 247, 0.15); color: #c084fc; border: 1px solid rgba(184, 41, 247, 0.4);',
                'icon' => 'fa-clipboard-question'
            ]
        ];

        return $map[$status] ?? [
            'label' => 'Status Pending',
            'class' => 'status-unknown',
            'badge_style' => 'background: rgba(255, 255, 255, 0.1); color: #fff;',
            'icon' => 'fa-circle-question'
        ];
    }

    /**
     * Helper for claim status badge presentation
     */
    public function getClaimStatusMetadata(string $status): array
    {
        $map = [
            self::CLAIM_STATUS_SUBMITTED => [
                'label' => 'Submitted',
                'badge_style' => 'background: rgba(255, 215, 0, 0.15); color: #ffd700; border: 1px solid rgba(255, 215, 0, 0.4);',
                'icon' => 'fa-inbox'
            ],
            self::CLAIM_STATUS_UNDER_REVIEW => [
                'label' => 'Under Review',
                'badge_style' => 'background: rgba(0, 245, 255, 0.15); color: var(--cyan); border: 1px solid var(--cyan);',
                'icon' => 'fa-magnifying-glass'
            ],
            self::CLAIM_STATUS_APPROVED => [
                'label' => 'Approved',
                'badge_style' => 'background: rgba(0, 255, 136, 0.15); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.4);',
                'icon' => 'fa-circle-check'
            ],
            self::CLAIM_STATUS_REJECTED => [
                'label' => 'Rejected',
                'badge_style' => 'background: rgba(255, 0, 85, 0.15); color: #ff3377; border: 1px solid rgba(255, 0, 85, 0.4);',
                'icon' => 'fa-circle-xmark'
            ],
            self::CLAIM_STATUS_RESOLVED => [
                'label' => 'Resolved',
                'badge_style' => 'background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4);',
                'icon' => 'fa-wrench'
            ],
            self::CLAIM_STATUS_CLOSED => [
                'label' => 'Closed',
                'badge_style' => 'background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.4);',
                'icon' => 'fa-lock'
            ]
        ];

        return $map[$status] ?? [
            'label' => ucfirst(strtolower($status)),
            'badge_style' => 'background: rgba(255, 255, 255, 0.1); color: #fff;',
            'icon' => 'fa-circle-question'
        ];
    }
}
