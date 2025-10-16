<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions grouped by category
     */
    public function index(Request $request)
    {
        try {
            $query = Permission::active();

            // Filter by category if provided
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%")
                      ->orWhere('subcategory', 'like', "%{$search}%");
                });
            }

            $permissions = $query->orderBy('category')
                                ->orderBy('subcategory')
                                ->orderBy('sort_order')
                                ->get();

            // Group permissions by category and subcategory
            $groupedPermissions = $permissions->groupBy('category')->map(function ($categoryPermissions, $category) {
                $generalPermissions = $categoryPermissions->filter(function ($permission) {
                    return is_null($permission->subcategory);
                });

                $subcategoryPermissions = $categoryPermissions->filter(function ($permission) {
                    return !is_null($permission->subcategory);
                })->groupBy('subcategory');

                return [
                    'category' => $category,
                    'display_category' => ucwords(str_replace('_', ' ', $category)),
                    'general_permissions' => $generalPermissions->values(),
                    'subcategories' => $subcategoryPermissions->map(function ($subPermissions, $subcategory) {
                        return [
                            'subcategory' => $subcategory,
                            'display_subcategory' => ucwords(str_replace('_', ' ', $subcategory)),
                            'permissions' => $subPermissions->values(),
                        ];
                    })->values(),
                    'total_permissions' => $categoryPermissions->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $groupedPermissions->values(),
                'summary' => [
                    'total_permissions' => $permissions->count(),
                    'total_categories' => $groupedPermissions->count(),
                    'categories' => $groupedPermissions->keys()->toArray(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permissions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get all permissions as a flat list (useful for role assignment)
     */
    public function flat(Request $request)
    {
        try {
            $query = Permission::active();

            // Filter by category if provided
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            $permissions = $query->orderBy('category')
                                ->orderBy('subcategory')
                                ->orderBy('sort_order')
                                ->get(['id', 'name', 'display_name', 'description', 'category', 'subcategory']);

            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permissions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get permissions for a specific category
     */
    public function getByCategory(string $category)
    {
        try {
            $permissions = Permission::getByCategory($category);

            $generalPermissions = $permissions->filter(function ($permission) {
                return is_null($permission->subcategory);
            });

            $subcategoryPermissions = $permissions->filter(function ($permission) {
                return !is_null($permission->subcategory);
            })->groupBy('subcategory');

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => $category,
                    'display_category' => ucwords(str_replace('_', ' ', $category)),
                    'general_permissions' => $generalPermissions->values(),
                    'subcategories' => $subcategoryPermissions->map(function ($subPermissions, $subcategory) {
                        return [
                            'subcategory' => $subcategory,
                            'display_subcategory' => ucwords(str_replace('_', ' ', $subcategory)),
                            'permissions' => $subPermissions->values(),
                        ];
                    })->values(),
                    'total_permissions' => $permissions->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to fetch permissions for category: {$category}",
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get all categories
     */
    public function getCategories()
    {
        try {
            $categories = Permission::getCategories();

            $categoriesWithCounts = collect($categories)->map(function ($category) {
                $count = Permission::where('category', $category)->active()->count();
                return [
                    'category' => $category,
                    'display_category' => ucwords(str_replace('_', ' ', $category)),
                    'permissions_count' => $count,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $categoriesWithCounts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get subcategories for a specific category
     */
    public function getSubcategories(string $category)
    {
        try {
            $subcategories = Permission::getSubcategories($category);

            $subcategoriesWithCounts = collect($subcategories)->map(function ($subcategory) use ($category) {
                $count = Permission::where('category', $category)
                                 ->where('subcategory', $subcategory)
                                 ->active()
                                 ->count();
                return [
                    'subcategory' => $subcategory,
                    'display_subcategory' => ucwords(str_replace('_', ' ', $subcategory)),
                    'permissions_count' => $count,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => $category,
                    'subcategories' => $subcategoriesWithCounts
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to fetch subcategories for category: {$category}",
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified permission
     */
    public function show(Permission $permission)
    {
        try {
            $permission->load(['roles:id,name,display_name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'description' => $permission->description,
                    'category' => $permission->category,
                    'subcategory' => $permission->subcategory,
                    'sort_order' => $permission->sort_order,
                    'is_active' => $permission->is_active,
                    'roles' => $permission->roles,
                    'roles_count' => $permission->roles->count(),
                    'created_at' => $permission->created_at,
                    'updated_at' => $permission->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permission details',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get roles that have a specific permission
     */
    public function getRoles(Permission $permission)
    {
        try {
            $roles = $permission->roles()
                               ->withCount('users')
                               ->select('id', 'name', 'display_name', 'is_active')
                               ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->display_name,
                    'roles' => $roles,
                    'roles_count' => $roles->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permission roles',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Search permissions
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        try {
            $query = $request->query;

            $permissions = Permission::active()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('display_name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhere('category', 'like', "%{$query}%")
                      ->orWhere('subcategory', 'like', "%{$query}%");
                })
                ->orderBy('category')
                ->orderBy('subcategory')
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $permissions,
                'query' => $query,
                'results_count' => $permissions->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search permissions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}