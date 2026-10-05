<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeederTest extends InventoryTestCase
{
    public function test_reseeding_refuses_to_promote_a_registered_username_collision(): void
    {
        $this->createUser('admin', 'renamed.administrator');
        $username = trim((string) env('ADMIN_USERNAME', 'admin'));
        $this->post('/register', [
            'full_name' => 'Registered Viewer',
            'username' => $username,
            'email' => 'registered.viewer@example.test',
            'password' => 'ViewerPassword1!',
            'password_confirmation' => 'ViewerPassword1!',
        ])->assertRedirect('/dashboard');

        $registered = User::where('username', $username)->firstOrFail();
        $before = $registered->getAttributes();

        try {
            $this->seed(DatabaseSeeder::class);
            $this->fail('Seeding must refuse to reuse a non-admin account.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('belongs to a non-admin account', $error->getMessage());
        }

        $this->assertSame($before, $registered->fresh()->getAttributes());
        $this->assertSame([RoleName::USER->value], $registered->fresh()->getRoleNames()->all());
        $this->assertTrue(Hash::check('ViewerPassword1!', $registered->fresh()->password_hash));
    }

    public function test_reseeding_preserves_an_existing_administrators_profile_and_password(): void
    {
        $username = trim((string) env('ADMIN_USERNAME', 'admin'));
        $admin = $this->createUser('admin', $username, 'ExistingAdminPass1!', 'Existing Administrator');
        $admin->forceFill(['email' => 'existing.admin@example.test'])->save();
        $before = $admin->getAttributes();

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($before, $admin->fresh()->getAttributes());
        $this->assertSame([RoleName::ADMIN->value], $admin->fresh()->getRoleNames()->all());
    }

    public function test_a_fresh_install_provisions_one_administrator_and_is_repeatable(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('username', trim((string) env('ADMIN_USERNAME', 'admin')))->firstOrFail();
        $before = $admin->getAttributes();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::role(RoleName::ADMIN->value)->count());
        $this->assertSame($before, $admin->fresh()->getAttributes());
        $this->assertSame([RoleName::ADMIN->value], $admin->fresh()->getRoleNames()->all());
    }
}
