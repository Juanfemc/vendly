<?php

namespace App\Http\Requests;

use App\Models\ColombiaLocation;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! ColombiaLocation::hasCatalog()) {
            return;
        }

        $location = ColombiaLocation::where('city_code', (string) $this->input('city_code'))
            ->where('department_code', (string) $this->input('department_code'))
            ->first();

        if (! $location) {
            return;
        }

        $this->merge([
            'city' => $location->city_name,
            'region' => $location->department_name,
            'department_code' => $location->department_code,
        ]);
    }

    public function rules(): array
    {
        $store = $this->checkoutStore();
        $isReservationStore = $store?->isReservationStore() ?? false;
        $usesColombiaLocations = ColombiaLocation::hasCatalog();
        $requiresTermsAcceptance = $store?->requiresTermsAcceptance() ?? false;
        $checkoutFieldEnabled = fn (string $field): bool => $store?->checkoutFieldEnabled($field) ?? true;
        $checkoutFieldPresence = fn (string $field): string => $checkoutFieldEnabled($field) && ($store?->checkoutFieldRequired($field) ?? false)
            ? 'required'
            : 'nullable';
        $locationEnabled = $checkoutFieldEnabled('city');
        $locationPresence = $locationEnabled && ($store?->checkoutFieldRequired('city') ?? true)
            ? 'required'
            : 'nullable';

        return [
            'email' => [$checkoutFieldPresence('email'), 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'last_name' => [$checkoutFieldPresence('last_name'), 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => [$checkoutFieldPresence('address'), 'string', 'max:255'],
            'apartment' => [$checkoutFieldPresence('apartment'), 'string', 'max:255'],
            'neighborhood' => [$checkoutFieldPresence('neighborhood'), 'string', 'max:255'],
            'department_code' => [$usesColombiaLocations && $locationEnabled ? $locationPresence : 'nullable', 'string', 'max:8'],
            'city_code' => array_filter([
                $usesColombiaLocations && $locationEnabled ? $locationPresence : 'nullable',
                'string',
                'max:12',
                $usesColombiaLocations && $locationEnabled
                    ? Rule::exists('colombia_locations', 'city_code')->where('department_code', (string) $this->input('department_code'))
                    : null,
            ]),
            'city' => [$usesColombiaLocations || ! $locationEnabled ? 'nullable' : $locationPresence, 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'document' => [$checkoutFieldPresence('document'), 'string', 'max:255'],
            'shipping_method' => ['nullable', 'string', 'max:20'],
            'discount_code' => ['nullable', 'string', 'max:60'],
            ...self::reservationRules($isReservationStore),
            'notes' => [$checkoutFieldPresence('notes'), 'string', 'max:1000'],
            'terms_acceptance' => [$requiresTermsAcceptance ? 'accepted' : 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms_acceptance.accepted' => 'Debes aceptar los terminos y condiciones de la tienda para continuar.',
        ];
    }

    public static function reservationRules(bool $required = true): array
    {
        $presenceRule = $required ? 'required' : 'nullable';

        return [
            'reservation_date' => [$presenceRule, 'date', 'after_or_equal:today'],
            'reservation_time' => [$presenceRule, 'date_format:H:i'],
        ];
    }

    private function checkoutStore(): ?Store
    {
        $slug = $this->input('store') ?: $this->query('store');

        if (! $slug) {
            return null;
        }

        return Store::where('slug', $slug)->first();
    }
}
