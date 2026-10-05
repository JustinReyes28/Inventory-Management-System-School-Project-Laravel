<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;

class ReportDashboardTest extends InventoryTestCase
{
    public function test_dashboard_activity_requires_the_activity_log_permission(): void
    {
        $actor = $this->createUser('admin', 'restricted.actor');
        $viewer = $this->createUser('user', 'restricted.viewer');
        $logId = $this->createActivityLog($actor->id, description: 'Restricted activity record.');

        $this->actingAs($viewer)->get('/activity-logs')->assertForbidden();
        $response = $this->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->missing('recent_activity')
        );
        $this->assertStringNotContainsString('Restricted activity record.', $response->getContent());

        $viewer->givePermissionTo('view activity logs');
        $this->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('recent_activity', 1)
            ->where('recent_activity.0.id', $logId)
        );
    }

    public function test_dashboard_contains_active_stock_expiry_and_recent_activity_data(): void
    {
        $user = $this->createUser('employee', 'dashboard.metrics');
        $categoryId = $this->createCategory('Dashboard hardware');
        $nearExpiryItem = $this->createItem(
            categoryId: $categoryId,
            sku: 'DASH-NEAR-1001',
            name: 'Near Expiry Adapter',
            quantity: 3,
            lowStockThreshold: 5,
        );
        $healthyItem = $this->createItem(
            categoryId: $categoryId,
            sku: 'DASH-HEALTHY-1001',
            name: 'Healthy Adapter',
            quantity: 40,
            lowStockThreshold: 5,
        );

        $this->createBatch(
            itemId: $nearExpiryItem,
            batchNumber: 'DASH-NEAR-BATCH',
            quantity: 3,
            expiryDate: now()->addDays(10)->toDateString(),
        );

        $this->createActivityLog(
            userId: $user->id,
            itemId: $nearExpiryItem,
            actionType: 'stock_out',
            oldQuantity: 8,
            newQuantity: 3,
            description: 'Dashboard activity should show this stock movement.',
        );

        $response = $this->actingAs($user)
            ->get('/dashboard')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('metrics.total_products', 2)
                ->where('metrics.low_stock_count', 1)
                ->where('metrics.near_expiry_count', 1)
                ->where('chart.labels', ['Dashboard hardware'])
            );

        $this->assertInertiaPageContains($response, 'Dashboard hardware');
        $this->assertInertiaPageContains($response, 'DASH-NEAR-BATCH');
        $this->assertInertiaPageContains($response, 'Dashboard activity should show this stock movement.');
    }

    public function test_reports_include_items_at_or_below_threshold_and_expiry_records(): void
    {
        $user = $this->createUser('employee', 'reports.viewer');
        $lowCategory = $this->createCategory('Report low stock');
        $otherCategory = $this->createCategory('Report healthy stock');

        $lowItem = $this->createItem(
            categoryId: $lowCategory,
            sku: 'REPORT-LOW-1001',
            name: 'At Threshold Product',
            quantity: 5,
            lowStockThreshold: 5,
        );
        $outOfStockItem = $this->createItem(
            categoryId: $lowCategory,
            sku: 'REPORT-ZERO-1001',
            name: 'Out Of Stock Product',
            quantity: 0,
            lowStockThreshold: 5,
        );
        $healthyItem = $this->createItem(
            categoryId: $otherCategory,
            sku: 'REPORT-HEALTHY-1001',
            name: 'Healthy Product',
            quantity: 50,
            lowStockThreshold: 5,
        );
        $this->createItem(
            categoryId: $otherCategory,
            sku: 'REPORT-ARCHIVED-1001',
            name: 'Archived Product',
            quantity: 1,
            lowStockThreshold: 5,
            isDeleted: true,
        );

        $this->createBatch(
            itemId: $lowItem,
            batchNumber: 'REPORT-NEAR-BATCH',
            expiryDate: now()->addDays(15)->toDateString(),
        );
        $this->createBatch(
            itemId: $healthyItem,
            batchNumber: 'REPORT-EXPIRED-BATCH',
            expiryDate: now()->subDays(1)->toDateString(),
        );

        $response = $this->actingAs($user)
            ->get('/reports')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Reports/Index')
                ->where('overview.low_stock_count', 2)
                ->where('overview.low_stock_value', 127.5)
                ->where('overview.expiring_30', 1)
                ->where('overview.total_actions', 0)
                ->where('filters.tab', 'low-stock')
            );

        // A threshold is inclusive: quantity equal to the threshold belongs
        // in the low-stock report, as does an out-of-stock item.
        $this->assertInertiaPageContains($response, 'REPORT-LOW-1001');
        $this->assertInertiaPageContains($response, 'REPORT-ZERO-1001');
        $this->assertInertiaPageContains($response, 'REPORT-NEAR-BATCH');
        $this->assertInertiaPageContains($response, 'REPORT-EXPIRED-BATCH');
        $this->assertStringNotContainsString('REPORT-ARCHIVED-1001', $response->getContent());

        $this->actingAs($user)
            ->get('/reports?days=30')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Reports/Index'));
    }
}
