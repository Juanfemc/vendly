<?php

namespace App\Services;

use App\Models\PriceList;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class PriceListService
{
    private array $productPriceCache = [];

    public function current(Store $store, ?Request $request = null): ?PriceList
    {
        if (! $store->allowsPriceLists() || ! Store::supportsPriceListsTables()) {
            session()->forget($this->sessionKey($store));

            return null;
        }

        $request ??= request();
        $key = trim((string) ($request->query('lista') ?: $request->input('lista')));

        if ($key !== '') {
            $priceList = $this->findAvailableByKey($store, $key);

            if ($priceList) {
                session()->put($this->sessionKey($store), $priceList->id);

                return $priceList;
            }

            session()->forget($this->sessionKey($store));

            return null;
        }

        $id = session()->get($this->sessionKey($store));

        if (! $id) {
            return null;
        }

        return $store->priceLists()
            ->available()
            ->whereKey($id)
            ->first();
    }

    public function priceFor(Product $product, ?PriceList $priceList): float
    {
        if (! $priceList || (int) $priceList->store_id !== (int) $product->store_id) {
            return (float) $product->price;
        }

        $prices = $this->pricesForList($priceList);
        $price = $prices[$product->id] ?? null;

        return $price === null ? (float) $product->price : (float) $price;
    }

    public function cartOptionsFor(Product $product, array $options, ?PriceList $priceList): array
    {
        if (! $priceList) {
            return $options;
        }

        return array_merge($options, [
            'price' => $this->priceFor($product, $priceList),
            'price_list_id' => $priceList->id,
            'price_list_name' => $priceList->name,
        ]);
    }

    private function findAvailableByKey(Store $store, string $key): ?PriceList
    {
        return $store->priceLists()
            ->available()
            ->where(function ($query) use ($key) {
                $query->where('slug', $key)->orWhere('access_code', $key);
            })
            ->first();
    }

    private function pricesForList(PriceList $priceList): array
    {
        $key = (int) $priceList->id;

        if (! array_key_exists($key, $this->productPriceCache)) {
            $this->productPriceCache[$key] = $priceList->productPrices()
                ->pluck('price', 'product_id')
                ->all();
        }

        return $this->productPriceCache[$key];
    }

    private function sessionKey(Store $store): string
    {
        return 'store_' . $store->id . '_price_list';
    }
}
