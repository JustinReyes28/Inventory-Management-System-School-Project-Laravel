<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

/**
 * Fortify authentication pipeline: registration, login/logout, throttling,
 * and password recovery (including invalid and expired reset tokens).
 */
class FortifyAuthTest extends InventoryTestCase
{
    public function test_registration_page_and_store_create_a_user_role_account(): void
    {
        $this->get('/register')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Register'));

        $response = $this->post('/register', [
            'full_name' => 'New Registrar',
            'username' => 'new.registrar',
            'email' => 'registrar@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            // Privilege escalation attempt: all of these must be ignored.
            'role_id' => 1,
            'role' => RoleName::ADMIN->value,
            'roles' => [RoleName::ADMIN->value],
            'permissions' => ['view users', 'delete users'],
            'is_admin' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::query()->where('username', 'new.registrar')->firstOrFail();
        $this->assertSame('registrar@example.test', $user->email);
        $this->assertUserHasRole($user, 'user');
        $this->assertFalse($user->hasRole(RoleName::ADMIN->value));
        $this->assertFalse($user->can('view users'));
        $this->assertFalse($user->can('create items'));
    }

    public function test_registration_validates_input_and_rejects_duplicates(): void
    {
        $this->createUser('employee', 'taken.username');
        $existing = User::query()->where('username', 'taken.username')->firstOrFail();
        $existing->forceFill(['email' => 'taken@example.test'])->save();

        $this->from('/register')
            ->post('/register', [
                'full_name' => '',
                'username' => 'taken.username',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['full_name', 'username', 'email', 'password']);

        $this->from('/register')
            ->post('/register', [
                'full_name' => 'Duplicate Email',
                'username' => 'duplicate.email',
                'email' => 'taken@example.test',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['email']);
    }

    public function test_login_honors_the_intended_redirect(): void
    {
        $user = $this->createUser('employee', 'intended.user');

        $this->get('/items')->assertRedirect('/login');

        $this->post('/login', [
            'username' => 'intended.user',
            'password' => 'password',
        ])->assertRedirect('/items');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_attempts_are_throttled(): void
    {
        $this->createUser('employee', 'throttled.user');

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'username' => 'throttled.user',
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'username' => 'throttled.user',
            'password' => 'password',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = $this->createUser('employee', 'logout.session');

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        // The invalidated session cannot reach protected pages anymore.
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_password_recovery_email_resets_the_password(): void
    {
        Notification::fake();

        $user = $this->createUser('employee', 'recovery.user', 'OldPass123!');
        $user->forceFill(['email' => 'recovery@example.test'])->save();

        $this->get('/forgot-password')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/ForgotPassword'));

        $this->post('/forgot-password', ['email' => 'recovery@example.test'])
            ->assertRedirect();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });
        $this->assertNotNull($token);

        $this->get('/reset-password/'.$token)
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/ResetPassword'));

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'recovery@example.test',
            'password' => 'BrandNewPass1!',
            'password_confirmation' => 'BrandNewPass1!',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('BrandNewPass1!', (string) $user->fresh()->password_hash));

        $this->post('/logout');
        // A fresh guest visit stores the intended URL…
        $this->get('/dashboard')->assertRedirect('/login');
        // …so signing in lands back on the dashboard.
        $this->post('/login', [
            'username' => 'recovery.user',
            'password' => 'BrandNewPass1!',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_email_and_invalid_reset_tokens_are_rejected(): void
    {
        $user = $this->createUser('employee', 'token.user', 'OldPass123!');
        $user->forceFill(['email' => 'token@example.test'])->save();

        $this->post('/forgot-password', ['email' => 'missing@example.test'])
            ->assertRedirect()
            ->assertSessionHasErrors(['email']);

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'token@example.test',
            'password' => 'BrandNewPass1!',
            'password_confirmation' => 'BrandNewPass1!',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('OldPass123!', (string) $user->fresh()->password_hash));
    }

    public function test_expired_reset_tokens_are_rejected(): void
    {
        Notification::fake();

        $user = $this->createUser('employee', 'expired.user', 'OldPass123!');
        $user->forceFill(['email' => 'expired@example.test'])->save();

        $this->post('/forgot-password', ['email' => 'expired@example.test']);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        // The token exists but its window has passed.
        DB::table('password_reset_tokens')
            ->where('email', 'expired@example.test')
            ->update(['created_at' => now()->subHours(3)]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'expired@example.test',
            'password' => 'BrandNewPass1!',
            'password_confirmation' => 'BrandNewPass1!',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('OldPass123!', (string) $user->fresh()->password_hash));
        $this->assertGuest();
    }
}
