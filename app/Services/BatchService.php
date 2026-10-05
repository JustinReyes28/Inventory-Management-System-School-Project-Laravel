<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\NotificationType;
use App\Models\Batch;
use App\Models\Item;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BatchService
{
    private const MAX_UNSIGNED_INTEGER = 4_294_967_295;

    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array{item_id: int|string, batch_number: string, quantity?: int|string, expiry_date: DateTimeInterface|string}  $attributes
     */
    public function create(array $attributes, ?User $actor = null): Batch
    {
        $values = $this->normalizeAttributes($attributes, true);

        return DB::transaction(function () use ($values, $actor): Batch {
            $item = Item::query()->findOrFail($values['item_id']);
            $batch = Batch::query()->create($values);
            $quantity = (int) $batch->quantity;
            $expiryDate = $batch->expiry_date->toDateString();

            $this->activityLog->log(
                $actor,
                $item,
                ActivityAction::CREATE,
                null,
                $quantity,
                "Created batch '{$batch->batch_number}' for item '{$item->name}' with quantity {$quantity}, expiring {$expiryDate}",
            );

            $this->notifications->notifyAll(
                NotificationType::CREATE,
                'New batch created',
                "Batch '{$batch->batch_number}' for '{$item->name}' ({$quantity} units, expires {$expiryDate})",
                'batches',
            );

            if ($this->isNearExpiry($batch->expiry_date)) {
                $this->notifyNearExpiry($batch, $item);
            }

            return $batch->load('item:id,sku,name,category_id');
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Batch $batch, array $attributes, ?User $actor = null): Batch
    {
        $values = $this->normalizeAttributes($attributes, false);

        return DB::transaction(function () use ($batch, $values, $actor): Batch {
            $locked = Batch::query()
                ->whereKey($batch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $oldItem = $locked->item()->withDeleted()->first();
            $oldQuantity = (int) $locked->quantity;
            $oldBatchNumber = $locked->batch_number;
            $oldExpiry = $locked->expiry_date->copy();
            $wasNearExpiry = $this->isNearExpiry($oldExpiry);

            $item = array_key_exists('item_id', $values)
                ? Item::query()->findOrFail($values['item_id'])
                : $oldItem;

            $locked->fill($values);
            $locked->save();

            $newQuantity = (int) $locked->quantity;
            $newExpiry = $locked->expiry_date;
            $description = "Updated batch '{$locked->batch_number}' for item '".($item?->name ?? 'unknown item')."'";
            $description .= " — quantity: {$oldQuantity} → {$newQuantity}, expiry: {$oldExpiry->toDateString()} → {$newExpiry->toDateString()}";

            $this->activityLog->log(
                $actor,
                $item,
                ActivityAction::UPDATE,
                $oldQuantity,
                $newQuantity,
                $description,
            );

            $this->notifications->notifyAll(
                NotificationType::UPDATE,
                'Batch updated',
                "Batch '{$locked->batch_number}' for '".($item?->name ?? 'unknown item')."' was updated",
                'batches',
            );

            $identityChanged = $oldItem?->getKey() !== $item?->getKey()
                || $oldBatchNumber !== $locked->batch_number
                || $oldExpiry->toDateString() !== $newExpiry->toDateString();

            if ((! $wasNearExpiry || $identityChanged) && $this->isNearExpiry($newExpiry)) {
                $this->notifyNearExpiry($locked, $item);
            }

            return $locked->load('item:id,sku,name,category_id');
        }, 3);
    }

    public function delete(Batch $batch, ?User $actor = null): void
    {
        DB::transaction(function () use ($batch, $actor): void {
            $locked = Batch::query()
                ->whereKey($batch->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $item = $locked->item()->withDeleted()->first();
            $number = $locked->batch_number;
            $quantity = (int) $locked->quantity;

            $locked->delete();

            $this->activityLog->log(
                $actor,
                $item,
                ActivityAction::DELETE,
                $quantity,
                0,
                "Deleted batch '{$number}' for item '".($item?->name ?? 'unknown item')."'",
            );

            $this->notifications->notifyAll(
                NotificationType::DELETE,
                'Batch deleted',
                "Batch '{$number}' for '".($item?->name ?? 'unknown item')."' has been removed",
                'batches',
            );
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, int|string>
     */
    private function normalizeAttributes(array $attributes, bool $creating): array
    {
        $values = Arr::only($attributes, [
            'item_id',
            'batch_number',
            'quantity',
            'expiry_date',
        ]);

        if ($creating) {
            foreach (['item_id', 'batch_number', 'expiry_date'] as $required) {
                if (! array_key_exists($required, $values)) {
                    throw new InvalidArgumentException("Batch [{$required}] is required.");
                }
            }
        }

        if (array_key_exists('item_id', $values)) {
            $itemId = filter_var($values['item_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($itemId === false) {
                throw new InvalidArgumentException('A valid batch item is required.');
            }
            $values['item_id'] = $itemId;
        }

        if (array_key_exists('batch_number', $values)) {
            $values['batch_number'] = trim((string) $values['batch_number']);
            if ($values['batch_number'] === '' || mb_strlen($values['batch_number']) > 50) {
                throw new InvalidArgumentException('Batch number must contain 1 to 50 characters.');
            }
        }

        if (array_key_exists('quantity', $values)) {
            $quantity = filter_var(
                $values['quantity'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => self::MAX_UNSIGNED_INTEGER]],
            );
            if ($quantity === false) {
                throw new InvalidArgumentException('Batch quantity is outside the supported unsigned integer range.');
            }
            $values['quantity'] = $quantity;
        }

        if (array_key_exists('expiry_date', $values)) {
            $values['expiry_date'] = $this->normalizeDate($values['expiry_date'])->toDateString();
        }

        if ($creating) {
            $values += ['quantity' => 0];
        }

        return $values;
    }

    private function normalizeDate(DateTimeInterface|string $value): Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        $date = Carbon::createFromFormat('!Y-m-d', trim($value));
        if (! $date->isValid() || $date->toDateString() !== trim($value)) {
            throw new InvalidArgumentException('Batch expiry date must use YYYY-MM-DD.');
        }

        return $date;
    }

    private function isNearExpiry(Carbon $expiryDate): bool
    {
        $today = today();

        return ! $expiryDate->isBefore($today) && ! $expiryDate->isAfter($today->copy()->addDays(30));
    }

    private function notifyNearExpiry(Batch $batch, ?Item $item): void
    {
        $itemName = $item?->name ?? 'unknown item';
        $expiryDate = $batch->expiry_date->toDateString();

        $this->notifications->notifyAll(
            NotificationType::NEAR_EXPIRY,
            'Batch expiring soon',
            "Batch '{$batch->batch_number}' for '{$itemName}' expires on {$expiryDate}",
            'batches',
        );
    }
}
