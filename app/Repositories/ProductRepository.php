<?php

namespace App\Repositories;

use PDO;

class ProductRepository extends BaseRepository
{
    protected string $table = 'products';

    public function getFeaturedProducts(int $limit = 8): array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon_class AS category_icon,
                       (SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.id) AS avg_rating,
                       (SELECT COUNT(*) FROM product_reviews pr WHERE pr.product_id = p.id) AS review_count,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
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
                WHERE p.slug = :slug AND p.status NOT IN ('DISCONTINUED', 'INACTIVE') LIMIT 1";

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
                WHERE p.id = :id AND p.status NOT IN ('DISCONTINUED', 'INACTIVE') LIMIT 1";

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
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon_class AS category_icon,
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
        $conditions = ["p.status NOT IN ('DISCONTINUED', 'INACTIVE')"];
        $params = [];

        // Search Keyword
        if (!empty($filters['search'])) {
            $searchKeyword = '%' . trim($filters['search']) . '%';
            $conditions[] = "(p.name LIKE :s1 OR p.brand LIKE :s2 OR p.description LIKE :s3 OR c.name LIKE :s4)";
            $params['s1'] = $searchKeyword;
            $params['s2'] = $searchKeyword;
            $params['s3'] = $searchKeyword;
            $params['s4'] = $searchKeyword;
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
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon_class AS category_icon,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE (p.slug = 'yasix-apex-mech-rig-2026-edition' OR p.featured = 1) AND p.status = 'AVAILABLE'
                ORDER BY (p.slug = 'yasix-apex-mech-rig-2026-edition') DESC, p.id DESC
                LIMIT 1";

        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findAdminProductById(int $id): ?array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon_class AS category_icon,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAdminProducts(array $filters, int $page = 1, int $perPage = 15): array
    {
        [$whereClause, $params] = $this->buildAdminFilterQuery($filters);

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon_class AS category_icon,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                {$whereClause}
                ORDER BY p.id DESC
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

    public function getAdminProductsCount(array $filters): int
    {
        [$whereClause, $params] = $this->buildAdminFilterQuery($filters);
        $sql = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id {$whereClause}";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":{$k}", $v);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function buildAdminFilterQuery(array $filters): array
    {
        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $conditions[] = "(p.name LIKE :s1 OR p.brand LIKE :s2 OR p.slug LIKE :s3)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
        }

        if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $conditions[] = "p.category_id = :cat_id";
            $params['cat_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $status = strtoupper(trim($filters['status']));
            if (in_array($status, ['AVAILABLE', 'OUT_OF_STOCK', 'DISCONTINUED', 'INACTIVE'], true)) {
                $conditions[] = "p.status = :status";
                $params['status'] = $status;
            }
        }

        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'in_stock':
                    $conditions[] = "p.stock_quantity > 5";
                    break;
                case 'low_stock':
                    $conditions[] = "p.stock_quantity > 0 AND p.stock_quantity <= 5";
                    break;
                case 'out_of_stock':
                    $conditions[] = "p.stock_quantity = 0";
                    break;
            }
        }

        return ["WHERE " . implode(" AND ", $conditions), $params];
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM products WHERE slug = :slug";
        $params = ['slug' => strtolower(trim($slug))];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createProduct(array $data): int
    {
        $sql = "INSERT INTO products (
            category_id, name, slug, brand, description, price, stock_quantity, 
            weight, package_length, package_width, package_height, warranty_months, 
            `condition`, status, featured, created_at, updated_at
        ) VALUES (
            :category_id, :name, :slug, :brand, :description, :price, :stock_quantity,
            :weight, :package_length, :package_width, :package_height, :warranty_months,
            :condition, :status, :featured, NOW(), NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'category_id' => (int)$data['category_id'],
            'name' => trim($data['name']),
            'slug' => strtolower(trim($data['slug'])),
            'brand' => trim($data['brand'] ?? 'YasIX'),
            'description' => trim($data['description'] ?? ''),
            'price' => (float)$data['price'],
            'stock_quantity' => max(0, (int)$data['stock_quantity']),
            'weight' => max(0.0, (float)($data['weight'] ?? 0.0)),
            'package_length' => max(0.0, (float)($data['package_length'] ?? 0.0)),
            'package_width' => max(0.0, (float)($data['package_width'] ?? 0.0)),
            'package_height' => max(0.0, (float)($data['package_height'] ?? 0.0)),
            'warranty_months' => max(0, (int)($data['warranty_months'] ?? 12)),
            'condition' => in_array(strtoupper($data['condition'] ?? 'NEW'), ['NEW', 'REFURBISHED', 'CUSTOM_MOD'], true) ? strtoupper($data['condition']) : 'NEW',
            'status' => in_array(strtoupper($data['status'] ?? 'AVAILABLE'), ['AVAILABLE', 'OUT_OF_STOCK', 'DISCONTINUED', 'INACTIVE'], true) ? strtoupper($data['status']) : 'AVAILABLE',
            'featured' => !empty($data['featured']) ? 1 : 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateProduct(int $id, array $data): bool
    {
        $sql = "UPDATE products SET 
            category_id = :category_id,
            name = :name,
            slug = :slug,
            brand = :brand,
            description = :description,
            price = :price,
            stock_quantity = :stock_quantity,
            weight = :weight,
            package_length = :package_length,
            package_width = :package_width,
            package_height = :package_height,
            warranty_months = :warranty_months,
            `condition` = :condition,
            status = :status,
            featured = :featured,
            updated_at = NOW()
        WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'category_id' => (int)$data['category_id'],
            'name' => trim($data['name']),
            'slug' => strtolower(trim($data['slug'])),
            'brand' => trim($data['brand'] ?? 'YasIX'),
            'description' => trim($data['description'] ?? ''),
            'price' => (float)$data['price'],
            'stock_quantity' => max(0, (int)$data['stock_quantity']),
            'weight' => max(0.0, (float)($data['weight'] ?? 0.0)),
            'package_length' => max(0.0, (float)($data['package_length'] ?? 0.0)),
            'package_width' => max(0.0, (float)($data['package_width'] ?? 0.0)),
            'package_height' => max(0.0, (float)($data['package_height'] ?? 0.0)),
            'warranty_months' => max(0, (int)($data['warranty_months'] ?? 12)),
            'condition' => in_array(strtoupper($data['condition'] ?? 'NEW'), ['NEW', 'REFURBISHED', 'CUSTOM_MOD'], true) ? strtoupper($data['condition']) : 'NEW',
            'status' => in_array(strtoupper($data['status'] ?? 'AVAILABLE'), ['AVAILABLE', 'OUT_OF_STOCK', 'DISCONTINUED', 'INACTIVE'], true) ? strtoupper($data['status']) : 'AVAILABLE',
            'featured' => !empty($data['featured']) ? 1 : 0,
            'id' => $id
        ]);
    }

