@php
    $fashionSocialClass = trim('fashion-social-links ' . ($class ?? ''));
    $fashionSocialLabel = $label ?? 'Redes sociales';
    $normalizeFashionSocialUrl = function (?string $value, string $network = ''): string {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        if (str_starts_with($value, '@')) {
            $handle = ltrim($value, '@');

            return match ($network) {
                'instagram' => 'https://instagram.com/' . $handle,
                'tiktok' => 'https://tiktok.com/@' . $handle,
                default => '',
            };
        }

        if (str_contains($value, '.')) {
            return 'https://' . ltrim($value, '/');
        }

        return '';
    };

    $fashionSocialLinks = collect([
        [
            'name' => 'Instagram',
            'url' => $normalizeFashionSocialUrl($store->instagram_url, 'instagram'),
            'icon' => asset('images/icons/icon-instagram.png'),
        ],
        [
            'name' => 'Facebook',
            'url' => $normalizeFashionSocialUrl($store->facebook_url, 'facebook'),
            'icon' => asset('images/icons/icon-facebook.png'),
        ],
        [
            'name' => 'TikTok',
            'url' => $normalizeFashionSocialUrl($store->tiktok_url, 'tiktok'),
            'icon' => asset('images/icons/icon-tik-tok.png'),
        ],
        [
            'name' => 'WhatsApp',
            'url' => $store->whatsappInfoUrl() ?: '',
            'icon' => asset('images/icons/icon-whatsapp.png'),
        ],
    ])->filter(fn ($social) => $social['url'] !== '')->values();
@endphp

@if($fashionSocialLinks->isNotEmpty())
    <div class="{{ $fashionSocialClass }}" aria-label="{{ $fashionSocialLabel }}">
        @foreach($fashionSocialLinks as $social)
            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['name'] }}">
                <img src="{{ $social['icon'] }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
            </a>
        @endforeach
    </div>
@endif
