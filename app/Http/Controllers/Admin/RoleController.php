<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Services\Admin\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Role::class);

        $roles = Role::withCount('users')->with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get()->groupBy(fn (Permission $p) => explode('.', $p->name)[0]);

        return view('admin.roles.index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'rolesProtegees' => RoleService::ROLES_PROTEGES,
        ]);
    }

    public function show(Role $role): JsonResponse
    {
        Gate::authorize('view', $role);

        return response()->json($role->load('permissions'));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->creer($request->validated());

        return response()->json([
            'message' => "Rôle « {$role->name} » créé.",
            'role' => $role->load('permissions'),
        ], 201);
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): JsonResponse
    {
        try {
            $role = $this->roleService->mettreAJourPermissions($role, $request->validated('permissions', []));
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'message' => "Permissions du rôle « {$role->name} » mises à jour.",
            'role' => $role->load('permissions'),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        Gate::authorize('delete', $role);

        try {
            $this->roleService->supprimer($role);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['message' => "Rôle supprimé."]);
    }
}
