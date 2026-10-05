<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Shared setup for the conversion feature tests.
 *
 * The tests deliberately insert the legacy-compatible rows that the HTTP
 * contract promises. This keeps them focused on routes, validation, policies,
 * and Inertia props instead of factory/model implementation details.
 */
abstract class InventoryTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createUser(
        string $role = 'employee',
        ?string $username = null,
        string $password = 'password',
        ?string $fullName = null,
    ): User {
        $username ??= $role.'-'.uniqid('', true);
        $fullName ??= ucfirst($role).' User';
        $roleId = $this->roleId($role);

        $attributes = [
            'full_name' => $fullName,
            'username' => $username,
            'password_hash' => Hash::make($password),
            'role_id' => $roleId,
            'created_at' => now(),
        ];

        $id = $this->insertLegacyRow('users', $attributes);

        return User::query()->findOrFail($id);
    }

    protected function roleId(string $role): int
    {
        $role = match (strtolower($role)) {
            'admin', 'administrator' => 'Admin',
            'employee', 'staff' => 'Employee',
            default => $role,
        };

        $legacyId = match ($role) {
            'Admin' => 1,
            'Employee' => 2,
            default => null,
        };

        $existing = DB::table('roles')
            ->where('role_name', $role)
            ->when($legacyId !== null, fn ($query) => $query->orWhere('id', $legacyId))
            ->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        $attributes = ['role_name' => $role];
        if ($legacyId !== null) {
            $attributes = ['id' => $legacyId, ...$attributes];
        }
        if (Schema::hasColumn('roles', 'created_at')) {
            $attributes['created_at'] = now();
        }
        if (Schema::hasColumn('roles', 'updated_at')) {
            $attributes['updated_at'] = now();
        }

        if ($legacyId !== null) {
            DB::table('roles')->insert($attributes);

            return $legacyId;
        }

        return (int) DB::table('roles')->insertGetId($attributes);
    }

    protected function createCategory(string $name = 'Electronics'): int
    {
        return $this->insertLegacyRow('categories', [
            'category_name' => $name,
        ]);
    }

    protected function createItem(
        ?int $categoryId = null,
        string $sku = 'SKU-1001',
        string $name = 'Wireless Mouse',
        float $price = 25.50,
        int $quantity = 20,
        int $lowStockThreshold = 5,
        bool $isDeleted = false,
    ): int {
        $categoryId ??= $this->createCategory();

        $attributes = [
            'sku' => $sku,
            'name' => $name,
            'category_id' => $categoryId,
            'price' => $price,
            'quantity' => $quantity,
            'low_stock_threshold' => $lowStockThreshold,
        ];

        if (Schema::hasColumn('items', 'is_deleted')) {
            $attributes['is_deleted'] = $isDeleted ? 1 : 0;
        }

        return $this->insertLegacyRow('items', $attributes);
    }

    protected function createBatch(
        ?int $itemId = null,
        string $batchNumber = 'BATCH-1001',
        int $quantity = 10,
        ?string $expiryDate = null,
    ): int {
        $itemId ??= $this->createItem();

        return $this->insertLegacyRow('batches', [
            'item_id' => $itemId,
            'batch_number' => $batchNumber,
            'quantity' => $quantity,
            'expiry_date' => $expiryDate ?? now()->addDays(10)->toDateString(),
        ]);
    }

    protected function createActivityLog(
        ?int $userId = null,
        ?int $itemId = null,
        string $actionType = 'create',
        ?int $oldQuantity = null,
        ?int $newQuantity = 10,
        string $description = 'Inventory activity recorded by the test.',
    ): int {
        $attributes = [
            'user_id' => $userId,
            'item_id' => $itemId,
            'action_type' => $actionType,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'description' => $description,
        ];

        return $this->insertLegacyRow('activity_log', $attributes);
    }

    protected function createNotification(
        int $userId,
        string $title = 'Inventory update',
        string $message = 'Your inventory was updated.',
        bool $isRead = false,
    ): int {
        $attributes = [
            'user_id' => $userId,
            'type' => 'inventory',
            'title' => $title,
            'message' => $message,
            'link' => '/items',
            'is_read' => $isRead ? 1 : 0,
        ];

        return $this->insertLegacyRow('notifications', $attributes);
    }

    /**
     * Insert the required legacy columns and add timestamps only when the
     * Laravel migration has opted into them. The optional-column check keeps
     * the tests compatible with a migration that adds standard timestamps
     * without making those columns part of the domain contract.
     */
    protected function insertLegacyRow(string $table, array $attributes): int
    {
        $available = Schema::getColumnListing($table);
        $now = now();

        foreach (['created_at', 'updated_at', 'deleted_at'] as $timestamp) {
            if (in_array($timestamp, $available, true) && ! array_key_exists($timestamp, $attributes)) {
                $attributes[$timestamp] = $now;
            }
        }

        return (int) DB::table($table)->insertGetId($attributes);
    }

    protected function assertInertiaPageContains(TestResponse $response, string $value): void
    {
        $response->assertSuccessful();

        // Inertia embeds its page props in the HTML data-page attribute. This
        // intentionally checks the public prop payload without prescribing
        // whether a controller calls the value `items`, `data`, or `rows`.
        $this->assertStringContainsString(
            $value,
            $response->getContent(),
            "Expected the Inertia page payload to contain [{$value}].",
        );
    }
}
