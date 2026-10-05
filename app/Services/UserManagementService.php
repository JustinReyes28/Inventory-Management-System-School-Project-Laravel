<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\RoleName;
use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserManagementService
{
    private const MIN_PASSWORD_LENGTH = 6;

    private const MAX_PASSWORD_LENGTH = 255;

    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array{full_name: string, username: string, password: string, role_id: int|string}  $attributes
     */
    public function create(array $attributes, ?User $actor = null): User
    {
        $values = $this->normalizeAttributes($attributes, true);
        $password = $this->normalizePassword($attributes['password'] ?? null, true);
        $passwordHash = Hash::make($password);

        return DB::transaction(function () use ($values, $passwordHash, $actor): User {
            $role = $this->resolveRole($values['role_id']);
            $this->ensureUsernameIsAvailable($values['username']);

            $user = User::query()->create([
                'full_name' => $values['full_name'],
                'username' => $values['username'],
                // Hash explicitly so the legacy password_hash contract is
                // obvious at the service boundary.
                'password_hash' => $passwordHash,
            ]);

            // Roles are assigned through validated Spatie operations only.
            $user->assignRole($role);

            $this->activityLog->log(
                $actor,
                null,
                ActivityAction::CREATE,
                description: "Created user: {$user->full_name} ({$user->username}) as {$role->name}",
            );

            return $user->load('roles:id,name');
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes, ?User $actor = null): User
    {
        $values = $this->normalizeAttributes($attributes, false);
        $password = $this->normalizePassword($attributes['password'] ?? null, false);
        $passwordHash = $password === null ? null : Hash::make($password);

        return DB::transaction(function () use ($user, $values, $passwordHash, $actor): User {
            $locked = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $role = null;
            if (array_key_exists('role_id', $values)) {
                $role = $this->resolveRole($values['role_id']);
                $this->ensureAdminRemains($locked, $role);
            }

            if (array_key_exists('username', $values)) {
                $this->ensureUsernameIsAvailable($values['username'], $locked->getKey());
            }

            $locked->fill(Arr::except($values, ['role_id']));
            if ($passwordHash !== null) {
                $locked->password_hash = $passwordHash;
            }
            $locked->save();

            if ($role !== null) {
                $locked->syncRoles([$role]);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            $roleName = $locked->roles->first()?->name ?? 'no role';
            $this->activityLog->log(
                $actor,
                null,
                ActivityAction::UPDATE,
                description: "Updated user #{$locked->getKey()}: {$locked->full_name} ({$locked->username}), role: {$roleName}",
            );

            return $locked->load('roles:id,name');
        }, 3);
    }

    public function delete(User $user, ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $actor): void {
            $locked = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($actor !== null && (int) $actor->getKey() === (int) $locked->getKey()) {
                throw new DomainException('You cannot delete your own account.');
            }

            // Deleting a user also removes their role assignment; protect the
            // last admin exactly like a demotion would.
            $this->ensureAdminRemains($locked, null);

            $fullName = $locked->full_name;
            $username = $locked->username;
            $id = $locked->getKey();
            $locked->delete();

            $this->activityLog->log(
                $actor,
                null,
                ActivityAction::DELETE,
                description: "Deleted user #{$id}: {$fullName} ({$username})",
            );
        }, 3);
    }

    private function normalizePassword(mixed $value, bool $required): ?string
    {
        if (! $required && ($value === null || $value === '')) {
            return null;
        }

        if (! is_string($value)
            || trim($value) === ''
            || mb_strlen($value) < self::MIN_PASSWORD_LENGTH
            || mb_strlen($value) > self::MAX_PASSWORD_LENGTH) {
            throw new InvalidArgumentException('Password must contain 6 to 255 characters.');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{full_name?: string, username?: string, role_id?: int}
     */
    private function normalizeAttributes(array $attributes, bool $creating): array
    {
        $values = Arr::only($attributes, ['full_name', 'username', 'role_id']);

        if ($creating) {
            foreach (['full_name', 'username', 'role_id'] as $required) {
                if (! array_key_exists($required, $attributes)) {
                    throw new InvalidArgumentException("User [{$required}] is required.");
                }
            }

        }

        if (array_key_exists('full_name', $values)) {
            $values['full_name'] = trim((string) $values['full_name']);
            if ($values['full_name'] === '' || mb_strlen($values['full_name']) > 100) {
                throw new InvalidArgumentException('Full name must contain 1 to 100 characters.');
            }
        }

        if (array_key_exists('username', $values)) {
            $values['username'] = trim((string) $values['username']);
            if ($values['username'] === '' || mb_strlen($values['username']) > 50) {
                throw new InvalidArgumentException('Username must contain 1 to 50 characters.');
            }
        }

        if (array_key_exists('role_id', $values)) {
            $roleId = filter_var($values['role_id'], FILTER_VALIDATE_INT);
            if ($roleId === false) {
                throw new InvalidArgumentException('A valid role is required.');
            }
            $values['role_id'] = $roleId;
        }

        return $values;
    }

    private function resolveRole(int $roleId): Role
    {
        $role = Role::query()->whereKey($roleId)->first();

        if ($role === null) {
            throw new InvalidArgumentException("Role [{$roleId}] does not exist.");
        }

        return $role;
    }

    private function ensureUsernameIsAvailable(string $username, ?int $exceptId = null): void
    {
        $query = User::query()
            ->whereRaw('LOWER(username) = ?', [mb_strtolower($username)]);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            throw new DomainException("Username [{$username}] is already taken.");
        }
    }

    /**
     * Keep at least one Admin account: blocks both demoting and deleting the
     * last administrator.
     */
    private function ensureAdminRemains(User $user, ?Role $newRole): void
    {
        $isCurrentlyAdmin = $user->hasRole(RoleName::ADMIN->value);
        $staysAdmin = $newRole?->name === RoleName::ADMIN->value;

        if (! $isCurrentlyAdmin || $staysAdmin) {
            return;
        }

        $adminCount = User::query()
            ->role(RoleName::ADMIN->value)
            ->lockForUpdate()
            ->count();

        if ($adminCount <= 1) {
            throw new DomainException('The last admin account cannot be removed or demoted.');
        }
    }
}
