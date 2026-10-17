<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Throwable;

class ProductService extends BaseService
{
    private ProductRepository $productRepository;

    public function __construct(?ProductRepository $productRepository = null)
    {
        $this->productRepository = $productRepository ?? new ProductRepository();
    }

    public function getHomeFeaturedProducts(): array
    {
        try {
            $products = $this->productRepository->getFeaturedProducts(8);
            if (!empty($products)) {
                return array_map(function ($product) {
                    $product['formatted_price'] = '$' . number_format((float)$product['price'], 2);
                    return $product;
                }, $products);
            }
        } catch (Throwable $e) {
            // Log or fallback safely in case database is unpopulated or unreachable
        }

        // Safe fallback default mock array for initial frontend compatibility
        return [
            [
                'id' => 1,
                'name' => 'YasIX Synapse Neural Headset',
                'category_slug' => 'neural-heuristics',
                'category_name' => 'Neural Heuristics',
                'price' => 399.00,
                'formatted_price' => '$399.00',
                'icon' => 'fa-headset',
                'description' => 'Spatial 7.1 audio with direct neural brainwave acoustic feedback sync.',
                'rating' => 5,
                'reviews_count' => 128,
                'tag' => 'POPULAR'
            ],
            [
                'id' => 2,
                'name' => 'RTX 5090 Cyber-Quantum',
                'category_slug' => 'cyber-gpus',
                'category_name' => 'Cyber GPUs',
                'price' => 2299.00,
                'formatted_price' => '$2,299.00',
                'icon' => 'fa-microchip',
                'description' => '32GB GDDR7, DLSS 4.0 frame gen, dual chamber vapor cooling array.',
                'rating' => 5,
                'reviews_count' => 210,
                'tag' => 'TOP PERFORMER'
            ]
        ];
    }

    public function getCatalog(array $params = []): array
    {
        $categoryService = new CategoryService();
        $categories = $categoryService->getCategories();
        $brands = $this->productRepository->getAvailableBrands();
        $conditions = $this->productRepository->getAvailableConditions();

        // Sanitize and extract filter parameters
        $search = trim((string)($params['q'] ?? $params['search'] ?? ''));
        $category = trim((string)($params['category'] ?? ''));
        $brand = trim((string)($params['brand'] ?? ''));
        $minPrice = isset($params['min_price']) && is_numeric($params['min_price']) ? (float)$params['min_price'] : null;
        $maxPrice = isset($params['max_price']) && is_numeric($params['max_price']) ? (float)$params['max_price'] : null;
        $condition = trim((string)($params['condition'] ?? ''));
        $warranty = isset($params['warranty']) && is_numeric($params['warranty']) ? (int)$params['warranty'] : null;
        $inStock = !empty($params['in_stock']);
        $sort = trim((string)($params['sort'] ?? 'newest'));

        // Validate min/max price bounds
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            $temp = $minPrice;
            $minPrice = $maxPrice;
            $maxPrice = $temp;
        }

        $filters = [
            'search' => $search,
            'category' => $category,
            'brand' => $brand,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'condition' => $condition,
            'warranty' => $warranty,
            'in_stock' => $inStock
        ];

        // Pagination setup
        $perPage = 6;
        $page = isset($params['page']) && is_numeric($params['page']) ? (int)$params['page'] : 1;
        $page = max(1, $page);

        $totalProducts = $this->productRepository->getFilteredProductsCount($filters);
        $totalPages = (int)ceil($totalProducts / $perPage);
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        $products = $this->productRepository->getFilteredProducts($filters, $page, $perPage, $sort);

        // Format product prices and attributes
        $formattedProducts = array_map(function ($product) {
            $product['formatted_price'] = '$' . number_format((float)$product['price'], 2);
            $product['is_available'] = ($product['status'] === 'AVAILABLE' && (int)$product['stock_quantity'] > 0);
            return $product;
        }, $products);

