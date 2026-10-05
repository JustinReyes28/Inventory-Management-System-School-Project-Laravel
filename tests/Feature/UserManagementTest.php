<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

class UserManagementTest extends InventoryTestCase
{
    public function test_only_admins_can_view_or_mutate_user_management(): void
    {
        $employee = $this->createUser('employee', 'employee.manager');
        $otherEmployee = $this->createUser('employee', 'other.employee');

        $this->actingAs($employee)->get('/users')->assertForbidden();
        $this->actingAs($employee)
            ->post('/users', [
                'full_name' => 'Should Not Exist',
                'username' => 'should.not.exist',
                'password' => 'password',
                'role_id' => 2,
            ])
            ->assertForbidden();
        $this->actingAs($employee)
            ->put('/users/'.$otherEmployee->id, [
                'full_name' => 'Changed By Employee',
                'username' => 'changed.by.employee',
                'password' => 'password',
                'role_id' => 2,
            ])
            ->assertForbidden();
        $this->actingAs($employee)
            ->delete('/users/'.$otherEmployee->id)
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'should.not.exist']);
        $this->assertDatabaseHas('users', [
            'id' => $otherEmployee->id,
            'username' => 'other.employee',
        ]);
    }

    public function test_admins_can_manage_users_with_username_role_and_password_validation(): void
    {
        $admin = $this->createUser('admin', 'system.admin');
        $employeeRoleId = $this->roleId('employee');

        $this->actingAs($admin)
            ->get('/users')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Users/Index'));

        $this->actingAs($admin)
            ->post('/users', [
                'full_name' => 'New Operator',
                'username' => 'new.operator',
                'password' => 'new-password',
                'role_id' => $employeeRoleId,
            ])
            ->assertRedirect('/users');

        $this->assertDatabaseHas('users', [
            'full_name' => 'New Operator',
            'username' => 'new.operator',
            'role_id' => $employeeRoleId,
        ]);

        $newUserId = (int) DB::table('users')->where('username', 'new.operator')->value('id');
        $this->assertTrue(Hash::check('new-password', (string) DB::table('users')->where('id', $newUserId)->value('password_hash')));

        $this->actingAs($admin)
            ->from('/users')
            ->post('/users', [
                'full_name' => '',
                'username' => 'new.operator',
                'password' => '',
                'role_id' => 0,
            ])
            ->assertRedirect('/users')
            ->assertSessionHasErrors([
                'full_name',
                'username',
                'password',
                'role_id',
            ]);

        $this->actingAs($admin)
            ->put('/users/'.$newUserId, [
                'full_name' => 'Updated Operator',
                'username' => 'updated.operator',
                'password' => 'updated-password',
                'role_id' => $employeeRoleId,
            ])
            ->assertRedirect('/users');

        $this->assertDatabaseHas('users', [
            'id' => $newUserId,
            'full_name' => 'Updated Operator',
            'username' => 'updated.operator',
        ]);
        $this->assertTrue(Hash::check('updated-password', (string) DB::table('users')->where('id', $newUserId)->value('password_hash')));

        $this->actingAs($admin)
            ->delete('/users/'.$newUserId)
            ->assertRedirect('/users');

        $this->assertDatabaseMissing('users', ['id' => $newUserId]);
    }

    public function test_admins_cannot_delete_themselves_or_remove_the_last_admin_role(): void
    {
        $admin = $this->createUser('admin', 'primary.admin');
        $otherAdmin = $this->createUser('admin', 'secondary.admin');
        $employeeRoleId = $this->roleId('employee');

        $this->actingAs($admin)
            ->delete('/users/'.$admin->id)
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role_id' => 1]);

        $this->actingAs($admin)
            ->delete('/users/'.$otherAdmin->id)
            ->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $otherAdmin->id]);

        $this->actingAs($admin)
            ->put('/users/'.$admin->id, [
                'full_name' => 'Primary Admin',
                'username' => 'primary.admin',
                'role_id' => $employeeRoleId,
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role_id' => 1,
        ]);
    }
}
