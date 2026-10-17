<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProductService;
use App\Services\CategoryService;
use App\Helpers\Session;
use App\Helpers\CSRF;

class AdminProductController extends BaseController
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
        $data = $this->productService->getAdminProducts($_GET);
        $categories = $this->categoryService->getAdminCategories();

        $this->render('admin/products/index', [
            'title' => 'Product Inventory Matrix | YasIX Admin',
            'headerTitle' => 'Hardware Catalog & SKU Administration',
            'products' => $data['products'],
            'filters' => $data['filters'],
            'pagination' => $data['pagination'],
            'categories' => $categories
        ], 'admin');
    }

    public function create(): void
    {
        $categories = $this->categoryService->getAdminCategories();

        $this->render('admin/products/create', [
            'title' => 'Deploy New Hardware SKU | YasIX Admin',
            'headerTitle' => 'Provision New Catalog SKU',
            'categories' => $categories,
            'old' => Session::getFlash('old_input') ?? []
        ], 'admin');
    }

    public function store(): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch. Please try again.');
            header('Location: ' . getenv('APP_URL') . '/admin/products/create');
            exit;
        }

        $file = $_FILES['image'] ?? null;
        $result = $this->productService->createProduct($_POST, $file);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            Session::setFlash('old_input', $_POST);
            header('Location: ' . getenv('APP_URL') . '/admin/products/create');
            exit;
        }

        Session::setFlash('success', 'Hardware product deployed successfully into active catalog matrix.');
        header('Location: ' . getenv('APP_URL') . '/admin/products');
        exit;
    }

    public function edit(string $id): void
    {
        $productId = (int)$id;
        $product = $this->productService->getAdminProductById($productId);

        if (!$product) {
            Session::setFlash('error', 'Product SKU not found in database.');
            header('Location: ' . getenv('APP_URL') . '/admin/products');
            exit;
        }

        $categories = $this->categoryService->getAdminCategories();

        $this->render('admin/products/edit', [
            'title' => 'Modify Hardware SKU #' . $productId . ' | YasIX Admin',
            'headerTitle' => 'Modify Hardware SKU: ' . htmlspecialchars($product['name']),
            'product' => $product,
            'categories' => $categories
        ], 'admin');
    }

    public function update(string $id): void
    {
        Session::start();
        $productId = (int)$id;
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . "/admin/products/{$productId}/edit");
            exit;
        }

        $file = $_FILES['image'] ?? null;
        $result = $this->productService->updateProduct($productId, $_POST, $file);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            header('Location: ' . getenv('APP_URL') . "/admin/products/{$productId}/edit");
            exit;
        }

        Session::setFlash('success', 'Product specifications and inventory updated successfully.');
        header('Location: ' . getenv('APP_URL') . '/admin/products');
        exit;
    }

    public function toggleStatus(string $id): void
    {
        Session::start();
        $productId = (int)$id;
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/admin/products');
            exit;
        }

        $result = $this->productService->toggleProductStatus($productId);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
        } else {
            Session::setFlash('success', "Product #{$productId} status toggled to {$result['status']}.");
        }

        header('Location: ' . getenv('APP_URL') . '/admin/products');
        exit;
    }
}
