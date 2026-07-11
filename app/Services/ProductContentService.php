<?php

namespace App\Services;

use App\Support\ProductText;

class ProductContentService
{
    public function optionList(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($option) => trim($option))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function cleanRichText(?string $value): ?string
    {
        $value = ProductText::rich($value);

        if ($value === '') {
            return null;
        }

        return $value;
    }
}
