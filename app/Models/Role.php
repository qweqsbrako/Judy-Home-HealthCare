<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system_role',
        'is_active'
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get users with this role
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get permissions for this role
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')->withTimestamps();
    }

    /**
     * Check if role has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check if role has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * Assign permission to role
     */
    public function givePermission(string $permission): bool
    {
        $permissionModel = Permission::where('name', $permission)->first();
        
        if (!$permissionModel) {
            return false;
        }

        if (!$this->hasPermission($permission)) {
            $this->permissions()->attach($permissionModel->id);
        }

        return true;
    }

    /**
     * Remove permission from role
     */
    public function revokePermission(string $permission): bool
    {
        $permissionModel = Permission::where('name', $permission)->first();
        
        if (!$permissionModel) {
            return false;
        }

        $this->permissions()->detach($permissionModel->id);
        return true;
    }

    /**
     * Sync permissions for this role
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Get permissions grouped by category
     */
    public function getGroupedPermissions(): array
    {
        return $this->permissions()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->toArray();
    }

    /**
     * Scope for active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for non-system roles (can be deleted)
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system_role', false);
    }
}