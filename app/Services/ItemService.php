<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\NotificationType;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ItemService
{
    private const MAX_PRICE = 99999999.99;

    private const MAX_UNSIGNED_INTEGER = 4_294_967_295;

    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @return Collection<int, Item>
     */
    public function list(
        ?string $search = null,
        ?int $categoryId = null,
        bool $lowStockOnly = false,
    ): Collection {
        return Item::query()
            ->with('category:id,category_name')
            ->when(filled($search), function (Builder $query) use ($search): void {
                $escaped = str_replace(
                    ['!', '%', '_'],
                    ['!!', '!%', '!_'],
                    trim((string) $search),
                );
                $pattern = "%{$escaped}%";

                $query->where(function (Builder $query) use ($pattern): void {
                    $query->whereRaw("(sku LIKE ? ESCAPE '!' OR name LIKE ? ESCAPE '!')", [
                        $pattern,
                        $pattern,
                    ]);
                });
            })
            ->when($categoryId !== null && $categoryId > 0, fn (Builder $query) => $query
                ->where('category_id', $categoryId))
            ->when($lowStockOnly, fn (Builder $query) => $query
                ->whereColumn('quantity', '<=', 'low_stock_threshold'))
            ->latest('id')
            ->get();
    }

    /**
     * @param  array{sku: string, name: string, category_id: int|string, price: int|float|string, quantity?: int|string, low_stock_threshold?: int|string}  $attributes
     */
    public function create(array $attributes, ?User $actor = null): Item
    {
        $values = $this->normalizeAttributes($attributes, true);

        return DB::transaction(function () use ($values, $actor): Item {
            Category::query()->findOrFail($values['category_id']);

            $item = Item::query()->create($values);
            $quantity = (int) $item->quantity;

            $this->activityLog->log(
                $actor,
                $item,
                ActivityAction::CREATE,
                null,
                $quantity,
                "Created new item '{$item->name}' (SKU: {$item->sku}) with initial quantity {$quantity}",
            );

            $this->notifications->notifyAll(
                NotificationType::CREATE,
                'New item created',
                "'{$item->name}' (SKU: {$item->sku}) was added with quantity {$quantity}",
                'items',
            );

            if ($item->isLowStock()) {
                $this->notifyLowStock($item, 30 * 60);
            }

            return $item->load('category:id,category_name');
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Item $item, array $attributes, ?User $actor = null): Item
    {
        $values = $this->normalizeAttributes($attributes, false);

        return DB::transaction(function () use ($item, $values, $actor): Item {
            $locked = Item::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $oldQuantity = (int) $locked->quantity;
            $wasLowStock = $locked->isLowStock();

            if (array_key_exists('category_id', $values)) {
                Category::query()->findOrFail($values['category_id']);
            }

            $locked->fill($values);
            $locked->save();

            $newQuantity = (int) $locked->quantity;
            $action = match (true) {
                $newQuantity > $oldQuantity => ActivityAction::STOCK_IN,
                $newQuantity < $oldQuantity => ActivityAction::STOCK_OUT,
                default => ActivityAction::UPDATE,
            };

            $description = "Updated item '{$locked->name}' (SKU: {$locked->sku})";
            if ($newQuantity !== $oldQuantity) {
                $description .= " quantity changed from {$oldQuantity} to {$newQuantity}";
            }

            $this->activityLog->log(
                $actor,
                $locked,
                $action,
                $oldQuantity,
                $newQuantity,
                $description,
            );

            $difference = $newQuantity - $oldQuantity;
            if ($difference > 0) {
                $this->notifications->notifyAll(
                    NotificationType::STOCK_IN,
                    'Stock increased',
                    "'{$locked->name}' stock increased by {$difference} to {$newQuantity}",
                    'items',
                );
            } elseif ($difference < 0) {
                $this->notifications->notifyAll(
                    NotificationType::STOCK_OUT,
                    'Stock decreased',
                    "'{$locked->name}' stock decreased by ".abs($difference)." to {$newQuantity}",
                    'items',
                );
            } else {
                $this->notifications->notifyAll(
                    NotificationType::UPDATE,
                    'Item updated',
                    "'{$locked->name}' (SKU: {$locked->sku}) was updated",
                    'items',
                );
            }

            if (! $wasLowStock && $locked->isLowStock()) {
                $this->notifyLowStock($locked, 30 * 60);
            }

            return $locked->load('category:id,category_name');
        }, 3);
    }

    public function delete(Item $item, ?User $actor = null): void
    {
        DB::transaction(function () use ($item, $actor): void {
            $locked = Item::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $quantity = (int) $locked->quantity;
            $name = $locked->name;
            $sku = $locked->sku;

            // Legacy inventory uses an explicit archive flag rather than a
            // physical delete so batch and activity history remains valid.
            $locked->is_deleted = true;
            $locked->save();

            $this->activityLog->log(
                $actor,
                $locked,
                ActivityAction::DELETE,
                $quantity,
                0,
                "Deleted item '{$name}' (SKU: {$sku})",
            );

            $this->notifications->notifyAll(
                NotificationType::DELETE,
                'Item deleted',
                "'{$name}' (SKU: {$sku}) has been removed",
                'items',
            );
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, int|float|string>
     */
    private function normalizeAttributes(array $attributes, bool $creating): array
    {
        $values = Arr::only($attributes, [
            'sku',
            'name',
            'category_id',
            'price',
            'quantity',
            'low_stock_threshold',
        ]);

        if ($creating) {
            foreach (['sku', 'name', 'category_id', 'price'] as $required) {
                if (! array_key_exists($required, $values)) {
                    throw new InvalidArgumentException("Item [{$required}] is required.");
                }
            }
        }

        if (array_key_exists('sku', $values)) {
            $values['sku'] = trim((string) $values['sku']);
            if ($values['sku'] === '' || mb_strlen($values['sku']) > 50) {
                throw new InvalidArgumentException('Item SKU must contain 1 to 50 characters.');
            }
        }

        if (array_key_exists('name', $values)) {
            $values['name'] = trim((string) $values['name']);
            if ($values['name'] === '' || mb_strlen($values['name']) > 150) {
                throw new InvalidArgumentException('Item name must contain 1 to 150 characters.');
            }
        }

        if (array_key_exists('category_id', $values)) {
            $categoryId = filter_var($values['category_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($categoryId === false) {
                throw new InvalidArgumentException('A valid item category is required.');
            }
            $values['category_id'] = $categoryId;
        }

        if (array_key_exists('price', $values)) {
            $price = (float) $values['price'];
            if (! is_numeric($values['price']) || ! is_finite($price) || $price < 0 || $price > self::MAX_PRICE) {
                throw new InvalidArgumentException('Item price must be between 0 and 99999999.99.');
            }
            $values['price'] = round($price, 2);
        }

        foreach (['quantity', 'low_stock_threshold'] as $integerField) {
            if (array_key_exists($integerField, $values)) {
                $integer = filter_var(
                    $values[$integerField],
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 0, 'max_range' => self::MAX_UNSIGNED_INTEGER]],
                );
                if ($integer === false) {
                    throw new InvalidArgumentException("Item {$integerField} is outside the supported unsigned integer range.");
                }
                $values[$integerField] = $integer;
            }
        }

        if ($creating) {
            $values += [
                'quantity' => 0,
                'low_stock_threshold' => 10,
            ];
        }

        return $values;
    }

    private function notifyLowStock(Item $item, int $withinSeconds): void
    {
        if ($this->notifications->hasRecentGlobal(NotificationType::LOW_STOCK, $withinSeconds)) {
            return;
        }

        $this->notifications->notifyAll(
            NotificationType::LOW_STOCK,
            'Low stock alert',
            "'{$item->name}' is low on stock ({$item->quantity} remaining)",
            'items',
        );
    }
}
