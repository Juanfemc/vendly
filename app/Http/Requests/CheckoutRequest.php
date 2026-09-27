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
            'email' => [$checkoutFieldPresence('email'), 'email', 'max:120'],
            'name' => ['required', 'string', 'min:2', 'max:80', "regex:/^[\\pL .'\\-]+$/u"],
            'last_name' => [$checkoutFieldPresence('last_name'), 'string', 'min:2', 'max:100', "regex:/^[\\pL .'\\-]+$/u"],
            'phone' => ['required', 'string', 'min:7', 'max:20', 'regex:/^[0-9+() -]+$/'],
            'address' => [$checkoutFieldPresence('address'), 'string', 'min:5', 'max:160', "regex:/^[\\pL0-9#.,°º\\/ -]+$/u"],
            'apartment' => [$checkoutFieldPresence('apartment'), 'string', 'max:120', "regex:/^[\\pL0-9#.,°º\\/ -]+$/u"],
            'neighborhood' => [$checkoutFieldPresence('neighborhood'), 'string', 'max:80', "regex:/^[\\pL0-9 .'\\-]+$/u"],
            'department_code' => [$usesColombiaLocations && $locationEnabled ? $locationPresence : 'nullable', 'string', 'max:8'],
            'city_code' => array_filter([
                $usesColombiaLocations && $locationEnabled ? $locationPresence : 'nullable',
                'string',
                'max:12',
                $usesColombiaLocations && $locationEnabled
                    ? Rule::exists('colombia_locations', 'city_code')->where('department_code', (string) $this->input('department_code'))
                    : null,
            ]),
            'city' => [$usesColombiaLocations || ! $locationEnabled ? 'nullable' : $locationPresence, 'string', 'min:2', 'max:80', "regex:/^[\\pL .'\\-]+$/u"],
            'region' => ['nullable', 'string', 'max:80', "regex:/^[\\pL .'\\-]+$/u"],
            'document' => [$checkoutFieldPresence('document'), 'string', 'min:5', 'max:20', 'regex:/^[0-9A-Za-z.-]+$/'],
            'shipping_method' => ['nullable', 'string', 'max:20'],
            'discount_code' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/'],
            ...self::reservationRules($isReservationStore),
            'notes' => [$checkoutFieldPresence('notes'), 'string', 'max:500'],
            'terms_acceptance' => [$requiresTermsAcceptance ? 'accepted' : 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms_acceptance.accepted' => 'Debes aceptar los términos y condiciones de la tienda para continuar.',
            'name.regex' => 'Escribe un nombre válido, solo letras y espacios.',
            'last_name.regex' => 'Escribe apellidos válidos, solo letras y espacios.',
            'phone.regex' => 'Escribe un número de teléfono válido.',
            'address.regex' => 'Escribe una dirección válida.',
            'apartment.regex' => 'Usa letras, números y referencias simples.',
            'neighborhood.regex' => 'Escribe un barrio válido.',
            'city.regex' => 'Escribe una ciudad válida.',
            'region.regex' => 'Escribe una región válida.',
            'document.regex' => 'Escribe un documento válido.',
            'discount_code.regex' => 'El cupón solo puede tener letras, números, guion o guion bajo.',
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
