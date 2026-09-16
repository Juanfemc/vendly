<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StoreLanding extends Model
{
    private static ?bool $supportsTable = null;

    protected $fillable = [
        'store_id',
        'product_id',
        'enabled_by_admin',
        'activation_requested_at',
        'enabled_at',
        'enabled_by',
        'eyebrow',
        'headline',
        'subtitle',
        'description',
        'video_url',
        'video_title',
        'video_description',
        'bundle_enabled',
        'bundle_quantity',
        'bundle_price',
        'bundle_badge',
        'bundle_shipping_text',
        'features',
        'faqs',
        'review_images',
        'sections_order',
    ];

    protected $casts = [
        'enabled_by_admin' => 'boolean',
        'activation_requested_at' => 'datetime',
        'enabled_at' => 'datetime',
        'bundle_enabled' => 'boolean',
        'bundle_quantity' => 'integer',
        'bundle_price' => 'decimal:2',
        'features' => 'array',
        'faqs' => 'array',
        'review_images' => 'array',
        'sections_order' => 'array',
    ];

    public static function supportsTable(): bool
    {
        return self::$supportsTable ??= Schema::hasTable('store_landings');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function enabledBy()
    {
        return $this->belongsTo(User::class, 'enabled_by');
    }

    public function isPubliclyActive(): bool
    {
        return $this->enabled_by_admin === true && $this->product_id !== null;
    }

    public function featuresList(): array
    {
        return collect($this->features ?? [])->map(function ($feature) {
            if (is_array($feature)) {
                return [
                    'title' => trim((string) ($feature['title'] ?? '')),
                    'description' => trim((string) ($feature['description'] ?? '')),
                ];
            }

            return [
                'title' => trim((string) $feature),
                'description' => '',
            ];
        })
            ->filter(fn (array $feature) => $feature['title'] !== '' || $feature['description'] !== '')
            ->take(8)
            ->values()
            ->all();
    }

    public function faqList(): array
    {
        return collect($this->faqs ?? [])->map(fn ($faq) => [
            'question' => trim((string) ($faq['question'] ?? '')),
            'answer' => trim((string) ($faq['answer'] ?? '')),
        ])
            ->filter(fn (array $faq) => $faq['question'] !== '' && $faq['answer'] !== '')
            ->take(8)
            ->values()
            ->all();
    }

    public function reviewImages(): array
    {
        return collect($this->review_images ?? [])
            ->map(fn ($path) => trim((string) $path))
            ->filter()
            ->take(12)
            ->values()
            ->all();
    }
}
