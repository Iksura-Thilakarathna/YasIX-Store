<?php

namespace App\Controllers;

use App\Services\ProductService;
use App\Services\CategoryService;

class HomeController extends BaseController
{
    private ProductService $productService;
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->productService = new ProductService();
        $this->categoryService = new CategoryService();
    }

    public function index(): void
    {
        $products = $this->productService->getHomeFeaturedProducts();
        $categories = $this->categoryService->getCategories();
        $flashDeal = $this->productService->getFlashDeal();

        $this->render('home/index', [
            'products' => $products,
            'categories' => $categories,
            'flashDeal' => $flashDeal,
            'title' => 'YasIX Store - Next-Gen Cyber Hardware & Tech'
        ]);
    }
}
