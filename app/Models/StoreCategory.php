<?php

namespace App\Models;

use App\Models\Concerns\HasAdminRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreCategory extends Model
{
    use HasAdminRouteKey;

    protected $fillable = [
        'store_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'sort_order',
        'admin_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (StoreCategory $category) {
            if (! $category->slug && $category->name && $category->store_id) {
                $category->slug = self::uniqueSlugFor((int) $category->store_id, $category->name, $category->id);
            }
        });
    }

    public static function uniqueSlugFor(int $storeId, string $name, ?int $ignoreCategoryId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'categoria';
        $slug = $baseSlug;
        $counter = 2;

        while (
            self::where('store_id', $storeId)
                ->where('slug', $slug)
                ->when($ignoreCategoryId, fn ($query) => $query->whereKeyNot($ignoreCategoryId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function scopeOrderedForDisplay($query)
    {
        return $query
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN sort_order = 0 THEN 60 ELSE sort_order END')
            ->orderBy('name');
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderedForDisplay();
    }

    public function activeChildren()
    {
        return $this->children()->where('is_active', true);
    }

    public function displayName(): string
    {
        return $this->parent ? $this->parent->name . ' / ' . $this->name : $this->name;
    }
}
