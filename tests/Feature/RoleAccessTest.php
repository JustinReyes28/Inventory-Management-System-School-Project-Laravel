<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\DB;

/**
 * Spatie RBAC behavior: permission sets per role, access to each restricted
 * action through direct requests, migrated assignments, and repeatable seeding.
 */
class RoleAccessTest extends InventoryTestCase
{
    public function test_seeded_roles_carry_the_documented_permission_sets(): void
    {
        $admin = $this->createUser('admin', 'perm.admin');
        $employee = $this->createUser('employee', 'perm.employee');
        $viewer = $this->createUser('user', 'perm.viewer');

        // Admin: all defined permissions, including user management.
        $this->assertTrue($admin->can('view users'));
        $this->assertTrue($admin->can('delete categories'));
        $this->assertTrue($admin->can('view reports'));

        // Employee: inventory CRUD, reports, activity viewing, notifications.
        $this->assertTrue($employee->can('create items'));
        $this->assertTrue($employee->can('update batches'));
        $this->assertTrue($employee->can('view activity logs'));
        $this->assertTrue($employee->can('manage notifications'));
        $this->assertFalse($employee->can('view users'));

        // User: inventory/report viewing and own notifications only.
        $this->assertTrue($viewer->can('view items'));
        $this->assertTrue($viewer->can('view categories'));
        $this->assertTrue($viewer->can('view reports'));
        $this->assertFalse($viewer->can('create items'));
        $this->assertFalse($viewer->can('delete batches'));
        $this->assertFalse($viewer->can('view activity logs'));
        $this->assertFalse($viewer->can('view users'));
    }

    public function test_migrated_assignments_resolve_through_spatie(): void
    {
        $admin = $this->createUser('admin', 'migrated.admin');
        $employee = $this->createUser('employee', 'migrated.employee');

        $this->assertUserHasRole($admin, 'admin');
        $this->assertUserHasRole($employee, 'employee');
        $this->assertSame([RoleName::ADMIN->value], $admin->getRoleNames()->all());
        $this->assertSame([RoleName::EMPLOYEE->value], $employee->getRoleNames()->all());

        $this->assertSame(1, User::query()->role(RoleName::ADMIN->value)->count());
    }

    public function test_permission_seeding_is_repeatable(): void
    {
        $counts = fn (): array => [
            DB::table('roles')->count(),
            DB::table('permissions')->count(),
            DB::table('role_has_permissions')->count(),
        ];

        $before = $counts();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame($before, $counts());
    }

    public function test_employee_can_use_inventory_crud_but_not_user_management(): void
    {
        $employee = $this->createUser('employee', 'matrix.employee');
        $categoryId = $this->createCategory('Matrix category');
        $itemId = $this->createItem($categoryId, 'MATRIX-1', 'Matrix Item');
        $batchId = $this->createBatch($itemId, 'MATRIX-B');

        $this->actingAs($employee)->get('/categories')->assertSuccessful();
        $this->actingAs($employee)->get('/items')->assertSuccessful();
        $this->actingAs($employee)->get('/batches')->assertSuccessful();
        $this->actingAs($employee)->get('/reports')->assertSuccessful();
        $this->actingAs($employee)->get('/activity-logs')->assertSuccessful();
        $this->actingAs($employee)->get('/notifications')->assertSuccessful();

        $this->actingAs($employee)->post('/categories', ['category_name' => 'Matrix new'])->assertRedirect('/categories');
        $this->actingAs($employee)->put('/categories/'.$categoryId, ['category_name' => 'Matrix renamed'])->assertRedirect('/categories');
        $this->actingAs($employee)->delete('/batches/'.$batchId)->assertRedirect('/batches');

        $this->actingAs($employee)->get('/users')->assertForbidden();
        $this->actingAs($employee)->post('/users', [
            'full_name' => 'Should Not Exist',
            'username' => 'should.not.exist.matrix',
            'password' => 'password123',
            'role_id' => $this->roleId('employee'),
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['username' => 'should.not.exist.matrix']);
    }

    public function test_registered_users_can_view_but_not_mutate_inventory(): void
    {
        $viewer = $this->createUser('user', 'matrix.viewer');
        $categoryId = $this->createCategory('Viewer category');
        $itemId = $this->createItem($categoryId, 'VIEWER-1', 'Viewer Item');
        $batchId = $this->createBatch($itemId, 'VIEWER-B');

        $this->actingAs($viewer)->get('/categories')->assertSuccessful();
        $this->actingAs($viewer)->get('/items')->assertSuccessful();
        $this->actingAs($viewer)->get('/batches')->assertSuccessful();
        $this->actingAs($viewer)->get('/reports')->assertSuccessful();
        $this->actingAs($viewer)->get('/notifications')->assertSuccessful();

        $this->actingAs($viewer)->post('/categories', ['category_name' => 'Nope'])->assertForbidden();
        $this->actingAs($viewer)->put('/items/'.$itemId, [
            'sku' => 'VIEWER-1',
            'name' => 'Hacked Item',
            'category_id' => $categoryId,
            'price' => 1,
            'quantity' => 1,
            'low_stock_threshold' => 1,
        ])->assertForbidden();
        $this->actingAs($viewer)->patch('/items/'.$itemId.'/archive')->assertForbidden();
        $this->actingAs($viewer)->delete('/batches/'.$batchId)->assertForbidden();
        $this->actingAs($viewer)->get('/activity-logs')->assertForbidden();
        $this->actingAs($viewer)->get('/users')->assertForbidden();
        $this->actingAs($viewer)->post('/users', [
            'full_name' => 'Escalation',
            'username' => 'escalation.viewer',
            'password' => 'password123',
            'role_id' => $this->roleId('admin'),
        ])->assertForbidden();

        $this->assertDatabaseHas('items', ['id' => $itemId, 'name' => 'Viewer Item']);
    }

    public function test_admins_reach_every_restricted_area(): void
    {
        $admin = $this->createUser('admin', 'matrix.admin');

        foreach (['/dashboard', '/items', '/categories', '/batches', '/activity-logs', '/reports', '/notifications', '/users', '/account'] as $path) {
            $this->actingAs($admin)->get($path)->assertSuccessful();
        }
    }

    public function test_web_group_runs_the_csrf_middleware(): void
    {
        // Ordinary feature tests bypass CSRF; this asserts the protection is
        // actually wired. The rejection behavior is exercised against a real
        // server in docs/VERIFICATION.md.
        $kernel = app(HttpKernel::class);
        $web = $kernel->getMiddlewareGroups()['web'];

        $this->assertTrue(
            collect($web)->contains(
                fn (string $middleware): bool => str_contains($middleware, 'PreventRequestForgery')
                    || str_contains($middleware, 'ValidateCsrfToken'),
            ),
            'The web middleware group must run Laravel CSRF protection.',
        );
    }
}
