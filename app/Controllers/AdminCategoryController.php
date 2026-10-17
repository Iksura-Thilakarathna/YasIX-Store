<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CategoryService;
use App\Helpers\Session;
use App\Helpers\CSRF;

class AdminCategoryController extends BaseController
{
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->categoryService = new CategoryService();
    }

    public function index(): void
    {
        $categories = $this->categoryService->getAdminCategories();

        $this->render('admin/categories/index', [
            'title' => 'Category Taxonomies | YasIX Admin',
            'headerTitle' => 'Category Hierarchy & Taxonomy Management',
            'categories' => $categories
        ], 'admin');
    }

    public function store(): void
    {
        Session::start();
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/admin/categories');
            exit;
        }

        $result = $this->categoryService->createCategory($_POST);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
        } else {
            Session::setFlash('success', 'Hardware category provisioned successfully.');
        }

        header('Location: ' . getenv('APP_URL') . '/admin/categories');
        exit;
    }

    public function update(string $id): void
    {
        Session::start();
        $categoryId = (int)$id;
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/admin/categories');
            exit;
        }

        $result = $this->categoryService->updateCategory($categoryId, $_POST);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
        } else {
            Session::setFlash('success', 'Category metadata updated successfully.');
        }

        header('Location: ' . getenv('APP_URL') . '/admin/categories');
        exit;
    }

    public function toggleStatus(string $id): void
    {
        Session::start();
        $categoryId = (int)$id;
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/admin/categories');
            exit;
        }

        $result = $this->categoryService->toggleStatus($categoryId);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
        } else {
            Session::setFlash('success', "Category #{$categoryId} status updated to {$result['status']}.");
        }

        header('Location: ' . getenv('APP_URL') . '/admin/categories');
        exit;
    }

    public function delete(string $id): void
    {
        Session::start();
        $categoryId = (int)$id;
        $token = $_POST['_csrf_token'] ?? null;
        if (!CSRF::verifyToken($token)) {
            Session::setFlash('error', 'Security token mismatch.');
            header('Location: ' . getenv('APP_URL') . '/admin/categories');
            exit;
        }

        $result = $this->categoryService->deleteCategory($categoryId);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
        } else {
            Session::setFlash('success', "Category #{$categoryId} safely deleted.");
        }

        header('Location: ' . getenv('APP_URL') . '/admin/categories');
        exit;
    }
}
