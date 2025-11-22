<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     * Requires: view roles permission
     */
    public function index(): JsonResponse
    {
        if (! auth('sanctum')->user()->can('view roles')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $roles = Role::with('permissions')->get();

        return $this->successResponse([
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                    'created_at' => $role->created_at->toIso8601String(),
                ];
            }),
        ], 'Roles retrieved successfully');
    }

    /**
     * Store a newly created role.
     * Requires: create roles permission
     */
    public function store(Request $request): JsonResponse
    {
        if (! auth('sanctum')->user()->can('create roles')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'sanctum',
        ]);

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])
                ->where('guard_name', 'sanctum')
                ->get();
            $role->syncPermissions($permissions);
        }

        return $this->createdResponse([
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ], 'Role created successfully');
    }

    /**
     * Update the specified role.
     * Requires: edit roles permission
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        if (! auth('sanctum')->user()->can('edit roles')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:roles,name,'.$role->id],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        if (isset($validated['name'])) {
            $role->update(['name' => $validated['name']]);
        }

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])
                ->where('guard_name', 'sanctum')
                ->get();
            $role->syncPermissions($permissions);
        }

        return $this->successResponse([
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->fresh()->permissions->pluck('name'),
            ],
        ], 'Role updated successfully');
    }

    /**
     * Remove the specified role.
     * Requires: delete roles permission
     */
    public function destroy(Role $role): JsonResponse
    {
        if (! auth('sanctum')->user()->can('delete roles')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $role->delete();

        return $this->successResponse(null, 'Role deleted successfully');
    }
}

