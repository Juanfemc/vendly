<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PriceList extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'access_code',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (PriceList $priceList) {
            if (! $priceList->slug && $priceList->name && $priceList->store_id) {
                $priceList->slug = self::uniqueSlugFor((int) $priceList->store_id, $priceList->name, $priceList->id);
            }

            if ($priceList->access_code !== null) {
                $priceList->access_code = trim((string) $priceList->access_code) ?: null;
            }
        });
    }

    public static function uniqueSlugFor(int $storeId, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'lista';
        $slug = $baseSlug;
        $counter = 2;

        while (
            self::where('store_id', $storeId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(PriceListProductPrice::class);
    }

    public function scopeAvailable($query)
    {
        $today = now()->toDateString();

        return $query
            ->where('is_active', true)
            ->where(function ($query) use ($today) {
                $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today);
            });
    }
}
