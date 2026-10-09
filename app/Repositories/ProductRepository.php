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
        return $this->findBySlugWithDetails($slug);
    }

    public function findBySlugWithDetails(string $slug): ?array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon_class AS category_icon,
                       (SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.id) AS avg_rating,
                       (SELECT COUNT(*) FROM product_reviews pr WHERE pr.product_id = p.id) AS review_count,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.slug = :slug AND p.status != 'DISCONTINUED' LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByIdWithDetails(int $id): ?array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon_class AS category_icon,
                       (SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.id) AS avg_rating,
                       (SELECT COUNT(*) FROM product_reviews pr WHERE pr.product_id = p.id) AS review_count,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id AND p.status != 'DISCONTINUED' LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getProductImages(int $productId): array
    {
        $sql = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRelatedProducts(int $categoryId, int $excludeProductId, int $limit = 4): array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = :category_id AND p.id != :exclude_id AND p.status != 'DISCONTINUED'
                ORDER BY RAND() LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':exclude_id', $excludeProductId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableBrands(): array
    {
        $sql = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' AND status != 'DISCONTINUED' ORDER BY brand ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableConditions(): array
    {
        return ['NEW', 'REFURBISHED', 'CUSTOM_MOD'];
    }

    public function getFilteredProducts(array $filters, int $page = 1, int $perPage = 6, string $sort = 'newest'): array
    {
        [$whereClause, $params] = $this->buildFilterQuery($filters);

        $sortExpression = match ($sort) {
            'price_asc' => 'p.price ASC, p.id DESC',
            'price_desc' => 'p.price DESC, p.id DESC',
            'name_asc' => 'p.name ASC',
            'rating' => 'avg_rating DESC, p.id DESC',
            default => 'p.id DESC' // newest
        };

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon_class AS category_icon,
                       (SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.id) AS avg_rating,
                       (SELECT COUNT(*) FROM product_reviews pr WHERE pr.product_id = p.id) AS review_count,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                FROM products p
                JOIN categories c ON p.category_id = c.id
                {$whereClause}
                ORDER BY {$sortExpression}
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue(":{$key}", $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredProductsCount(array $filters): int
    {
        [$whereClause, $params] = $this->buildFilterQuery($filters);

        $sql = "SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.id {$whereClause}";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue(":{$key}", $val);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function buildFilterQuery(array $filters): array
    {
        $conditions = ["p.status != 'DISCONTINUED'"];
        $params = [];

        // Search Keyword
        if (!empty($filters['search'])) {
            $searchKeyword = '%' . trim($filters['search']) . '%';
            $conditions[] = "(p.name LIKE :search OR p.brand LIKE :search OR p.description LIKE :search OR c.name LIKE :search)";
            $params['search'] = $searchKeyword;
        }

        // Category Filter (slug or ID)
        if (!empty($filters['category'])) {
            if (is_numeric($filters['category'])) {
                $conditions[] = "c.id = :category_id";
                $params['category_id'] = (int)$filters['category'];
            } else {
                $conditions[] = "c.slug = :category_slug";
                $params['category_slug'] = trim($filters['category']);
            }
        }

        // Brand Filter
        if (!empty($filters['brand'])) {
            $conditions[] = "LOWER(p.brand) = LOWER(:brand)";
            $params['brand'] = trim($filters['brand']);
        }

        // Price Min/Max
        if (isset($filters['min_price']) && is_numeric($filters['min_price']) && $filters['min_price'] >= 0) {
            $conditions[] = "p.price >= :min_price";
            $params['min_price'] = (float)$filters['min_price'];
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price']) && $filters['max_price'] > 0) {
            $conditions[] = "p.price <= :max_price";
            $params['max_price'] = (float)$filters['max_price'];
        }

        // Condition Filter (NEW, REFURBISHED, CUSTOM_MOD)
        if (!empty($filters['condition']) && in_array(strtoupper($filters['condition']), ['NEW', 'REFURBISHED', 'CUSTOM_MOD'], true)) {
            $conditions[] = "p.condition = :condition";
            $params['condition'] = strtoupper($filters['condition']);
        }

        // Warranty Duration (Minimum months)
        if (isset($filters['warranty']) && is_numeric($filters['warranty']) && $filters['warranty'] > 0) {
            $conditions[] = "p.warranty_months >= :warranty";
            $params['warranty'] = (int)$filters['warranty'];
        }

        // In-Stock Only
        if (!empty($filters['in_stock'])) {
            $conditions[] = "p.stock_quantity > 0 AND p.status = 'AVAILABLE'";
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);
        return [$whereClause, $params];
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
