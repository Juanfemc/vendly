<?php

namespace App\Http\Requests;

use App\Models\Store;
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
            'business_type' => $store?->business_type ?: 'store',
            'whatsapp' => $phone,
            'subdomain' => $subdomain,
        ]);
    }

    public function rules(): array
    {
        $store = $this->user()?->store ?? $this->user()?->stores()->first();
        $allowsSubdomain = Store::supportsSubdomainColumn() && (bool) $store?->allowsSubdomain();

        return [
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', Rule::in(array_keys(Store::businessTypeOptions()))],
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
            'shop_copy' => ['nullable', 'string', 'max:320'],
            'brand_color' => ['nullable', 'regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
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

    public function onboardingData(): array
    {
        $validated = $this->validated();
        $brandColor = trim((string) ($validated['brand_color'] ?? ''));

        if ($brandColor !== '' && ! str_starts_with($brandColor, '#')) {
            $brandColor = '#' . $brandColor;
        }

        $data = [
            'name' => $validated['name'],
            'business_type' => $validated['business_type'],
            'whatsapp' => $validated['whatsapp'],
            'location' => $validated['location'] ?? null,
            'shop_copy' => $validated['shop_copy'] ?? null,
            'brand_color' => $brandColor ?: '#ff6b00',
            'text_color' => Store::automaticTextColorFor('#ffffff'),
            'background_color' => '#ffffff',
        ];

        $store = $this->user()?->store ?? $this->user()?->stores()->first();

        if (Store::supportsSubdomainColumn()) {
            $data['subdomain'] = $store?->allowsSubdomain() ? ($validated['subdomain'] ?? null) : null;
        }

        if (Store::supportsHeroOverlayColumns()) {
            $data['show_hero_overlay'] = $this->boolean('show_hero_overlay', false);
            $data['hero_overlay_eyebrow'] = trim((string) ($validated['hero_overlay_eyebrow'] ?? '')) ?: null;
            $data['hero_overlay_title'] = trim((string) ($validated['hero_overlay_title'] ?? '')) ?: null;
            $data['hero_overlay_button_text'] = trim((string) ($validated['hero_overlay_button_text'] ?? '')) ?: null;
            $data['hero_overlay_button_url'] = $this->normalizeHeroOverlayUrl($validated['hero_overlay_button_url'] ?? null);
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
