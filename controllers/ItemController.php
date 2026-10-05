<?php

require_once __DIR__ . '/../classes/Item.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/ActivityLog.php';
require_once __DIR__ . '/../classes/Notification.php';

class ItemController
{
    private $itemModel;
    private $categoryModel;
    private $activityLog;
    private $notification;

    public function __construct()
    {
        $this->itemModel = new Item();
        $this->categoryModel = new Category();
        $this->activityLog = new ActivityLog();
        $this->notification = new Notification();
    }

    public function listItems(?string $search = null, ?int $categoryId = null, bool $lowStockOnly = false): array
    {
        return [
            'success' => true,
            'data'    => $this->itemModel->getAll($search, $categoryId, $lowStockOnly)
        ];
    }

    public function getItem(int $id): array
    {
        $item = $this->itemModel->getById($id);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found.'];
        }
        return ['success' => true, 'data' => $item];
    }

    public function createItem(array $data, ?int $userId): array
    {
        $errors = $this->validateItemData($data);
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        if ($this->itemModel->existsSku($data['sku'])) {
            return ['success' => false, 'message' => "SKU '{$data['sku']}' is already in use."];
        }

        $categoryId = (int) $data['category_id'];
        $category = $this->categoryModel->getById($categoryId);
        if (!$category) {
            return ['success' => false, 'message' => 'Selected category is invalid.'];
        }

        $itemId = $this->itemModel->create($data);
        if ($itemId) {
            $qty = (int) ($data['quantity'] ?? 0);
            $this->activityLog->log(
                $userId,
                $itemId,
                'create',
                null,
                $qty,
                "Created new item '{$data['name']}' (SKU: {$data['sku']}) with initial quantity {$qty}"
            );

            $this->notification->createForAllUsers(
                'create',
                "New item created",
                "'{$data['name']}' (SKU: {$data['sku']}) was added with quantity {$qty}",
                'index.php?page=items'
            );

            return [
                'success' => true,
                'message' => 'Item created successfully.',
                'data'    => ['id' => $itemId]
            ];
        }

        return ['success' => false, 'message' => 'Failed to create item.'];
    }

    public function updateItem(int $id, array $data, ?int $userId): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid item ID.'];
        }

        $existing = $this->itemModel->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Item not found.'];
        }

        $errors = $this->validateItemData($data);
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        if ($this->itemModel->existsSku($data['sku'], $id)) {
            return ['success' => false, 'message' => "SKU '{$data['sku']}' is already used by another item."];
        }

        $categoryId = (int) $data['category_id'];
        $category = $this->categoryModel->getById($categoryId);
        if (!$category) {
            return ['success' => false, 'message' => 'Selected category is invalid.'];
        }

        $oldQty = (int) $existing['quantity'];
        $newQty = (int) $data['quantity'];

        $updated = $this->itemModel->update($id, $data);
        if ($updated) {
            $actionType = 'update';
            if ($newQty > $oldQty) {
                $actionType = 'stock_in';
            } elseif ($newQty < $oldQty) {
                $actionType = 'stock_out';
            }

            $description = "Updated item '{$data['name']}' (SKU: {$data['sku']})";
            if ($newQty !== $oldQty) {
                $description .= " quantity changed from {$oldQty} to {$newQty}";
            }

            $this->activityLog->log($userId, $id, $actionType, $oldQty, $newQty, $description);

            $diff = $newQty - $oldQty;
            if ($diff > 0) {
                $this->notification->createForAllUsers(
                    'stock_in',
                    "Stock increased",
                    "'{$data['name']}' stock increased by {$diff} to {$newQty}",
                    'index.php?page=items'
                );
            } elseif ($diff < 0) {
                $this->notification->createForAllUsers(
                    'stock_out',
                    "Stock decreased",
                    "'{$data['name']}' stock decreased by " . abs($diff) . " to {$newQty}",
                    'index.php?page=items'
                );
            } else {
                $this->notification->createForAllUsers(
                    'update',
                    "Item updated",
                    "'{$data['name']}' (SKU: {$data['sku']}) was updated",
                    'index.php?page=items'
                );
            }

            if ($newQty <= (int) $data['low_stock_threshold']) {
                if (!$this->notification->hasRecentGlobalNotification('low_stock', 1800)) {
                    $this->notification->createForAllUsers(
                        'low_stock',
                        "Low stock alert",
                        "'{$data['name']}' is low on stock ({$newQty} remaining)",
                        'index.php?page=items'
                    );
                }
            }

            return [
                'success' => true,
                'message' => 'Item updated successfully.'
            ];
        }

        return ['success' => false, 'message' => 'Failed to update item.'];
    }

    public function deleteItem(int $id, ?int $userId): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid item ID.'];
        }

        $existing = $this->itemModel->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Item not found.'];
        }

        $deleted = $this->itemModel->delete($id);
        if ($deleted) {
            $this->activityLog->log(
                $userId,
                $id,
                'delete',
                (int) $existing['quantity'],
                0,
                "Deleted item '{$existing['name']}' (SKU: {$existing['sku']})"
            );

            $this->notification->createForAllUsers(
                'delete',
                "Item deleted",
                "'{$existing['name']}' (SKU: {$existing['sku']}) has been removed",
                'index.php?page=items'
            );

            return [
                'success' => true,
                'message' => 'Item deleted successfully.'
            ];
        }

        return ['success' => false, 'message' => 'Failed to delete item.'];
    }

    private function validateItemData(array $data): array
    {
        $errors = [];

        if (empty($data['sku'])) {
            $errors[] = 'SKU is required.';
        } elseif (mb_strlen($data['sku']) > 50) {
            $errors[] = 'SKU cannot exceed 50 characters.';
        }

        if (empty($data['name'])) {
            $errors[] = 'Item name is required.';
        } elseif (mb_strlen($data['name']) > 150) {
            $errors[] = 'Item name cannot exceed 150 characters.';
        }

        if (empty($data['category_id']) || (int) $data['category_id'] <= 0) {
            $errors[] = 'Please select a valid category.';
        }

        if (!isset($data['price']) || !is_numeric($data['price']) || (float) $data['price'] < 0) {
            $errors[] = 'Price must be a positive number.';
        }

        if (!isset($data['quantity']) || filter_var($data['quantity'], FILTER_VALIDATE_INT) === false || (int) $data['quantity'] < 0) {
            $errors[] = 'Quantity must be a non-negative integer.';
        }

        if (!isset($data['low_stock_threshold']) || filter_var($data['low_stock_threshold'], FILTER_VALIDATE_INT) === false || (int) $data['low_stock_threshold'] < 0) {
            $errors[] = 'Low stock threshold must be a non-negative integer.';
        }

        return $errors;
    }
}
