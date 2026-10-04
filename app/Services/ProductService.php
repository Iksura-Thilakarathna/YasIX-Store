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

    public function getFlashDeal(): ?array
    {
        try {
            return $this->productRepository->getFlashDealProduct();
        } catch (Throwable $e) {
            return null;
        }
    }
}
