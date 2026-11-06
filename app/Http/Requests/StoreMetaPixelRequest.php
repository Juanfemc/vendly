<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMetaPixelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'meta_pixel_id' => trim((string) $this->input('meta_pixel_id', '')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'meta_pixel_id' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]+$/'],
        ];
    }
}
