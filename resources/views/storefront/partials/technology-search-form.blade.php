@php
    $technologySearchId = $technologySearchId ?? 'technologySearch';
    $technologySearchClass = $technologySearchClass ?? '';
    $technologySearchValue = trim((string) request('q', ''));
    $technologySearchProductsSource = collect($technologySearchProducts ?? []);

    if ($technologySearchProductsSource->isEmpty()) {
        $technologySearchProductsSource = collect($allProducts ?? [])
            ->merge(collect($products ?? []))
            ->merge(collect($relatedProducts ?? []));
    }

    if ($technologySearchProductsSource->isEmpty()) {
        $technologySearchProductsSource = $store->products()
            ->latest()
            ->take(48)
            ->get();
    }

    $technologySearchProducts = $technologySearchProductsSource
        ->filter(fn ($product) => $product instanceof \App\Models\Product)
        ->unique('id')
        ->take(48)
        ->map(fn ($product) => [
            'name' => (string) $product->name,
            'search' => \Illuminate\Support\Str::lower((string) $product->name . ' ' . (string) $product->category . ' ' . (string) $product->material),
            'price' => '$ ' . number_format((float) $product->price, 0, ',', '.'),
            'image' => $product->image ? asset('storage/' . $product->image) : null,
            'url' => $storefrontUrls->product($store, $product),
        ])
        ->values();
@endphp

<form
    class="tech-inline-search {{ $technologySearchClass }}"
    action="{{ $storefrontUrls->products($store) }}"
    method="GET"
    role="search"
    data-storefront-search
>
    <button class="tech-search-submit" type="submit" aria-label="Buscar productos">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="6"></circle>
            <path d="m16 16 4 4"></path>
        </svg>
    </button>
    <input
        id="{{ $technologySearchId }}"
        type="search"
        name="q"
        value="{{ $technologySearchValue }}"
        placeholder="Buscar productos"
        autocomplete="off"
        data-storefront-search-input
    >
    <button
        class="tech-search-clear-button"
        type="button"
        aria-label="Limpiar busqueda"
        data-storefront-search-clear
        @if($technologySearchValue === '') hidden @endif
    >
        &times;
    </button>

    <div class="tech-search-suggestions" data-storefront-search-results hidden>
        @foreach($technologySearchProducts as $technologySearchProduct)
            <a
                href="{{ $technologySearchProduct['url'] }}"
                class="tech-search-suggestion"
                data-storefront-search-item
                data-search-name="{{ $technologySearchProduct['search'] }}"
            >
                <span class="tech-search-suggestion-thumb">
                    @if($technologySearchProduct['image'])
                        <img src="{{ $technologySearchProduct['image'] }}" alt="" loading="lazy" decoding="async">
                    @else
                        <span>{{ mb_strtoupper(mb_substr($technologySearchProduct['name'], 0, 1)) }}</span>
                    @endif
                </span>
                <span class="tech-search-suggestion-copy">
                    <strong>{{ \Illuminate\Support\Str::limit($technologySearchProduct['name'], 30) }}</strong>
                    <small>{{ $technologySearchProduct['price'] }}</small>
                </span>
            </a>
        @endforeach
        <a
            href="{{ $technologySearchValue !== '' ? $storefrontUrls->products($store) . '?q=' . urlencode($technologySearchValue) : $storefrontUrls->products($store) }}"
            class="tech-search-suggestion-all"
            data-storefront-search-all
        >
            Ver resultados
        </a>
    </div>
</form>
