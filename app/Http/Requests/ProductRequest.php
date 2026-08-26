<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\Store;
use App\Support\ProductText;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowsWholesalePricing = $this->storeForProductRequest()?->allowsWholesalePricing() ?? false;
        $wholesaleEnabled = $allowsWholesalePricing && $this->boolean('has_wholesale_price');
        $wholesaleRules = $wholesaleEnabled
            ? ['nullable', 'required_if:has_wholesale_price,1', 'integer', 'min:2', 'max:999999']
            : ['nullable', 'integer', 'min:2', 'max:999999'];
        $wholesalePriceRules = $wholesaleEnabled
            ? ['nullable', 'required_if:has_wholesale_price,1', 'numeric', 'gt:0', 'lt:price']
            : ['nullable', 'numeric', 'gt:0', 'lt:price'];

        return [
            'store_id' => $this->user()?->isAdmin()
                ? ['required', 'integer', Rule::exists('stores', 'id')]
                : ['nullable'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
            'category' => ['nullable', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_sold_out' => ['nullable', 'boolean'],
            'has_offer' => ['nullable', 'boolean'],
            'offer_original_price' => ['nullable', 'required_if:has_offer,1', 'numeric', 'gt:price'],
            'has_wholesale_price' => ['nullable', 'boolean'],
            'wholesale_min_quantity' => $wholesaleRules,
            'wholesale_price' => $wholesalePriceRules,
            'custom_badges' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'string'],
            'sizes' => ['nullable', 'string', 'max:1000'],
            'colors' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:2048'],
            'ai_generated_image_path' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:2048'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'offer_original_price.required_if' => 'Agrega el precio antes de la oferta.',
            'offer_original_price.gt' => 'El precio antes de la oferta debe ser mayor que el precio actual.',
            'wholesale_min_quantity.required_if' => 'Agrega la cantidad mínima para activar el precio mayorista.',
            'wholesale_min_quantity.min' => 'La cantidad mínima mayorista debe ser de al menos 2 unidades.',
            'wholesale_price.required_if' => 'Agrega el precio mayorista.',
            'wholesale_price.gt' => 'El precio mayorista debe ser mayor que 0.',
            'wholesale_price.lt' => 'El precio mayorista debe ser menor que el precio normal.',
        ];
    }

    public function attributes(): array
    {
        return [
            'store_id' => 'tienda',
            'name' => 'nombre del producto',
            'price' => 'precio',
            'category' => 'categoría',
            'material' => 'material',
            'stock_quantity' => 'stock disponible',
            'offer_original_price' => 'precio antes de la oferta',
            'wholesale_min_quantity' => 'cantidad mínima mayorista',
            'wholesale_price' => 'precio mayorista',
            'custom_badges' => 'etiquetas personalizadas',
            'description' => 'descripción',
            'features' => 'características',
            'sizes' => 'tallas',
            'colors' => 'colores',
            'image' => 'imagen principal',
            'images' => 'galería de imágenes',
            'images.*' => 'imagen de galería',
        ];
    }

    public function baseData(): array
    {
        $data = $this->safe()->only([
            'name',
            'category',
            'material',
            'price',
            'description',
            'stock_quantity',
            'offer_original_price',
            'wholesale_min_quantity',
            'wholesale_price',
        ]);
        $data['description'] = ProductText::plain($data['description'] ?? null) ?: null;
        $data['is_sold_out'] = $this->boolean('is_sold_out');
        $data['has_offer'] = $this->boolean('has_offer');
        $data['has_wholesale_price'] = $this->boolean('has_wholesale_price');
        $data['custom_badges'] = $this->cleanBadges($this->input('custom_badges'));

        if (! $data['has_offer']) {
            $data['offer_original_price'] = null;
        }

        if (! $data['has_wholesale_price']) {
            $data['wholesale_min_quantity'] = null;
            $data['wholesale_price'] = null;
        }

        return $data;
    }

    private function cleanBadges(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($badge) => trim(preg_replace('/\s+/', ' ', (string) $badge) ?? ''))
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();
    }

    private function storeForProductRequest(): ?Store
    {
        $routeProduct = $this->route('product');

        if ($routeProduct instanceof Product) {
            return $routeProduct->store;
        }

        if ($this->user()?->isAdmin() && $this->filled('store_id')) {
            return Store::find($this->integer('store_id'));
        }

        return $this->user()?->store;
    }
}
