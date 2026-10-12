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

    public function getFlashDeal(): ?array
    {
        try {
            return $this->productRepository->getFlashDealProduct();
        } catch (Throwable $e) {
            return null;
        }
    }
}
