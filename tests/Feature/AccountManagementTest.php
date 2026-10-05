<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Inertia\Testing\AssertableInertia;

/**
 * Account maintenance through Fortify: profile updates (the email-update path
 * for legacy accounts) and password changes.
 */
class AccountManagementTest extends InventoryTestCase
{
    public function test_profile_errors_reach_inertia_in_the_named_form_bag(): void
    {
        $user = $this->createUser('employee', 'profile.errors');
        $errors = (new ViewErrorBag)->put('updateProfileInformation', new MessageBag([
            'full_name' => ['The full name field is required.'],
        ]));

        $this->actingAs($user)->withSession(['errors' => $errors]);
        $session = $this->app['session']->driver();
        $session->save();

        $this->withCookie($session->getName(), $session->getId())
            ->get('/account', ['X-Inertia-Error-Bag' => 'updateProfileInformation'])
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('errors.updateProfileInformation.full_name', 'The full name field is required.')
                ->missing('errors.full_name')
            );
    }

    public function test_password_errors_are_preserved_on_partial_inertia_responses(): void
    {
        $user = $this->createUser('employee', 'password.errors');
        $errors = (new ViewErrorBag)->put('updatePassword', new MessageBag([
            'current_password' => ['The provided password does not match your current password.'],
        ]));
        $version = app(HandleInertiaRequests::class)->version(Request::create('/account'));

        $this->actingAs($user)->withSession(['errors' => $errors]);
        $session = $this->app['session']->driver();
        $session->save();

        $this->withCookie($session->getName(), $session->getId())->get('/account', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version ?? '',
            'X-Inertia-Error-Bag' => 'updatePassword',
            'X-Inertia-Partial-Component' => 'Account',
            'X-Inertia-Partial-Data' => 'auth',
        ])->assertSuccessful()
            ->assertJsonPath('props.errors.updatePassword.current_password', 'The provided password does not match your current password.')
            ->assertJsonMissingPath('props.errors.current_password');
    }

    public function test_the_account_page_updates_profile_information_and_email(): void
    {
        $user = $this->createUser('employee', 'account.user');

        $this->actingAs($user)
            ->get('/account')
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Account'));

        $this->actingAs($user)
            ->put('/user/profile-information', [
                'full_name' => 'Updated Name',
                'username' => 'account.user',
                'email' => 'account@example.test',
            ])
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('Updated Name', $user->full_name);
        $this->assertSame('account@example.test', $user->email);
    }

    public function test_profile_updates_reject_duplicate_and_invalid_values(): void
    {
        $other = $this->createUser('employee', 'other.account');
        $other->forceFill(['email' => 'taken@example.test'])->save();
        $user = $this->createUser('user', 'my.account');

        $this->actingAs($user)
            ->put('/user/profile-information', [
                'full_name' => '',
                'username' => 'other.account',
                'email' => 'taken@example.test',
            ])
            ->assertSessionHasErrorsIn('updateProfileInformation', ['full_name', 'username', 'email']);

        $this->actingAs($user)
            ->put('/user/profile-information', [
                'full_name' => 'Still Viewer',
                'username' => 'my.account',
                'email' => 'viewer-updated@example.test',
            ])
            ->assertSessionHas('status');

        // A profile update is not a privilege escalation path.
        $user->refresh();
        $this->assertSame([RoleName::USER->value], $user->getRoleNames()->all());
    }

    public function test_password_updates_require_the_current_password(): void
    {
        $user = $this->createUser('employee', 'pw.user', 'OldPass123!');

        $this->actingAs($user)
            ->put('/user/password', [
                'current_password' => 'WrongPass1!',
                'password' => 'NewPass123!',
                'password_confirmation' => 'NewPass123!',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['current_password']);

        $this->assertTrue(Hash::check('OldPass123!', (string) $user->fresh()->password_hash));

        $this->actingAs($user)
            ->put('/user/password', [
                'current_password' => 'OldPass123!',
                'password' => 'NewPass123!',
                'password_confirmation' => 'NewPass123!',
            ])
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewPass123!', (string) $user->fresh()->password_hash));
    }

    public function test_legacy_accounts_without_email_can_sign_in_and_add_one(): void
    {
        // Existing accounts predate the email column entirely.
        $user = $this->createUser('employee', 'legacy.user', 'LegacyPass1!');
        $this->assertNull($user->email);

        $this->post('/login', [
            'username' => 'legacy.user',
            'password' => 'LegacyPass1!',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // The account page is the email-update path that enables recovery.
        $this->put('/user/profile-information', [
            'full_name' => $user->full_name,
            'username' => 'legacy.user',
            'email' => 'legacy@example.test',
        ])->assertSessionHas('status');

        $this->assertSame('legacy@example.test', $user->fresh()->email);
    }
}
