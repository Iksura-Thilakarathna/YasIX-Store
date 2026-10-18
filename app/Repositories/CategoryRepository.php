<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class CategoryRepository extends BaseRepository
{
    protected string $table = 'categories';

    public function getAllCategoriesWithCounts(): array
    {
        $sql = "SELECT c.*, COUNT(p.id) AS product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id AND p.status = 'AVAILABLE'
                WHERE c.status = 'ACTIVE'
                GROUP BY c.id
                ORDER BY c.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCategoriesForAdmin(): array
    {
        $sql = "SELECT c.*, COUNT(p.id) AS product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id
                GROUP BY c.id
                ORDER BY c.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM categories WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM categories WHERE slug = :slug LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM categories WHERE slug = :slug";
        $params = ['slug' => strtolower(trim($slug))];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createCategory(array $data): int
    {
        $sql = "INSERT INTO categories (name, slug, description, icon_class, status, created_at, updated_at) 
                VALUES (:name, :slug, :description, :icon_class, :status, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => trim($data['name']),
            'slug' => strtolower(trim($data['slug'])),
            'description' => trim($data['description'] ?? ''),
            'icon_class' => trim($data['icon_class'] ?? 'fa-solid fa-folder'),
            'status' => in_array(strtoupper($data['status'] ?? 'ACTIVE'), ['ACTIVE', 'INACTIVE'], true) ? strtoupper($data['status']) : 'ACTIVE'
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateCategory(int $id, array $data): bool
    {
        $sql = "UPDATE categories 
                SET name = :name, 
                    slug = :slug, 
                    description = :description, 
                    icon_class = :icon_class, 
                    status = :status, 
                    updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'name' => trim($data['name']),
            'slug' => strtolower(trim($data['slug'])),
            'description' => trim($data['description'] ?? ''),
            'icon_class' => trim($data['icon_class'] ?? 'fa-solid fa-folder'),
            'status' => in_array(strtoupper($data['status'] ?? 'ACTIVE'), ['ACTIVE', 'INACTIVE'], true) ? strtoupper($data['status']) : 'ACTIVE',
            'id' => $id
        ]);
    }

    public function setStatus(int $id, string $status): bool
    {
        $validStatus = in_array(strtoupper($status), ['ACTIVE', 'INACTIVE'], true) ? strtoupper($status) : 'ACTIVE';
        $sql = "UPDATE categories SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $validStatus,
            'id' => $id
        ]);
    }

    public function getAssociatedProductCount(int $categoryId): int
    {
        $sql = "SELECT COUNT(*) FROM products WHERE category_id = :category_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['category_id' => $categoryId]);
        return (int)$stmt->fetchColumn();
    }

    public function deleteCategory(int $id): bool
    {
        if ($this->getAssociatedProductCount($id) > 0) {
            return false;
        }

        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
