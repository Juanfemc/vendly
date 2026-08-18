@php
    $fashionSearchId = $fashionSearchId ?? 'fashionSearch';
    $fashionSearchClass = $fashionSearchClass ?? '';
    $fashionSearchValue = trim((string) request('q', ''));
    $fashionSearchProducts = $fashionSearchProducts ?? collect();
@endphp

<form
    class="fashion-inline-search {{ $fashionSearchClass }}"
    action="{{ $storefrontUrls->products($store) }}"
    method="GET"
    role="search"
    data-fashion-search
>
    <button class="fashion-search-submit" type="submit" aria-label="Buscar productos">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="7"></circle>
            <path d="m16.5 16.5 4 4"></path>
        </svg>
    </button>
    <input
        id="{{ $fashionSearchId }}"
        type="search"
        name="q"
        value="{{ $fashionSearchValue }}"
        placeholder="Buscar..."
        autocomplete="off"
        data-fashion-search-input
    >
    <button
        class="fashion-search-clear-button"
        type="button"
        aria-label="Limpiar busqueda"
        data-fashion-search-clear
        @if($fashionSearchValue === '') hidden @endif
    >
        &times;
    </button>

    <div class="fashion-search-suggestions" data-fashion-search-results hidden>
        @foreach($fashionSearchProducts as $fashionSearchProduct)
            <a
                href="{{ $fashionSearchProduct['url'] }}"
                class="fashion-search-suggestion"
                data-fashion-search-item
                data-search-name="{{ $fashionSearchProduct['search'] }}"
            >
                <span class="fashion-search-suggestion-thumb">
                    @if($fashionSearchProduct['image'])
                        <img src="{{ $fashionSearchProduct['image'] }}" alt="" loading="lazy" decoding="async">
                    @else
                        <span>{{ mb_strtoupper(mb_substr($fashionSearchProduct['name'], 0, 1)) }}</span>
                    @endif
                </span>
                <span class="fashion-search-suggestion-copy">
                    <strong>{{ \Illuminate\Support\Str::limit($fashionSearchProduct['name'], 26) }}</strong>
                    <small>{{ $fashionSearchProduct['price'] }}</small>
                </span>
            </a>
        @endforeach
        <a
            href="{{ $storefrontUrls->products($store) }}"
            class="fashion-search-suggestion-all"
            data-fashion-search-all
        >
            Ver resultados
        </a>
    </div>
</form>
