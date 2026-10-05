<?php

require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/ActivityLog.php';

class CategoryController
{
    private $categoryModel;
    private $activityLog;

    public function __construct()
    {
        $this->categoryModel = new Category();
        $this->activityLog = new ActivityLog();
    }

    public function listCategories(): array
    {
        return [
            'success' => true,
            'data'    => $this->categoryModel->getAll()
        ];
    }

    public function getCategory(int $id): array
    {
        $category = $this->categoryModel->getById($id);
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found.'];
        }
        return ['success' => true, 'data' => $category];
    }

    public function createCategory(string $name, ?int $userId): array
    {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required.'];
        }

        if (mb_strlen($name) > 100) {
            return ['success' => false, 'message' => 'Category name cannot exceed 100 characters.'];
        }

        if ($this->categoryModel->existsName($name)) {
            return ['success' => false, 'message' => "Category '{$name}' already exists."];
        }

        $categoryId = $this->categoryModel->create($name);
        if ($categoryId) {
            $this->activityLog->log($userId, null, 'create', null, null, "Created category: {$name}");
            return [
                'success' => true,
                'message' => 'Category created successfully.',
                'data'    => ['id' => $categoryId, 'category_name' => $name]
            ];
        }

        return ['success' => false, 'message' => 'Failed to create category.'];
    }

    public function updateCategory(int $id, string $name, ?int $userId): array
    {
        $name = trim($name);
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid category ID.'];
        }

        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required.'];
        }

        if (mb_strlen($name) > 100) {
            return ['success' => false, 'message' => 'Category name cannot exceed 100 characters.'];
        }

        $existing = $this->categoryModel->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Category not found.'];
        }

        if ($this->categoryModel->existsName($name, $id)) {
            return ['success' => false, 'message' => "Another category named '{$name}' already exists."];
        }

        $updated = $this->categoryModel->update($id, $name);
        if ($updated) {
            $this->activityLog->log($userId, null, 'update', null, null, "Updated category #{$id} from '{$existing['category_name']}' to '{$name}'");
            return [
                'success' => true,
                'message' => 'Category updated successfully.'
            ];
        }

        return ['success' => false, 'message' => 'Failed to update category.'];
    }

    public function deleteCategory(int $id, ?int $userId): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid category ID.'];
        }

        $existing = $this->categoryModel->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Category not found.'];
        }

        $result = $this->categoryModel->delete($id);
        if ($result['success']) {
            $this->activityLog->log($userId, null, 'delete', null, null, "Deleted category: {$existing['category_name']}");
        }

        return $result;
    }
}
