<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

class InventoryResourceTest extends InventoryTestCase
{
    public function test_categories_support_create_validation_and_listing(): void
    {
        $user = $this->createUser('employee', 'category.operator');

        $this->actingAs($user)
            ->post('/categories', ['category_name' => 'Office supplies'])
            ->assertRedirect('/categories');

        $this->assertDatabaseHas('categories', ['category_name' => 'Office supplies']);

        $this->actingAs($user)
            ->from('/categories')
            ->post('/categories', ['category_name' => ''])
            ->assertRedirect('/categories')
            ->assertSessionHasErrors('category_name');

        $this->actingAs($user)
            ->post('/categories', ['category_name' => 'Office supplies'])
            ->assertSessionHasErrors('category_name');

        $categoryId = (int) DB::table('categories')->where('category_name', 'Office supplies')->value('id');

        $this->actingAs($user)
            ->put('/categories/'.$categoryId, ['category_name' => 'Office supplies updated'])
            ->assertRedirect('/categories');
        $this->assertDatabaseHas('categories', [
            'id' => $categoryId,
            'category_name' => 'Office supplies updated',
        ]);

        $this->actingAs($user)
            ->get('/categories')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Categories/Index'));

        $this->actingAs($user)
            ->delete('/categories/'.$categoryId)
            ->assertRedirect('/categories');
        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
    }

    public function test_items_support_create_validation_update_and_soft_delete(): void
    {
        $user = $this->createUser('employee', 'item.operator');
        $categoryId = $this->createCategory('Hardware');

        $this->actingAs($user)
            ->post('/items', [
                'sku' => 'HW-1001',
                'name' => 'USB-C Dock',
                'category_id' => $categoryId,
                'price' => '89.95',
                'quantity' => 12,
                'low_stock_threshold' => 4,
            ])
            ->assertRedirect('/items');

        $this->assertDatabaseHas('items', [
            'sku' => 'HW-1001',
            'name' => 'USB-C Dock',
            'category_id' => $categoryId,
            'quantity' => 12,
            'low_stock_threshold' => 4,
        ]);

        $this->actingAs($user)
            ->from('/items')
            ->post('/items', [
                'sku' => '',
                'name' => '',
                'category_id' => 999999,
                'price' => -1,
                'quantity' => -1,
                'low_stock_threshold' => -1,
            ])
            ->assertRedirect('/items')
            ->assertSessionHasErrors([
                'sku',
                'name',
                'category_id',
                'price',
                'quantity',
                'low_stock_threshold',
            ]);

        $itemId = (int) DB::table('items')->where('sku', 'HW-1001')->value('id');

        $this->actingAs($user)
            ->put('/items/'.$itemId, [
                'sku' => 'HW-1001',
                'name' => 'USB-C Dock Pro',
                'category_id' => $categoryId,
                'price' => '99.95',
                'quantity' => 8,
                'low_stock_threshold' => 4,
            ])
            ->assertRedirect('/items');

        $this->assertDatabaseHas('items', [
            'id' => $itemId,
            'name' => 'USB-C Dock Pro',
            'quantity' => 8,
        ]);

        $this->actingAs($user)
            ->delete('/items/'.$itemId)
            ->assertRedirect('/items');

        $this->assertDatabaseHas('items', [
            'id' => $itemId,
            'is_deleted' => 1,
        ]);
    }

    public function test_archived_items_are_not_returned_in_the_active_item_page(): void
    {
        $user = $this->createUser('employee', 'archive.operator');
        $this->createItem(sku: 'ARCHIVED-ONLY-1001', name: 'Archived Product', isDeleted: true);

        $this->actingAs($user)
            ->get('/items')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Items/Index'))
            ->assertDontSee('ARCHIVED-ONLY-1001');
    }

    public function test_batches_support_crud_and_reject_invalid_item_quantity_and_date(): void
    {
        $user = $this->createUser('employee', 'batch.operator');
        $itemId = $this->createItem(sku: 'BATCH-ITEM-1001', name: 'Perishable Item');

        $this->actingAs($user)
            ->post('/batches', [
                'item_id' => $itemId,
                'batch_number' => 'BATCH-A',
                'quantity' => 25,
                'expiry_date' => now()->addDays(20)->toDateString(),
            ])
            ->assertRedirect('/batches');

        $this->assertDatabaseHas('batches', [
            'item_id' => $itemId,
            'batch_number' => 'BATCH-A',
            'quantity' => 25,
        ]);

        $this->actingAs($user)
            ->from('/batches')
            ->post('/batches', [
                'item_id' => 999999,
                'batch_number' => '',
                'quantity' => -1,
                'expiry_date' => 'not-a-date',
            ])
            ->assertRedirect('/batches')
            ->assertSessionHasErrors([
                'item_id',
                'batch_number',
                'quantity',
                'expiry_date',
            ]);

        $batchId = (int) DB::table('batches')->where('batch_number', 'BATCH-A')->value('id');

        $this->actingAs($user)
            ->put('/batches/'.$batchId, [
                'item_id' => $itemId,
                'batch_number' => 'BATCH-A-UPDATED',
                'quantity' => 18,
                'expiry_date' => now()->addDays(45)->toDateString(),
            ])
            ->assertRedirect('/batches');

        $this->assertDatabaseHas('batches', [
            'id' => $batchId,
            'batch_number' => 'BATCH-A-UPDATED',
            'quantity' => 18,
        ]);

        $this->actingAs($user)
            ->delete('/batches/'.$batchId)
            ->assertRedirect('/batches');

        $this->assertDatabaseMissing('batches', ['id' => $batchId]);
    }

    public function test_inventory_mutations_are_recorded_in_the_activity_log(): void
    {
        $user = $this->createUser('employee', 'activity.operator');
        $categoryId = $this->createCategory('Logged equipment');

        $this->actingAs($user)
            ->post('/items', [
                'sku' => 'LOG-1001',
                'name' => 'Logged Item',
                'category_id' => $categoryId,
                'price' => '10.00',
                'quantity' => 4,
                'low_stock_threshold' => 5,
            ])
            ->assertRedirect('/items');

        $itemId = (int) DB::table('items')->where('sku', 'LOG-1001')->value('id');

        $this->assertDatabaseHas('activity_log', [
            'user_id' => $user->id,
            'item_id' => $itemId,
            'action_type' => 'create',
        ]);

        $this->actingAs($user)
            ->put('/items/'.$itemId, [
                'sku' => 'LOG-1001',
                'name' => 'Logged Item',
                'category_id' => $categoryId,
                'price' => '10.00',
                'quantity' => 9,
                'low_stock_threshold' => 5,
            ])
            ->assertRedirect('/items');

        $this->assertDatabaseHas('activity_log', [
            'user_id' => $user->id,
            'item_id' => $itemId,
            'action_type' => 'stock_in',
            'old_quantity' => 4,
            'new_quantity' => 9,
        ]);

        $activityResponse = $this->actingAs($user)
            ->get('/activity-logs')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('ActivityLogs/Index'));

        $this->assertInertiaPageContains($activityResponse, 'stock_in');
    }
}
