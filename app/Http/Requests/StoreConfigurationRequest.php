<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStoreProfile;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class StoreConfigurationRequest extends FormRequest
{
    use ValidatesStoreProfile;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subdomain' => Store::normalizeSubdomain($this->input('subdomain')),
            'custom_domain' => Store::normalizeCustomDomain($this->input('custom_domain')),
        ]);
    }

    public function rules(): array
    {
        $profileRules = $this->storeProfileRules();

        return [
            'subdomain' => $profileRules['subdomain'],
            'custom_domain' => $profileRules['custom_domain'],
            'checkout_fields' => ['nullable', 'array'],
            'checkout_fields.*.enabled' => ['nullable', 'boolean'],
            'checkout_fields.*.required' => ['nullable', 'boolean'],
            'require_terms_acceptance' => ['nullable', 'boolean'],
            'terms_title' => ['nullable', 'string', 'max:120'],
            'terms_content' => ['nullable', 'string', 'max:5000'],
            'terms_url' => ['nullable', 'url', 'max:255'],
            'terms_version' => ['nullable', 'string', 'max:80'],
        ];
    }
}
