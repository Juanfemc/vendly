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
            'icon' => '<rect x="4" y="4" width="16" height="16" rx="4"></rect><circle cx="12" cy="12" r="3.5"></circle><circle cx="17" cy="7" r="1"></circle>',
        ],
        [
            'name' => 'Facebook',
            'url' => $normalizeFashionSocialUrl($store->facebook_url, 'facebook'),
            'icon' => '<path d="M14 8.6h2.1V5.1c-.4-.1-1.7-.2-3.1-.2-3 0-5 1.8-5 5.2v2.9H4.7v4H8v8h4v-8h3.3l.5-4H12v-2.5c0-1.1.3-1.9 2-1.9Z"></path>',
        ],
        [
            'name' => 'TikTok',
            'url' => $normalizeFashionSocialUrl($store->tiktok_url, 'tiktok'),
            'icon' => '<path d="M14.6 4c.4 1.8 1.5 3.1 3.4 3.6v2.7c-1.2 0-2.4-.4-3.4-1.1v5.2a4.8 4.8 0 1 1-4.8-4.8c.3 0 .6 0 .9.1v2.8a2.2 2.2 0 1 0 1.3 2V4h2.6z"></path>',
        ],
        [
            'name' => 'WhatsApp',
            'url' => $store->whatsappInfoUrl() ?: '',
            'icon' => '<path d="M20 11.7A8 8 0 0 1 8.2 18.8L4 20l1.2-4.1A8 8 0 1 1 20 11.7Z"></path><path d="M9 8.5c.2-.4.4-.5.7-.5h.5c.2 0 .4.1.5.4l.7 1.6c.1.3.1.5-.1.7l-.4.5c.6 1.1 1.5 2 2.7 2.6l.5-.5c.2-.2.4-.3.7-.2l1.5.7c.3.1.4.3.4.6v.4c0 .4-.2.7-.5.9-.5.3-1.2.5-2.2.2-2.8-.7-5.1-3-5.8-5.7-.2-.8 0-1.4.3-1.8Z"></path>',
        ],
    ])->filter(fn ($social) => $social['url'] !== '')->values();
@endphp

@if($fashionSocialLinks->isNotEmpty())
    <div class="{{ $fashionSocialClass }}" aria-label="{{ $fashionSocialLabel }}">
        @foreach($fashionSocialLinks as $social)
            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['name'] }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    {!! $social['icon'] !!}
                </svg>
            </a>
        @endforeach
    </div>
@endif
