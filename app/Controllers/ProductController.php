<?php

namespace App\Controllers;

use App\Services\ProductService;
use App\Helpers\View;

class ProductController extends BaseController
{
    private ProductService $productService;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    public function index(): void
    {
        $catalogData = $this->productService->getCatalog($_GET);

        View::render('products/index', [
            'title' => 'Product Catalog - YasIX Cyber Store',
            'catalog' => $catalogData
        ]);
    }

    public function detail(?string $slug = null): void
    {
        $targetSlug = $slug ?? $_GET['slug'] ?? null;
        $targetId = $_GET['id'] ?? null;

        $product = null;

        if ($targetSlug) {
            $product = $this->productService->getProductBySlug($targetSlug);
        } elseif ($targetId && is_numeric($targetId)) {
            $product = $this->productService->getProductById((int)$targetId);
        }

        if (!$product) {
            (new ErrorController())->notFound();
            return;
        }

        View::render('products/detail', [
            'title' => e($product['name']) . ' - YasIX Store',
            'product' => $product
        ]);
    }
}
