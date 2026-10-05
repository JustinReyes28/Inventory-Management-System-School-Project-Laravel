<?php

namespace App\Http\Controllers;

use App\Enums\RoleId;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $users) {}

    public function index(UserIndexRequest $request): Response
    {
        $this->authorize('manage-users');
        $filters = $request->validated();

        $users = User::query()
            ->with('role:id,role_name')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($query) use ($term): void {
                    $query->whereRaw('LOWER(full_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(username) LIKE ?', [$term]);
                });
            })
            ->when($filters['role_id'] ?? null, fn ($query, $roleId) => $query->where('role_id', $roleId))
            ->orderBy('full_name')
            ->get()
            ->map(fn (User $user) => (new UserResource($user))->resolve($request))
            ->values()
            ->all();

        $roles = Role::query()
            ->orderBy('id')
            ->get(['id', 'role_name'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->role_name,
                'role_name' => $role->role_name,
            ])
            ->all();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role_id' => $filters['role_id'] ?? null,
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        $this->authorize('manage-users');

        return to_route('users.index');
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->authorize('manage-users');

        return $this->mutate(
            fn () => $this->users->create($request->validated(), $request->user()),
            'User created successfully.',
            'users.index',
        );
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);
        $user->load('role:id,role_name');

        return (new UserResource($user))->response();
    }

    public function edit(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        return to_route('users.index');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $data = $request->validated();
        if (! $request->filled('password')) {
            unset($data['password']);
        }

        return $this->mutate(
            fn () => $this->users->update($user, $data, $request->user()),
            'User updated successfully.',
            'users.index',
        );
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isAdmin() && User::query()->where('role_id', RoleId::ADMIN->value)->count() <= 1) {
            return back()->with('error', 'The last admin account cannot be deleted.');
        }

        $this->authorize('delete', $user);

        return $this->mutate(
            fn () => $this->users->delete($user, $actor),
            'User deleted successfully.',
            'users.index',
        );
    }
}