        return [
            'products' => $formattedProducts,
            'categories' => $categories,
            'brands' => $brands,
            'conditions' => $conditions,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'brand' => $brand,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'condition' => $condition,
                'warranty' => $warranty,
                'in_stock' => $inStock,
                'sort' => $sort
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_products' => $totalProducts,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    }

    public function getProductBySlug(string $slug): ?array
    {
        $product = $this->productRepository->findBySlugWithDetails($slug);
        if (!$product) {
            return null;
        }

        $product['formatted_price'] = '$' . number_format((float)$product['price'], 2);
        $product['is_available'] = ($product['status'] === 'AVAILABLE' && (int)$product['stock_quantity'] > 0);
        $product['images'] = $this->productRepository->getProductImages((int)$product['id']);
        $product['related_products'] = $this->productRepository->getRelatedProducts((int)$product['category_id'], (int)$product['id'], 4);

        return $product;
    }

    public function getProductById(int $id): ?array
    {
        $product = $this->productRepository->findByIdWithDetails($id);
        if (!$product) {
            return null;
        }

        $product['formatted_price'] = '$' . number_format((float)$product['price'], 2);
        $product['is_available'] = ($product['status'] === 'AVAILABLE' && (int)$product['stock_quantity'] > 0);
        $product['images'] = $this->productRepository->getProductImages((int)$product['id']);
        $product['related_products'] = $this->productRepository->getRelatedProducts((int)$product['category_id'], (int)$product['id'], 4);

        return $product;
    }

    public function getAdminProductById(int $id): ?array
    {
        $product = $this->productRepository->findAdminProductById($id);
        if (!$product) {
            return null;
        }

        $product['formatted_price'] = formatCurrency($product['price']);
        $product['is_available'] = ($product['status'] === 'AVAILABLE' && (int)$product['stock_quantity'] > 0);
        $product['images'] = $this->productRepository->getProductImages((int)$product['id']);
        return $product;
    }

    public function getFlashDeal(): ?array
    {
        try {
            return $this->productRepository->getFlashDealProduct();
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getAdminProducts(array $params): array
    {
        $page = isset($params['page']) && is_numeric($params['page']) ? max(1, (int)$params['page']) : 1;
        $perPage = 15;

        $filters = [
            'search' => trim($params['search'] ?? ''),
            'category_id' => !empty($params['category_id']) ? (int)$params['category_id'] : null,
            'status' => trim($params['status'] ?? ''),
            'stock_status' => trim($params['stock_status'] ?? '')
        ];

        $total = $this->productRepository->getAdminProductsCount($filters);
        $totalPages = (int)ceil($total / $perPage);
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        $products = $this->productRepository->getAdminProducts($filters, $page, $perPage);

        return [
            'products' => $products,
            'filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_products' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    }

    public function createProduct(array $input, ?array $file = null): array
    {
        $validation = $this->validateProductInput($input);
        if (!$validation['success']) {
            return $validation;
        }

        $slug = $validation['slug'];
        if ($this->productRepository->slugExists($slug)) {
            return ['success' => false, 'error' => "A product with the slug '{$slug}' already exists. Please customize the product name or slug."];
        }

        $productData = $validation['data'];
        $productId = $this->productRepository->createProduct($productData);

        // Safe Image Upload Handling
        if ($file && !empty($file['tmp_name'])) {
            $uploadedPath = $this->handleImageUpload($file);
            if ($uploadedPath) {
                $this->productRepository->setPrimaryImage($productId, $uploadedPath);
            }
        } elseif (!empty($input['image_path'])) {
            $this->productRepository->setPrimaryImage($productId, trim($input['image_path']));
        }

        return ['success' => true, 'product_id' => $productId];
    }

    public function updateProduct(int $id, array $input, ?array $file = null): array
    {
        $existing = $this->productRepository->findAdminProductById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Product not found.'];
        }

        $validation = $this->validateProductInput($input, $id);
        if (!$validation['success']) {
            return $validation;
        }

        $slug = $validation['slug'];
        if ($this->productRepository->slugExists($slug, $id)) {
            return ['success' => false, 'error' => "A product with the slug '{$slug}' already exists. Please choose a unique slug."];
        }

        $productData = $validation['data'];
        $this->productRepository->updateProduct($id, $productData);

        // Handle safe image upload or update
        if ($file && !empty($file['tmp_name'])) {
            $uploadedPath = $this->handleImageUpload($file);
            if ($uploadedPath) {
                $this->productRepository->setPrimaryImage($id, $uploadedPath);
            }
        } elseif (!empty($input['image_path']) && $input['image_path'] !== ($existing['primary_image'] ?? '')) {
            $this->productRepository->setPrimaryImage($id, trim($input['image_path']));
        }

        return ['success' => true];
    }

    public function toggleProductStatus(int $id): array
    {
        $existing = $this->productRepository->findAdminProductById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Product not found.'];
        }

        $newStatus = ($existing['status'] === 'INACTIVE' || $existing['status'] === 'DISCONTINUED')
            ? (((int)$existing['stock_quantity'] > 0) ? 'AVAILABLE' : 'OUT_OF_STOCK')
            : 'INACTIVE';

        $this->productRepository->setStatus($id, $newStatus);
        return ['success' => true, 'status' => $newStatus];
    }

    public function adjustStock(int $productId, int $newStock, ?int $userId, string $reason): array
    {
        if ($newStock < 0) {
            return ['success' => false, 'error' => 'Stock quantity cannot be negative.'];
        }

        if (empty(trim($reason))) {
            return ['success' => false, 'error' => 'An adjustment reason must be provided for audit compliance.'];
        }

        return $this->productRepository->adjustStock($productId, $newStock, $userId, $reason);
    }

    public function getInventoryLogs(?int $productId = null, int $limit = 50): array
    {
        return $this->productRepository->getInventoryLogs($productId, $limit);
    }

    private function validateProductInput(array $input, ?int $excludeId = null): array
    {
        $name = trim($input['name'] ?? '');
        if (empty($name) || strlen($name) < 2 || strlen($name) > 200) {
            return ['success' => false, 'error' => 'Product name is required (between 2 and 200 characters).'];
        }

        if (!isset($input['price']) || !is_numeric($input['price']) || (float)$input['price'] < 0) {
            return ['success' => false, 'error' => 'Valid non-negative price is required.'];
        }

        if (!isset($input['stock_quantity']) || !is_numeric($input['stock_quantity']) || (int)$input['stock_quantity'] < 0) {
            return ['success' => false, 'error' => 'Valid non-negative stock quantity integer is required.'];
        }

        if (empty($input['category_id']) || !is_numeric($input['category_id'])) {
            return ['success' => false, 'error' => 'Valid product category must be selected.'];
        }

        $slug = trim($input['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug), '-'));
        }

        $condition = strtoupper(trim($input['condition'] ?? 'NEW'));
        if (!in_array($condition, ['NEW', 'REFURBISHED', 'CUSTOM_MOD'], true)) {
            $condition = 'NEW';
        }

        $status = strtoupper(trim($input['status'] ?? 'AVAILABLE'));
        if (!in_array($status, ['AVAILABLE', 'OUT_OF_STOCK', 'DISCONTINUED', 'INACTIVE'], true)) {
            $status = ((int)$input['stock_quantity'] === 0) ? 'OUT_OF_STOCK' : 'AVAILABLE';
        }

        return [
            'success' => true,
            'slug' => $slug,
            'data' => [
                'name' => $name,
                'slug' => $slug,
                'category_id' => (int)$input['category_id'],
                'brand' => trim($input['brand'] ?? 'YasIX') ?: 'YasIX',
                'description' => trim($input['description'] ?? ''),
                'price' => (float)$input['price'],
                'stock_quantity' => (int)$input['stock_quantity'],
                'weight' => max(0.0, (float)($input['weight'] ?? 0.0)),
                'package_length' => max(0.0, (float)($input['package_length'] ?? 0.0)),
                'package_width' => max(0.0, (float)($input['package_width'] ?? 0.0)),
                'package_height' => max(0.0, (float)($input['package_height'] ?? 0.0)),
                'warranty_months' => max(0, (int)($input['warranty_months'] ?? 12)),
                'condition' => $condition,
                'status' => $status,
                'featured' => !empty($input['featured']) ? 1 : 0
            ]
        ];
    }

    /**
     * Secure Image Upload Processing
     */
    private function handleImageUpload(array $file): ?string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Limit size to 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        // Validate MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedMimes[$mime])) {
            return null;
        }

        // Verify image content
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return null;
        }

        $extension = $allowedMimes[$mime];
        $filename = 'prod_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $uploadDir = dirname(__DIR__, 2) . '/public/assets/images/products';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return '/assets/images/products/' . $filename;
        }

        return null;
    }
}
