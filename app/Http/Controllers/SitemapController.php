<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCategory;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    private const MAX_URLS = 50000;
    private const STORE_CHUNK_SIZE = 100;
    private const RELATED_CHUNK_SIZE = 500;

    public function __invoke(): Response
    {
        $urls = collect();

        $this->addUrl($urls, route('home', absolute: true), now(), 'daily', '1.0');
        $this->addUrl($urls, route('trial-signup.create', absolute: true), now(), 'weekly', '0.8');

        Store::publiclyAvailable()
            ->orderBy('updated_at', 'desc')
            ->chunk(self::STORE_CHUNK_SIZE, function ($stores) use ($urls) {
                $storesById = $stores->keyBy('id');
                $storeIds = $storesById->keys();
                $storesWithOffers = $this->storesWithOfferProducts($storeIds);

                foreach ($stores as $store) {
                    $storeUpdatedAt = $store->updated_at ?? now();

                    if (! $this->addUrl($urls, route('store.show', $store->slug, absolute: true), $storeUpdatedAt, 'daily', '0.9')) {
                        return false;
                    }

                    if (! $this->addUrl($urls, route('store.products.index', $store->slug, absolute: true), $storeUpdatedAt, 'daily', '0.7')) {
                        return false;
                    }

                    if ($store->hasAboutContent()) {
                        if (! $this->addUrl($urls, route('store.about', $store->slug, absolute: true), $storeUpdatedAt, 'monthly', '0.5')) {
                            return false;
                        }
                    }

                    if ($store->allowsOfferBadges() && $storesWithOffers->has($store->id)) {
                        if (! $this->addUrl($urls, route('store.offers.index', $store->slug, absolute: true), $storeUpdatedAt, 'daily', '0.7')) {
                            return false;
                        }
                    }
                }

                if (! $this->addCategoryUrls($urls, $storesById)) {
                    return false;
                }

                if (! $this->addProductUrls($urls, $storesById)) {
                    return false;
                }
            });

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function storesWithOfferProducts(Collection $storeIds): Collection
    {
        if (! Product::supportsOfferColumn()) {
            return collect();
        }

        return Product::query()
            ->whereIn('store_id', $storeIds)
            ->where('has_offer', true)
            ->distinct()
            ->pluck('store_id')
            ->flip();
    }

    private function addCategoryUrls(Collection $urls, Collection $storesById): bool
    {
        $storeIds = $storesById
            ->filter(fn (Store $store) => $store->allowsCategories())
            ->keys();

        if ($storeIds->isEmpty()) {
            return true;
        }

        return StoreCategory::query()
            ->whereIn('store_id', $storeIds)
            ->where('is_active', true)
            ->orderedForDisplay()
            ->select(['id', 'store_id', 'slug', 'updated_at'])
            ->chunk(self::RELATED_CHUNK_SIZE, function ($categories) use ($urls, $storesById) {
                foreach ($categories as $category) {
                    $store = $storesById->get($category->store_id);

                    if (! $store instanceof Store) {
                        continue;
                    }

                    if (! $this->addUrl(
                        $urls,
                        route('store.category.show', ['slug' => $store->slug, 'category' => $category->slug], absolute: true),
                        $category->updated_at ?? $store->updated_at,
                        'weekly',
                        '0.6'
                    )) {
                        return false;
                    }
                }
            }) !== false;
    }

    private function addProductUrls(Collection $urls, Collection $storesById): bool
    {
        return Product::query()
            ->whereIn('store_id', $storesById->keys())
            ->select(['id', 'store_id', 'slug', 'updated_at'])
            ->latest()
            ->chunk(self::RELATED_CHUNK_SIZE, function ($products) use ($urls, $storesById) {
                foreach ($products as $product) {
                    $store = $storesById->get($product->store_id);

                    if (! $store instanceof Store) {
                        continue;
                    }

                    $productKey = $product->slug ?: (string) $product->id;

                    if (! $this->addUrl(
                        $urls,
                        route('store.product.show', ['slug' => $store->slug, 'product' => $productKey], absolute: true),
                        $product->updated_at ?? $store->updated_at,
                        'weekly',
                        '0.7'
                    )) {
                        return false;
                    }
                }
            }) !== false;
    }

    private function addUrl(Collection $urls, string $loc, mixed $lastmod, string $changefreq, string $priority): bool
    {
        if ($urls->count() >= self::MAX_URLS) {
            return false;
        }

        $urls->push($this->url($loc, $lastmod, $changefreq, $priority));

        return $urls->count() < self::MAX_URLS;
    }

    private function url(string $loc, mixed $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'lastmod' => optional($lastmod)->toAtomString() ?: now()->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
