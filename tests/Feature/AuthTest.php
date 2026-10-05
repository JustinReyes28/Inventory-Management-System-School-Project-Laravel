<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;

class AuthTest extends InventoryTestCase
{
    public function test_guests_are_redirected_to_the_username_login_page(): void
    {
        foreach (['/', '/dashboard', '/items', '/categories', '/batches', '/activity-logs', '/reports', '/notifications', '/users'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_login_page_is_an_inertia_page(): void
    {
        $this->get('/login')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Login'));
    }

    public function test_a_user_can_log_in_with_a_username_and_password(): void
    {
        $user = $this->createUser('employee', 'warehouse.operator', 'correct-password');

        $response = $this->post('/login', [
            'username' => 'warehouse.operator',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_authenticate_the_user(): void
    {
        $this->createUser('employee', 'warehouse.operator');

        $response = $this->post('/login', [
            'username' => 'warehouse.operator',
            'password' => 'not-the-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_a_logged_in_user_can_log_out(): void
    {
        $user = $this->createUser('employee', 'logout.operator');

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_authenticated_dashboard_is_an_inertia_page(): void
    {
        $user = $this->createUser('employee', 'dashboard.operator');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Dashboard'));
    }
}
