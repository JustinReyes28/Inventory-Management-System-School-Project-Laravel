<?php

require_once __DIR__ . '/../classes/Batch.php';
require_once __DIR__ . '/../classes/Item.php';
require_once __DIR__ . '/../classes/ActivityLog.php';
require_once __DIR__ . '/../classes/Notification.php';

class BatchController
{
    private $batchModel;
    private $itemModel;
    private $activityLog;
    private $notification;

    public function __construct()
    {
        $this->batchModel = new Batch();
        $this->itemModel = new Item();
        $this->activityLog = new ActivityLog();
        $this->notification = new Notification();
    }

    public function listBatches(?int $itemId = null): array
    {
        return [
            'success' => true,
            'data'    => $this->batchModel->getAll($itemId),
        ];
    }

    public function getBatch(int $id): array
    {
        $batch = $this->batchModel->getById($id);
        if (!$batch) {
            return ['success' => false, 'message' => 'Batch not found.'];
        }
        return ['success' => true, 'data' => $batch];
    }

    public function createBatch(array $data, ?int $userId): array
    {
        $errors = $this->validateBatchData($data);
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $itemId = (int) $data['item_id'];
        $item = $this->itemModel->getById($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Selected item is invalid.'];
        }

        $batchId = $this->batchModel->create($data);
        if ($batchId) {
            $this->activityLog->log(
                $userId,
                $itemId,
                'create',
                null,
                (int) $data['quantity'],
                "Created batch '{$data['batch_number']}' for item '{$item['name']}' with quantity {$data['quantity']}, expiring {$data['expiry_date']}"
            );

            $this->notification->createForAllUsers(
                'create',
                "New batch created",
                "Batch '{$data['batch_number']}' for '{$item['name']}' ({$data['quantity']} units, expires {$data['expiry_date']})",
                'index.php?page=batches'
            );

            $expiryDate = \DateTime::createFromFormat('Y-m-d', $data['expiry_date']);
            $thirtyDaysFromNow = (new \DateTime())->modify('+30 days');
            if ($expiryDate <= $thirtyDaysFromNow && $expiryDate >= new \DateTime()) {
                $this->notification->createForAllUsers(
                    'near_expiry',
                    "Batch expiring soon",
                    "Batch '{$data['batch_number']}' for '{$item['name']}' expires on {$data['expiry_date']}",
                    'index.php?page=batches'
                );
            }

            return [
                'success' => true,
                'message' => 'Batch created successfully.',
                'data'    => ['id' => $batchId],
            ];
        }

        return ['success' => false, 'message' => 'Failed to create batch.'];
    }

    public function updateBatch(int $id, array $data, ?int $userId): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid batch ID.'];
        }

        $existing = $this->batchModel->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Batch not found.'];
        }

        $errors = $this->validateBatchData($data);
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $itemId = (int) $data['item_id'];
        $item = $this->itemModel->getById($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Selected item is invalid.'];
        }

        $updated = $this->batchModel->update($id, $data);
        if ($updated) {
            $this->activityLog->log(
                $userId,
                $itemId,
                'update',
                null,
                (int) $data['quantity'],
                "Updated batch '{$data['batch_number']}' for item '{$item['name']}' — quantity: {$existing['quantity']} → {$data['quantity']}, expiry: {$existing['expiry_date']} → {$data['expiry_date']}"
            );

            $this->notification->createForAllUsers(
                'update',
                "Batch updated",
                "Batch '{$data['batch_number']}' for '{$item['name']}' was updated",
                'index.php?page=batches'
            );

            return [
                'success' => true,
                'message' => 'Batch updated successfully.',
            ];
        }

        return ['success' => false, 'message' => 'Failed to update batch.'];
    }

    public function deleteBatch(int $id, ?int $userId): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid batch ID.'];
        }

        $existing = $this->batchModel->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Batch not found.'];
        }

        $deleted = $this->batchModel->delete($id);
        if ($deleted) {
            $this->activityLog->log(
                $userId,
                (int) $existing['item_id'],
                'delete',
                (int) $existing['quantity'],
                0,
                "Deleted batch '{$existing['batch_number']}' for item '{$existing['item_name']}'"
            );

            $this->notification->createForAllUsers(
                'delete',
                "Batch deleted",
                "Batch '{$existing['batch_number']}' for '{$existing['item_name']}' has been removed",
                'index.php?page=batches'
            );

            return [
                'success' => true,
                'message' => 'Batch deleted successfully.',
            ];
        }

        return ['success' => false, 'message' => 'Failed to delete batch.'];
    }

    private function validateBatchData(array $data): array
    {
        $errors = [];

        if (empty($data['item_id']) || (int) $data['item_id'] <= 0) {
            $errors[] = 'Please select a valid item.';
        }

        if (empty($data['batch_number'])) {
            $errors[] = 'Batch number is required.';
        } elseif (mb_strlen($data['batch_number']) > 50) {
            $errors[] = 'Batch number cannot exceed 50 characters.';
        }

        if (!isset($data['quantity']) || filter_var($data['quantity'], FILTER_VALIDATE_INT) === false || (int) $data['quantity'] < 0) {
            $errors[] = 'Quantity must be a non-negative integer.';
        }

        if (empty($data['expiry_date'])) {
            $errors[] = 'Expiry date is required.';
        } else {
            $d = \DateTime::createFromFormat('Y-m-d', $data['expiry_date']);
            if (!$d || $d->format('Y-m-d') !== $data['expiry_date']) {
                $errors[] = 'Expiry date must be a valid date (YYYY-MM-DD).';
            }
        }

        return $errors;
    }
}
