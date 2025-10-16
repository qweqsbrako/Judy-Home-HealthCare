<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of roles
     */
    public function index(Request $request)
    {
        try {
            $query = Role::with(['permissions:id,name,display_name,category'])
                         ->withCount('users');

            // Filter by active status
            if ($request->has('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $roles = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                        'description' => $role->description,
                        'is_system_role' => $role->is_system_role,
                        'is_active' => $role->is_active,
                        'users_count' => $role->users_count,
                        'permissions_count' => $role->permissions->count(),
                        'permissions' => $role->permissions->groupBy('category'),
                        'created_at' => $role->created_at,
                        'updated_at' => $role->updated_at,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'is_system_role' => false, // Custom roles are never system roles
                'is_active' => true,
            ]);

            // Assign permissions if provided
            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            DB::commit();

            // Load the role with permissions for response
            $role->load(['permissions:id,name,display_name,category']);

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                    'is_system_role' => $role->is_system_role,
                    'is_active' => $role->is_active,
                    'permissions' => $role->permissions->groupBy('category'),
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified role
     */
    public function show(Role $role)
    {
        try {
            $role->load(['permissions:id,name,display_name,category,subcategory', 'users:id,first_name,last_name,email,is_active']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                    'is_system_role' => $role->is_system_role,
                    'is_active' => $role->is_active,
                    'permissions' => $role->permissions->groupBy('category'),
                    'users' => $role->users,
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role details',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, Role $role)
    {
        // Prevent editing system roles
        if ($role->is_system_role) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be modified'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|alpha_dash|unique:roles,name,' . $role->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role->update([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'is_active' => $request->get('is_active', $role->is_active),
            ]);

            // Update permissions if provided
            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            DB::commit();

            // Load the role with permissions for response
            $role->load(['permissions:id,name,display_name,category']);

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                    'is_system_role' => $role->is_system_role,
                    'is_active' => $role->is_active,
                    'permissions' => $role->permissions->groupBy('category'),
                    'updated_at' => $role->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy(Role $role)
    {
        // Prevent deleting system roles
        if ($role->is_system_role) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted'
            ], 403);
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role that has assigned users. Please reassign users first.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Remove all permissions from role
            $role->permissions()->detach();
            
            // Delete the role
            $role->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role->permissions()->sync($request->permissions);

            $role->load(['permissions:id,name,display_name,category']);

            return response()->json([
                'success' => true,
                'message' => 'Permissions updated successfully',
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->display_name,
                    'permissions' => $role->permissions->groupBy('category'),
                    'permissions_count' => $role->permissions->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permissions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get role permissions
     */
    public function getPermissions(Role $role)
    {
        try {
            $role->load(['permissions:id,name,display_name,category,subcategory']);

            return response()->json([
                'success' => true,
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->display_name,
                    'permissions' => $role->permissions->groupBy('category'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role permissions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Assign role to users
     */
    public function assignUsers(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $users = User::whereIn('id', $request->user_ids)->get();

            foreach ($users as $user) {
                $user->assignRole($role);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Users assigned to role successfully',
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->display_name,
                    'assigned_users_count' => count($request->user_ids),
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign users to role',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get users with this role
     */
    public function getUsers(Role $role)
    {
        try {
            $users = $role->users()
                          ->select('id', 'first_name', 'last_name', 'email', 'is_active', 'is_verified', 'last_login_at')
                          ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->display_name,
                    'users' => $users,
                    'users_count' => $users->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role users',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Duplicate an existing role
     */
    public function duplicate(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name|alpha_dash',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $newRole = Role::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'is_system_role' => false,
                'is_active' => true,
            ]);

            // Copy permissions from original role
            $permissionIds = $role->permissions()->pluck('permissions.id');
            $newRole->permissions()->sync($permissionIds);

            DB::commit();

            $newRole->load(['permissions:id,name,display_name,category']);

            return response()->json([
                'success' => true,
                'message' => 'Role duplicated successfully',
                'data' => [
                    'id' => $newRole->id,
                    'name' => $newRole->name,
                    'display_name' => $newRole->display_name,
                    'description' => $newRole->description,
                    'is_system_role' => $newRole->is_system_role,
                    'is_active' => $newRole->is_active,
                    'permissions' => $newRole->permissions->groupBy('category'),
                    'created_at' => $newRole->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate role',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}