<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepository;
use App\Validators\Validator;
use Throwable;

class CategoryService extends BaseService
{
    private CategoryRepository $categoryRepository;
    private Validator $validator;

    public function __construct(?CategoryRepository $categoryRepository = null)
    {
        $this->categoryRepository = $categoryRepository ?? new CategoryRepository();
        $this->validator = new Validator();
    }

    public function getCategories(): array
    {
        try {
            return $this->categoryRepository->getAllCategoriesWithCounts();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getAdminCategories(): array
    {
        try {
            return $this->categoryRepository->getAllCategoriesForAdmin();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getCategoryById(int $id): ?array
    {
        return $this->categoryRepository->findById($id);
    }

    public function createCategory(array $input): array
    {
        $name = trim($input['name'] ?? '');
        $slug = trim($input['slug'] ?? '');
        $description = trim($input['description'] ?? '');
        $iconClass = trim($input['icon_class'] ?? 'fa-solid fa-folder');
        $status = strtoupper(trim($input['status'] ?? 'ACTIVE'));

        if (empty($name) || strlen($name) < 2) {
            return ['success' => false, 'error' => 'Category name must be at least 2 characters long.'];
        }

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug), '-'));
        }

        if ($this->categoryRepository->slugExists($slug)) {
            return ['success' => false, 'error' => "A category with the slug '{$slug}' already exists. Please choose a unique name or slug."];
        }

        $id = $this->categoryRepository->createCategory([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'icon_class' => $iconClass,
            'status' => in_array($status, ['ACTIVE', 'INACTIVE'], true) ? $status : 'ACTIVE'
        ]);

        return ['success' => true, 'category_id' => $id];
    }

    public function updateCategory(int $id, array $input): array
    {
        $existing = $this->categoryRepository->findById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Category not found.'];
        }

        $name = trim($input['name'] ?? '');
        $slug = trim($input['slug'] ?? '');
        $description = trim($input['description'] ?? '');
        $iconClass = trim($input['icon_class'] ?? $existing['icon_class']);
        $status = strtoupper(trim($input['status'] ?? $existing['status']));

        if (empty($name) || strlen($name) < 2) {
            return ['success' => false, 'error' => 'Category name must be at least 2 characters long.'];
        }

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug), '-'));
        }

        if ($this->categoryRepository->slugExists($slug, $id)) {
            return ['success' => false, 'error' => "A category with the slug '{$slug}' already exists. Please choose a unique slug."];
        }

        $this->categoryRepository->updateCategory($id, [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'icon_class' => $iconClass,
            'status' => in_array($status, ['ACTIVE', 'INACTIVE'], true) ? $status : 'ACTIVE'
        ]);

        return ['success' => true];
    }

    public function toggleStatus(int $id): array
    {
        $existing = $this->categoryRepository->findById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Category not found.'];
        }

        $newStatus = ($existing['status'] === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
        $this->categoryRepository->setStatus($id, $newStatus);

        return ['success' => true, 'status' => $newStatus];
    }

    public function deleteCategory(int $id): array
    {
        $existing = $this->categoryRepository->findById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Category not found.'];
        }

        $productCount = $this->categoryRepository->getAssociatedProductCount($id);
        if ($productCount > 0) {
            return [
                'success' => false,
                'error' => "Security Rule: Category contains {$productCount} associated product(s). Deletion is prohibited to preserve integrity. Please deactivate the category or reassign products first."
            ];
        }

        $this->categoryRepository->deleteCategory($id);
        return ['success' => true];
    }
}
