<?php

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
                GROUP BY c.id
                ORDER BY c.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