    public function setStatus(int $id, string $status): bool
    {
        $validStatus = in_array(strtoupper($status), ['AVAILABLE', 'OUT_OF_STOCK', 'DISCONTINUED', 'INACTIVE'], true) ? strtoupper($status) : 'AVAILABLE';
        $sql = "UPDATE products SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $validStatus,
            'id' => $id
        ]);
    }

    public function adjustStock(int $productId, int $newStock, ?int $userId, string $reason): array
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT stock_quantity, status FROM products WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $productId]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$current) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Product not found.'];
            }

            $previousStock = (int)$current['stock_quantity'];
            $newStock = max(0, $newStock);
            $change = $newStock - $previousStock;

            // Update product stock and status if depleted or restored
            $currentStatus = $current['status'];
            $newStatus = $currentStatus;
            if ($newStock === 0 && $currentStatus === 'AVAILABLE') {
                $newStatus = 'OUT_OF_STOCK';
            } elseif ($newStock > 0 && $currentStatus === 'OUT_OF_STOCK') {
                $newStatus = 'AVAILABLE';
            }

            $updateStmt = $this->db->prepare("UPDATE products SET stock_quantity = :stock, status = :status, updated_at = NOW() WHERE id = :id");
            $updateStmt->execute([
                'stock' => $newStock,
                'status' => $newStatus,
                'id' => $productId
            ]);

            // Insert audit log
            $logStmt = $this->db->prepare("
                INSERT INTO inventory_logs (product_id, user_id, change_quantity, previous_stock, new_stock, reason, created_at)
                VALUES (:product_id, :user_id, :change_quantity, :previous_stock, :new_stock, :reason, NOW())
            ");
            $logStmt->execute([
                'product_id' => $productId,
                'user_id' => $userId,
                'change_quantity' => $change,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => trim($reason) ?: 'Manual inventory adjustment'
            ]);

            $this->db->commit();
            return [
                'success' => true,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'change' => $change
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getInventoryLogs(?int $productId = null, int $limit = 50): array
    {
        $sql = "SELECT il.*, p.name AS product_name, u.name AS user_name
                FROM inventory_logs il
                JOIN products p ON il.product_id = p.id
                LEFT JOIN users u ON il.user_id = u.id";
        
        if ($productId !== null) {
            $sql .= " WHERE il.product_id = :product_id";
        }

        $sql .= " ORDER BY il.created_at DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        if ($productId !== null) {
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setPrimaryImage(int $productId, string $imagePath): bool
    {
        $unsetStmt = $this->db->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id");
        $unsetStmt->execute(['product_id' => $productId]);

        $insStmt = $this->db->prepare("
            INSERT INTO product_images (product_id, image_path, is_primary, sort_order, created_at)
            VALUES (:product_id, :image_path, 1, 0, NOW())
        ");
        return $insStmt->execute([
            'product_id' => $productId,
            'image_path' => $imagePath
        ]);
    }
}
