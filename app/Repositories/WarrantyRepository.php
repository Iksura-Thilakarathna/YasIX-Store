<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;
use Exception;

class WarrantyRepository extends BaseRepository
{
    protected string $table = 'warranties';

    /**
     * Retrieve all purchased hardware items for a customer with warranty information
     */
    public function getUserPurchasedItems(int $userId): array
    {
        $sql = "SELECT oi.id AS order_item_id, oi.order_id, oi.product_id, oi.product_name, oi.unit_price, oi.quantity, oi.subtotal, oi.warranty_months,
                       o.order_number, o.status AS order_status, o.delivered_at, o.created_at AS order_date,
                       w.id AS warranty_id, w.serial_number, w.start_date AS warranty_start_date, w.end_date AS warranty_end_date, w.status AS warranty_record_status,
                       wc.id AS active_claim_id, wc.claim_number AS active_claim_number, wc.status AS active_claim_status,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = oi.product_id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                LEFT JOIN warranties w ON w.order_item_id = oi.id
                LEFT JOIN warranty_claims wc ON (wc.warranty_id = w.id AND wc.status NOT IN ('REJECTED', 'CLOSED'))
                WHERE o.user_id = :user_id
                ORDER BY o.created_at DESC, oi.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve a specific order item with order ownership and warranty details
     */
    public function getOrderItemWithWarranty(int $orderItemId, int $userId): ?array
    {
        $sql = "SELECT oi.id AS order_item_id, oi.order_id, oi.product_id, oi.product_name, oi.unit_price, oi.quantity, oi.subtotal, oi.warranty_months,
                       o.order_number, o.status AS order_status, o.delivered_at, o.created_at AS order_date, o.user_id,
                       u.name AS customer_name, u.email AS customer_email,
                       w.id AS warranty_id, w.serial_number, w.start_date AS warranty_start_date, w.end_date AS warranty_end_date, w.status AS warranty_record_status,
                       wc.id AS active_claim_id, wc.claim_number AS active_claim_number, wc.status AS active_claim_status,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = oi.product_id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN users u ON o.user_id = u.id
                LEFT JOIN warranties w ON w.order_item_id = oi.id
                LEFT JOIN warranty_claims wc ON (wc.warranty_id = w.id AND wc.status NOT IN ('REJECTED', 'CLOSED'))
                WHERE oi.id = :order_item_id AND o.user_id = :user_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_item_id' => $orderItemId,
            'user_id' => $userId
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Create or retrieve warranty registration record
     */
    public function findOrCreateWarranty(int $orderItemId, int $userId, int $productId, string $startDate, string $endDate): int
    {
        $findStmt = $this->db->prepare("SELECT id FROM warranties WHERE order_item_id = :order_item_id LIMIT 1");
        $findStmt->execute(['order_item_id' => $orderItemId]);
        $existingId = $findStmt->fetchColumn();

        if ($existingId) {
            return (int)$existingId;
        }

        $serialNumber = 'WAR-' . $orderItemId . '-' . strtoupper(substr(md5($orderItemId . '-' . time()), 0, 8));
        $insertStmt = $this->db->prepare(
            "INSERT INTO warranties (order_item_id, user_id, product_id, serial_number, start_date, end_date, status, created_at)
             VALUES (:order_item_id, :user_id, :product_id, :serial_number, :start_date, :end_date, 'ACTIVE', NOW())"
        );
        $insertStmt->execute([
            'order_item_id' => $orderItemId,
            'user_id' => $userId,
            'product_id' => $productId,
            'serial_number' => $serialNumber,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Create warranty claim atomically with duplicate active claim protection
     */
    public function createClaimWithTransaction(
        int $warrantyId,
        int $userId,
        string $claimNumber,
        string $claimReason,
        string $issueDescription
    ): array {
        $this->db->beginTransaction();

        try {
            // Check for existing active claim
            $checkStmt = $this->db->prepare(
                "SELECT id, claim_number, status FROM warranty_claims 
                 WHERE warranty_id = :warranty_id AND status NOT IN ('REJECTED', 'CLOSED') 
                 LIMIT 1 FOR UPDATE"
            );
            $checkStmt->execute(['warranty_id' => $warrantyId]);
            $existingClaim = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingClaim) {
                throw new Exception("An active warranty claim ({$existingClaim['claim_number']}) is already in progress for this item.");
            }

            // Insert warranty claim
            $insertStmt = $this->db->prepare(
                "INSERT INTO warranty_claims (warranty_id, user_id, claim_number, claim_reason, issue_description, status, admin_decision, created_at, updated_at)
                 VALUES (:warranty_id, :user_id, :claim_number, :claim_reason, :issue_description, 'SUBMITTED', 'PENDING', NOW(), NOW())"
            );
            $insertStmt->execute([
                'warranty_id' => $warrantyId,
                'user_id' => $userId,
                'claim_number' => $claimNumber,
                'claim_reason' => $claimReason,
                'issue_description' => $issueDescription
            ]);
            $claimId = (int)$this->db->lastInsertId();

            // Update warranty status to CLAIMED
            $updateWarrantyStmt = $this->db->prepare("UPDATE warranties SET status = 'CLAIMED' WHERE id = :id");
            $updateWarrantyStmt->execute(['id' => $warrantyId]);

            $this->db->commit();

            return [
                'success' => true,
                'claim_id' => $claimId,
                'claim_number' => $claimNumber
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve all claims submitted by a customer
     */
    public function getUserClaims(int $userId): array
    {
        $sql = "SELECT wc.*, 
                       w.serial_number, w.start_date, w.end_date,
                       oi.product_name, oi.unit_price, oi.warranty_months,
                       o.order_number, o.delivered_at
                FROM warranty_claims wc
                JOIN warranties w ON wc.warranty_id = w.id
                JOIN order_items oi ON w.order_item_id = oi.id
                JOIN orders o ON oi.order_id = o.id
                WHERE wc.user_id = :user_id
                ORDER BY wc.created_at DESC, wc.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve claim by claim number with authorization
     */
    public function getClaimByNumber(string $claimNumber, int $userId): ?array
    {
        $sql = "SELECT wc.*, 
                       w.serial_number, w.start_date, w.end_date, w.status AS warranty_status,
                       oi.id AS order_item_id, oi.product_name, oi.unit_price, oi.warranty_months,
                       o.id AS order_id, o.order_number, o.delivered_at, o.user_id AS order_user_id,
                       u.name AS customer_name, u.email AS customer_email
                FROM warranty_claims wc
                JOIN warranties w ON wc.warranty_id = w.id
                JOIN order_items oi ON w.order_item_id = oi.id
                JOIN orders o ON oi.order_id = o.id
                JOIN users u ON wc.user_id = u.id
                WHERE wc.claim_number = :claim_number AND wc.user_id = :user_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'claim_number' => $claimNumber,
            'user_id' => $userId
        ]);
        $claim = $stmt->fetch(PDO::FETCH_ASSOC);
        return $claim ?: null;
    }

    /**
     * Admin claim review and decision update
     */
    public function updateClaimStatusWithTransaction(
        int $claimId,
        string $newStatus,
        int $adminUserId,
        string $adminRole,
        ?string $adminDecision = null,
        ?string $resolutionDetails = null,
        ?string $internalNotes = null
    ): array {
        if ($adminRole !== 'ADMIN') {
            return [
                'success' => false,
                'message' => 'Unauthorized: Only administrators can update warranty claim decisions.'
            ];
        }

        $validStatuses = ['SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'REJECTED', 'RESOLVED', 'CLOSED'];
        if (!in_array($newStatus, $validStatuses, true)) {
            return [
                'success' => false,
                'message' => "Invalid claim status '{$newStatus}'."
            ];
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("SELECT id, warranty_id, status FROM warranty_claims WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $claimId]);
            $claim = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$claim) {
                throw new Exception("Warranty claim not found.");
            }

            // Check valid transition from current status
            $currentStatus = $claim['status'];
            $allowedTransitions = [
                'SUBMITTED' => ['UNDER_REVIEW', 'APPROVED', 'REJECTED'],
                'UNDER_REVIEW' => ['APPROVED', 'REJECTED'],
                'APPROVED' => ['RESOLVED', 'CLOSED'],
                'REJECTED' => ['UNDER_REVIEW'], // Admin can reopen for re-evaluation
                'RESOLVED' => ['CLOSED'],
                'CLOSED' => []
            ];

            if ($currentStatus !== $newStatus && isset($allowedTransitions[$currentStatus]) && !in_array($newStatus, $allowedTransitions[$currentStatus], true)) {
                throw new Exception("Invalid transition from '{$currentStatus}' to '{$newStatus}'.");
            }

            $isResolved = in_array($newStatus, ['RESOLVED', 'CLOSED'], true);
            $resolvedSql = $isResolved ? ', resolved_at = COALESCE(resolved_at, NOW())' : '';

            $updateSql = "UPDATE warranty_claims 
                          SET status = :status, 
                              admin_decision = :decision, 
                              resolution_details = :details,
                              internal_notes = :notes,
                              reviewed_by_user_id = :admin_id
                              {$resolvedSql},
                              updated_at = NOW() 
                          WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([
                'status' => $newStatus,
                'decision' => $adminDecision ?? $newStatus,
                'details' => $resolutionDetails,
                'notes' => $internalNotes,
                'admin_id' => $adminUserId,
                'id' => $claimId
            ]);

            // If rejected or closed, reset warranty status to ACTIVE if warranty not yet expired
            if (in_array($newStatus, ['REJECTED', 'CLOSED'], true)) {
                $checkExpiry = $this->db->prepare("SELECT end_date FROM warranties WHERE id = :id");
                $checkExpiry->execute(['id' => $claim['warranty_id']]);
                $endDate = $checkExpiry->fetchColumn();

                $warStatus = ($endDate && strtotime((string)$endDate) >= time()) ? 'ACTIVE' : 'EXPIRED';
                $upWar = $this->db->prepare("UPDATE warranties SET status = :status WHERE id = :id");
                $upWar->execute(['status' => $warStatus, 'id' => $claim['warranty_id']]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'claim_id' => $claimId,
                'new_status' => $newStatus
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getAdminClaims(array $filters, int $page = 1, int $perPage = 15): array
    {
        [$whereClause, $params] = $this->buildAdminClaimFilterQuery($filters);

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT wc.*, 
                       w.serial_number, w.start_date, w.end_date,
                       oi.product_name, oi.unit_price, oi.warranty_months,
                       o.order_number, o.delivered_at,
                       u.name AS customer_name, u.email AS customer_email,
                       u_rev.name AS reviewer_name
                FROM warranty_claims wc
                JOIN warranties w ON wc.warranty_id = w.id
                JOIN order_items oi ON w.order_item_id = oi.id
                JOIN orders o ON oi.order_id = o.id
                JOIN users u ON wc.user_id = u.id
                LEFT JOIN users u_rev ON wc.reviewed_by_user_id = u_rev.id
                {$whereClause}
                ORDER BY wc.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":{$k}", $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminClaimsCount(array $filters): int
    {
        [$whereClause, $params] = $this->buildAdminClaimFilterQuery($filters);
        $sql = "SELECT COUNT(*) 
                FROM warranty_claims wc
                JOIN warranties w ON wc.warranty_id = w.id
                JOIN order_items oi ON w.order_item_id = oi.id
                JOIN orders o ON oi.order_id = o.id
                JOIN users u ON wc.user_id = u.id
                {$whereClause}";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":{$k}", $v);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function buildAdminClaimFilterQuery(array $filters): array
    {
        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = "wc.status = :status";
            $params['status'] = strtoupper(trim($filters['status']));
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $conditions[] = "(wc.claim_number LIKE :s1 OR o.order_number LIKE :s2 OR u.name LIKE :s3 OR oi.product_name LIKE :s4)";
            $params['s1'] = $s;
            $params['s2'] = $s;
            $params['s3'] = $s;
            $params['s4'] = $s;
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "wc.created_at >= :date_from";
            $params['date_from'] = trim($filters['date_from']) . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "wc.created_at <= :date_to";
            $params['date_to'] = trim($filters['date_to']) . ' 23:59:59';
        }

        return ["WHERE " . implode(" AND ", $conditions), $params];
    }

    public function getAdminClaimById(int $claimId): ?array
    {
        $sql = "SELECT wc.*, 
                       w.serial_number, w.start_date, w.end_date, w.status AS warranty_status,
                       oi.id AS order_item_id, oi.product_name, oi.unit_price, oi.warranty_months,
                       o.id AS order_id, o.order_number, o.delivered_at, o.user_id AS order_user_id,
                       u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                       u_rev.name AS reviewer_name
                FROM warranty_claims wc
                JOIN warranties w ON wc.warranty_id = w.id
                JOIN order_items oi ON w.order_item_id = oi.id
                JOIN orders o ON oi.order_id = o.id
                JOIN users u ON wc.user_id = u.id
                LEFT JOIN users u_rev ON wc.reviewed_by_user_id = u_rev.id
                WHERE wc.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $claimId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
