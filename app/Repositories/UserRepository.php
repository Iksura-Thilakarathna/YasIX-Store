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
}
