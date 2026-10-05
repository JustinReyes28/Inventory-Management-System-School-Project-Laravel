<?php

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/ActivityLog.php';

class UserController
{
    private $userModel;
    private $activityLog;

    public function __construct()
    {
        $this->userModel = new User();
        $this->activityLog = new ActivityLog();
    }

    public function listUsers(): array
    {
        return [
            'success' => true,
            'data'    => $this->userModel->getAll()
        ];
    }

    public function getUser(int $id): array
    {
        $user = $this->userModel->getUserById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        return ['success' => true, 'data' => $user];
    }

    public function getRoles(): array
    {
        $roles = $this->userModel->getRoles();
        return [
            'success' => true,
            'data'    => $roles
        ];
    }

    public function createUser(array $data, ?int $actorId): array
    {
        $fullName = trim($data['full_name'] ?? '');
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $roleId   = (int) ($data['role_id'] ?? 0);

        if (empty($fullName)) {
            return ['success' => false, 'message' => 'Full name is required.'];
        }
        if (empty($username)) {
            return ['success' => false, 'message' => 'Username is required.'];
        }
        if (empty($password)) {
            return ['success' => false, 'message' => 'Password is required.'];
        }
        if ($roleId < 1) {
            return ['success' => false, 'message' => 'Role is required.'];
        }

        if ($this->userModel->existsUsername($username)) {
            return ['success' => false, 'message' => "Username '{$username}' is already taken."];
        }

        $userId = $this->userModel->createUser($fullName, $username, $password, $roleId);
        if ($userId) {
            $roleLabel = $roleId === 1 ? 'Admin' : 'Employee';
            $this->activityLog->log($actorId, null, 'create', null, null, "Created user: {$fullName} ({$username}) as {$roleLabel}");
            return [
                'success' => true,
                'message' => 'User created successfully.',
                'data'    => ['id' => $userId]
            ];
        }

        return ['success' => false, 'message' => 'Failed to create user.'];
    }

    public function updateUser(int $id, array $data, ?int $actorId): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID.'];
        }

        $existing = $this->userModel->getUserById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $fullName = trim($data['full_name'] ?? '');
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $roleId   = (int) ($data['role_id'] ?? 0);

        if (empty($fullName)) {
            return ['success' => false, 'message' => 'Full name is required.'];
        }
        if (empty($username)) {
            return ['success' => false, 'message' => 'Username is required.'];
        }
        if ($roleId < 1) {
            return ['success' => false, 'message' => 'Role is required.'];
        }

        if ($this->userModel->existsUsername($username, $id)) {
            return ['success' => false, 'message' => "Another user already has username '{$username}'."];
        }

        $passwordForUpdate = !empty($password) ? $password : null;
        $updated = $this->userModel->updateUser($id, $fullName, $username, $roleId, $passwordForUpdate);
        if ($updated) {
            $roleLabel = $roleId === 1 ? 'Admin' : 'Employee';
            $this->activityLog->log($actorId, null, 'update', null, null, "Updated user #{$id}: {$fullName} ({$username}), role: {$roleLabel}");
            return [
                'success' => true,
                'message' => 'User updated successfully.'
            ];
        }

        return ['success' => false, 'message' => 'Failed to update user.'];
    }

    public function deleteUser(int $id, ?int $actorId): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID.'];
        }

        if ($actorId !== null && $id === $actorId) {
            return ['success' => false, 'message' => 'You cannot delete your own account.'];
        }

        $existing = $this->userModel->getUserById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if ((int) $existing['role_id'] === 1) {
            $adminCount = $this->userModel->countAdmins();
            if ($adminCount <= 1) {
                return ['success' => false, 'message' => 'Cannot delete the last admin account.'];
            }
        }

        $deleted = $this->userModel->deleteUser($id);
        if ($deleted) {
            $this->activityLog->log($actorId, null, 'delete', null, null, "Deleted user #{$id}: {$existing['full_name']} ({$existing['username']})");
            return [
                'success' => true,
                'message' => 'User deleted successfully.'
            ];
        }

        return ['success' => false, 'message' => 'Failed to delete user.'];
    }
}
