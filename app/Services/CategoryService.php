<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use Throwable;

class CategoryService extends BaseService
{
    private CategoryRepository $categoryRepository;

    public function __construct(?CategoryRepository $categoryRepository = null)
    {
        $this->categoryRepository = $categoryRepository ?? new CategoryRepository();
    }

    public function getCategories(): array
    {
        try {
            return $this->categoryRepository->getAllCategoriesWithCounts();
        } catch (Throwable $e) {
            return [];
        }
    }
}
