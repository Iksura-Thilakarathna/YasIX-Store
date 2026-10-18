<?php

namespace App\Repositories;

use PDO;

class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $normalizedEmail = strtolower(trim($email));
        $sql = "SELECT u.*, r.name AS role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE LOWER(u.email) = :email LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $normalizedEmail]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT u.*, r.name AS role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $normalizedEmail = strtolower(trim($email));
        $sql = "SELECT COUNT(*) FROM users WHERE LOWER(email) = :email";
        $params = ['email' => $normalizedEmail];

        if ($excludeUserId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createUser(array $data): int
    {
        $sql = "INSERT INTO users (role_id, name, email, password_hash, phone, status) 
                VALUES (:role_id, :name, :email, :password_hash, :phone, :status)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'role_id' => $data['role_id'] ?? 2, // Default: CUSTOMER
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password_hash' => $data['password_hash'],
            'phone' => trim($data['phone'] ?? ''),
            'status' => $data['status'] ?? 'ACTIVE'
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateProfile(int $userId, string $name, string $phone): bool
    {
        $sql = "UPDATE users SET name = :name, phone = :phone, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'name' => trim($name),
            'phone' => trim($phone),
            'id' => $userId
        ]);
    }

    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $sql = "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $userId
        ]);
    }

    public function getAdminCustomers(array $filters, int $page = 1, int $perPage = 15): array
    {
        [$whereClause, $params] = $this->buildAdminCustomerFilterQuery($filters);

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at, r.name AS role_name,
                       COUNT(DISTINCT o.id) AS total_orders,
                       COALESCE(SUM(CASE WHEN o.status != 'CANCELLED' THEN o.total_amount ELSE 0 END), 0) AS total_spent
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN orders o ON u.id = o.user_id
                {$whereClause}
                GROUP BY u.id, u.name, u.email, u.phone, u.status, u.created_at, r.name
                ORDER BY u.created_at DESC
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

    public function getAdminCustomersCount(array $filters): int
    {
        [$whereClause, $params] = $this->buildAdminCustomerFilterQuery($filters);
        $sql = "SELECT COUNT(DISTINCT u.id) FROM users u JOIN roles r ON u.role_id = r.id {$whereClause}";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":{$k}", $v);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function buildAdminCustomerFilterQuery(array $filters): array
    {
        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['role'])) {
            $conditions[] = "r.name = :role";
            $params['role'] = strtoupper(trim($filters['role']));
        } else {
            $conditions[] = "r.name = 'CUSTOMER'";
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $conditions[] = "(u.name LIKE :s1 OR u.email LIKE :s2 OR u.phone LIKE :s3)";
            $params['s1'] = $s;
            $params['s2'] = $s;
            $params['s3'] = $s;
        }

        if (!empty($filters['status'])) {
            $conditions[] = "u.status = :status";
            $params['status'] = strtoupper(trim($filters['status']));
        }

        return ["WHERE " . implode(" AND ", $conditions), $params];
    }

    public function getCustomerDetails(int $userId): ?array
    {
        $sql = "SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at, u.updated_at, r.name AS role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return null;

        // Fetch customer orders
        $orderStmt = $this->db->prepare("
            SELECT id, order_number, total_amount, status, payment_status, payment_method, created_at
            FROM orders
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");
        $orderStmt->execute(['user_id' => $userId]);
        $user['orders'] = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch customer warranty claims
        $claimStmt = $this->db->prepare("
            SELECT wc.id, wc.claim_number, wc.claim_reason, wc.status, wc.created_at, oi.product_name, o.order_number
            FROM warranty_claims wc
            JOIN warranties w ON wc.warranty_id = w.id
            JOIN order_items oi ON w.order_item_id = oi.id
            JOIN orders o ON oi.order_id = o.id
            WHERE wc.user_id = :user_id
            ORDER BY wc.created_at DESC
        ");
        $claimStmt->execute(['user_id' => $userId]);
        $user['claims'] = $claimStmt->fetchAll(PDO::FETCH_ASSOC);

        $user['total_orders'] = count($user['orders']);
        $user['total_spent'] = array_reduce($user['orders'], function ($acc, $ord) {
            return $acc + (float)$ord['total_amount'];
        }, 0.0);

        return $user;
    }

    public function updateCustomerStatus(int $userId, string $status): bool
    {
        $validStatuses = ['ACTIVE', 'INACTIVE', 'SUSPENDED'];
        $cleanStatus = in_array(strtoupper($status), $validStatuses, true) ? strtoupper($status) : 'ACTIVE';

        $sql = "UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $cleanStatus,
            'id' => $userId
        ]);
    }
}
