<?php

namespace App\Http\Requests;

use App\Models\ColombiaLocation;
use App\Models\Store;
use App\Support\StoreOnboardingSteps;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $store = $this->user()?->store ?? $this->user()?->stores()->first();
        $phone = preg_replace('/\D+/', '', (string) $this->input('whatsapp')) ?: '';
        $subdomain = Store::normalizeSubdomain(
            $this->input('subdomain') ?: $this->input('name') ?: $store?->subdomain ?: $store?->name
        );

        if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
            $phone = '57'.$phone;
        }

        $this->merge([
            'whatsapp' => $phone,
            'subdomain' => $subdomain,
            'step' => in_array($this->input('step'), StoreOnboardingSteps::allKeys(), true)
                ? $this->input('step')
                : StoreOnboardingSteps::BASIC,
        ]);
    }

    public function rules(): array
    {
        $store = $this->user()?->store ?? $this->user()?->stores()->first();
        $allowsSubdomain = Store::supportsSubdomainColumn() && (bool) $store?->allowsSubdomain();
        $step = $this->stepKey();
        $base = [
            'step' => ['required', Rule::in(StoreOnboardingSteps::allKeys())],
            'intent' => ['nullable', Rule::in(['continue', 'back', 'exit', 'finish'])],
        ];

        if (! in_array($step, $this->availableStepKeys($store), true)) {
            return $base;
        }

        if ($step === 'basic') {
            return $base + [
                'name' => ['required', 'string', 'max:255'],
                'subdomain' => array_filter([
                    $allowsSubdomain ? 'required' : 'nullable',
                    'string',
                    'max:63',
                    'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/',
                    Rule::notIn(Store::reservedSubdomains()),
                    $allowsSubdomain
                        ? Rule::unique('stores', 'subdomain')->ignore($store?->id)
                        : null,
                ]),
                'whatsapp' => ['required', 'regex:/^573\d{9}$/'],
                'location' => ['nullable', 'string', 'max:255'],
            ];
        }

        if ($step === 'identity') {
            return $base + [
                'shop_copy' => ['nullable', 'string', 'max:320'],
                'brand_color' => ['nullable', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
                'background_color' => ['nullable', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
                'font_family' => ['nullable', Rule::in(array_keys(Store::fontFamilyOptions()))],
                'logo_image' => ['nullable', 'image', 'max:4096'],
                'cover_image' => ['nullable', 'image', 'max:4096'],
                'ai_generated_logo_path' => ['nullable', 'string', 'max:255'],
                'ai_generated_cover_path' => ['nullable', 'string', 'max:255'],
                'show_hero_overlay' => ['nullable', 'boolean'],
                'hero_overlay_eyebrow' => ['nullable', 'string', 'max:80'],
                'hero_overlay_title' => ['nullable', 'string', 'max:120'],
                'hero_overlay_button_text' => ['nullable', 'string', 'max:60'],
                'hero_overlay_button_url' => ['nullable', 'string', 'max:255'],
            ];
        }

        if ($step === 'orders') {
            return $base + [
                'shipping_methods' => ['nullable', 'array', 'max:5'],
                'shipping_methods.*.name' => ['nullable', 'string', 'max:80'],
                'shipping_methods.*.cost' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
                'local_delivery_area' => [
                    'nullable',
                    'string',
                    'max:120',
                    function (string $attribute, mixed $value, \Closure $fail) {
                        if (trim((string) $value) === '' || ! ColombiaLocation::hasCatalog()) {
                            return;
                        }

                        $exists = ColombiaLocation::query()
                            ->where('city_name', $value)
                            ->orWhere('city_code', $value)
                            ->exists();

                        if (! $exists) {
                            $fail('Selecciona una ciudad local valida.');
                        }
                    },
                ],
                'local_delivery_city_code' => array_filter([
                    'nullable',
                    'string',
                    'max:12',
                    ColombiaLocation::hasCatalog()
                        ? Rule::exists('colombia_locations', 'city_code')
                        : null,
                ]),
                'local_delivery_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
                'outside_delivery_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
                'checkout_fields' => ['nullable', 'array'],
                'checkout_fields.*.enabled' => ['nullable', 'boolean'],
                'checkout_fields.*.required' => ['nullable', 'boolean'],
            ];
        }

        if ($step === 'product') {
            $hasProducts = (bool) $store?->products()->exists();
            $creatingProduct = ! $hasProducts && ($this->filled('product_name') || $this->input('intent') === 'continue');
            $requiredWhenCreating = $creatingProduct ? 'required' : 'nullable';

            return $base + [
                'product_name' => [$requiredWhenCreating, 'string', 'max:255'],
                'product_price' => [$requiredWhenCreating, 'numeric', 'min:0'],
                'product_category' => ['nullable', 'string', 'max:255'],
                'product_description' => ['nullable', 'string'],
                'product_features' => ['nullable', 'string'],
                'product_sizes' => ['nullable', 'string', 'max:1000'],
                'product_colors' => ['nullable', 'string', 'max:1000'],
                'image' => ['nullable', 'image', 'max:2048'],
                'ai_generated_image_path' => ['nullable', 'string', 'max:255'],
            ];
        }

        return $base;
    }

    public function stepKey(): string
    {
        $step = (string) $this->input('step', StoreOnboardingSteps::BASIC);

        return in_array($step, StoreOnboardingSteps::allKeys(), true) ? $step : StoreOnboardingSteps::BASIC;
    }

    private function availableStepKeys(?Store $store): array
    {
        return $store ? StoreOnboardingSteps::keys($store) : [StoreOnboardingSteps::BASIC];
    }

    public function onboardingData(?string $step = null, ?Store $store = null): array
    {
        $step ??= $this->stepKey();
        $store ??= $this->user()?->store ?? $this->user()?->stores()->first();
        $validated = $this->validated();

        if ($step === 'basic') {
            $data = [
                'name' => $validated['name'],
                'whatsapp' => $validated['whatsapp'],
                'location' => $validated['location'] ?? null,
            ];

            if (Store::supportsSubdomainColumn()) {
                $data['subdomain'] = $store?->allowsSubdomain() ? ($validated['subdomain'] ?? null) : null;
            }

            return $data;
        }

        if ($step === 'orders') {
            return $this->ordersData($store);
        }

        if ($step !== 'identity') {
            return [];
        }

        $brandColor = trim((string) ($validated['brand_color'] ?? ''));
        $backgroundColor = trim((string) ($validated['background_color'] ?? ''));

        if ($brandColor !== '' && ! str_starts_with($brandColor, '#')) {
            $brandColor = '#' . $brandColor;
        }

        if ($backgroundColor !== '' && ! str_starts_with($backgroundColor, '#')) {
            $backgroundColor = '#' . $backgroundColor;
        }

        $data = [
            'shop_copy' => $validated['shop_copy'] ?? null,
            'brand_color' => $store?->allowsFullCustomization() ? ($brandColor ?: '#ff6b00') : $store?->brand_color,
            'background_color' => $store?->allowsFullCustomization() ? ($backgroundColor ?: '#ffffff') : $store?->background_color,
            'font_family' => $store?->allowsFullCustomization() ? ($validated['font_family'] ?? 'system') : $store?->font_family,
        ];

        $data['text_color'] = Store::automaticTextColorFor($data['background_color']);

        if (Store::supportsHeroOverlayColumns()) {
            $data['show_hero_overlay'] = $this->boolean('show_hero_overlay', false);
            $data['hero_overlay_eyebrow'] = trim((string) ($validated['hero_overlay_eyebrow'] ?? '')) ?: null;
            $data['hero_overlay_title'] = trim((string) ($validated['hero_overlay_title'] ?? '')) ?: null;
            $data['hero_overlay_button_text'] = trim((string) ($validated['hero_overlay_button_text'] ?? '')) ?: null;
            $data['hero_overlay_button_url'] = $this->normalizeHeroOverlayUrl($validated['hero_overlay_button_url'] ?? null);
        }

        return $data;
    }

    private function ordersData(?Store $store): array
    {
        if (! $store || ! $store->allowsShippingMethods() || ! Store::supportsShippingMethodsColumn()) {
            return [];
        }

        $data = [
            'shipping_methods' => collect($this->input('shipping_methods', []))
                ->map(function ($method) {
                    $name = trim((string) ($method['name'] ?? ''));

                    return $name === ''
                        ? null
                        : [
                            'name' => $name,
                            'cost' => max(0, (float) ($method['cost'] ?? 0)),
                        ];
                })
                ->filter()
                ->take(5)
                ->values()
                ->all(),
        ];

        if (Store::supportsLocalDeliveryColumns()) {
            $data['local_delivery_area'] = trim((string) $this->input('local_delivery_area')) ?: null;
            $data['local_delivery_cost'] = $this->filled('local_delivery_cost') ? max(0, (float) $this->input('local_delivery_cost')) : null;
            $data['outside_delivery_cost'] = $this->filled('outside_delivery_cost') ? max(0, (float) $this->input('outside_delivery_cost')) : null;

            if (Store::supportsLocalDeliveryCityCodeColumn()) {
                $data['local_delivery_city_code'] = trim((string) $this->input('local_delivery_city_code')) ?: null;
            }
        }

        if (Store::supportsCheckoutFieldsColumn()) {
            $data['checkout_fields'] = Store::normalizeCheckoutFields($this->input('checkout_fields', []));
        }

        return $data;
    }

    private function normalizeHeroOverlayUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        return '/' . ltrim($value, '/');
    }
}
