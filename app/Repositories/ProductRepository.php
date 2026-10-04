<?php

namespace App\Repositories;

use PDO;

class ProductRepository extends BaseRepository
{
    protected string $table = 'products';

    public function getFeaturedProducts(int $limit = 8): array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                       (SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.id) AS avg_rating,
                       (SELECT COUNT(*) FROM product_reviews pr WHERE pr.product_id = p.id) AS review_count
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.featured = 1 AND p.status = 'AVAILABLE'
                ORDER BY p.id ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductsByCategory(string $categorySlug): array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE c.slug = :category_slug AND p.status = 'AVAILABLE'
                ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['category_slug' => $categorySlug]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.slug = :slug LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getFlashDealProduct(): ?array
    {
        $sql = "SELECT p.*, c.name AS category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.slug = 'yasix-apex-mech-rig-2026-edition' LIMIT 1";

        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
