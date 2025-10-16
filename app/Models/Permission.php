<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'category',
        'subcategory',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get roles that have this permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')->withTimestamps();
    }

    /**
     * Get permissions grouped by category
     */
    public static function getGrouped(): array
    {
        return self::active()
            ->orderBy('category')
            ->orderBy('subcategory')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->map(function ($permissions) {
                return $permissions->groupBy('subcategory');
            })
            ->toArray();
    }

    /**
     * Get permissions for a specific category
     */
    public static function getByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('category', $category)
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Scope for active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all unique categories
     */
    public static function getCategories(): array
    {
        return self::select('category')
            ->distinct()
            ->active()
            ->orderBy('category')
            ->pluck('category')
            ->toArray();
    }

    /**
     * Get subcategories for a specific category
     */
    public static function getSubcategories(string $category): array
    {
        return self::where('category', $category)
            ->whereNotNull('subcategory')
            ->select('subcategory')
            ->distinct()
            ->active()
            ->orderBy('subcategory')
            ->pluck('subcategory')
            ->toArray();
    }
}