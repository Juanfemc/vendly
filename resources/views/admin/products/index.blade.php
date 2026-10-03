@extends('layouts.admin')

@section('content')
@php
    $currentProductStore = $selectedStore ?? $store ?? null;
    $productSearch = $productSearch ?? request('q', '');
    $productsTotal = method_exists($products, 'total') ? $products->total() : $products->count();
    $storeProductCount = $currentProductStore ? $currentProductStore->products()->count() : $productsTotal;
    $productLimit = $currentProductStore?->productLimit();
    $isProductSearchActive = trim((string) $productSearch) !== '';
    $canManageCategories = auth()->user()->isAdmin() || ($currentProductStore?->allowsCategories() ?? true);
@endphp

<style>
    .products-console {
        display: grid;
        gap: 24px;
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }

    .products-console-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .products-tabs {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        max-width: 100%;
        padding: 6px;
        border: 1px solid #dfe6ea;
        border-radius: 16px;
        background: #f1f4f6;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .72);
    }

    .products-tab {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
        padding: 0 18px;
        border-radius: 12px;
        color: #557679;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
        transition: background .18s ease, color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .products-tab:hover {
        transform: translateY(-1px);
        color: #063a39;
    }

    .products-tab.is-active {
        background: #ffffff;
        color: #063a39;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .09), inset 0 0 0 1px rgba(15, 23, 42, .04);
    }

    .products-tab svg,
    .products-action svg,
    .products-search-icon svg,
    .product-card-action svg,
    .product-empty-icon svg {
        width: 20px;
        height: 20px;
        flex: 0 0 auto;
    }

    .products-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        min-width: 0;
        flex-wrap: wrap;
    }

    .products-action {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 0 18px;
        border-radius: 13px;
        border: 1px solid #00594d;
        background: #00594d;
        color: #ffffff;
        font-weight: 900;
        text-decoration: none;
        box-shadow: 0 12px 24px rgba(0, 89, 77, .22);
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .products-action:hover {
        transform: translateY(-1px);
        background: #00483f;
        box-shadow: 0 16px 30px rgba(0, 89, 77, .26);
    }

    .products-action--secondary {
        border-color: #d8e2e8;
        background: #ffffff;
        color: #063a39;
        box-shadow: 0 10px 20px rgba(15, 23, 42, .08);
    }

    .products-action--secondary:hover {
        background: #f8fbfc;
        box-shadow: 0 14px 26px rgba(15, 23, 42, .11);
    }

    .products-limit {
        min-height: 42px;
        display: flex;
        align-items: center;
        padding: 10px 16px;
        border: 1px solid #9cf5c7;
        border-radius: 12px;
        background: #eafff3;
        color: #064437;
        font-size: 14px;
        font-weight: 800;
    }

    .products-toolbar {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        min-width: 0;
        padding: 24px;
        border: 1px solid #dfe6ea;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 14px 32px rgba(15, 23, 42, .055);
    }

    .products-search-field {
        position: relative;
        min-width: 0;
    }

    .products-search-icon {
        position: absolute;
        top: 50%;
        left: 16px;
        transform: translateY(-50%);
        color: #8fb0b2;
        pointer-events: none;
    }

    .products-search-field input {
        width: 100%;
        min-height: 48px;
        margin: 0;
        padding: 0 16px 0 48px;
        border: 1px solid #d9e3e7;
        border-radius: 13px;
        background: #ffffff;
        color: #16383c;
        font-size: 14px;
        box-shadow: none;
    }

    .products-search-field input:focus {
        outline: none;
        border-color: #12b89f;
        box-shadow: 0 0 0 4px rgba(18, 184, 159, .12);
    }

    .products-toolbar-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        min-width: 0;
    }

    .products-toolbar-actions .btn,
    .products-toolbar-actions .btn-secondary {
        min-height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        margin-bottom: 0;
    }

    .products-owner-panel {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        min-width: 0;
        padding: 18px 20px;
        border: 1px solid #dfe6ea;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .045);
    }

    .products-owner-panel h3 {
        margin: 0;
        color: #063a39;
        font-size: 18px;
    }

    .products-owner-panel p {
        margin: 4px 0 0;
        color: #6a8588;
        font-size: 13px;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
        align-items: stretch;
        min-width: 0;
        max-width: 100%;
    }

    .product-card {
        min-width: 0;
        display: grid;
        grid-template-rows: auto 1fr auto;
        overflow: hidden;
        border: 1px solid #e0e7eb;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 16px 34px rgba(15, 23, 42, .065);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .product-card:hover {
        transform: translateY(-3px);
        border-color: #c8d7dc;
        box-shadow: 0 20px 42px rgba(15, 23, 42, .1);
    }

    .product-card-media {
        position: relative;
        display: block;
        aspect-ratio: 4 / 4.2;
        overflow: hidden;
        background: linear-gradient(135deg, #f3f7f8, #eaf0f2);
        text-decoration: none;
    }

    .product-card-media img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .product-card-placeholder {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        color: #7aa4a5;
        font-weight: 900;
    }

    .product-card-placeholder span {
        width: 64px;
        height: 64px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: #e8fff6;
        color: #007c68;
        font-size: 24px;
    }

    .product-card-body {
        display: grid;
        gap: 12px;
        padding: 18px 18px 14px;
    }

    .product-card-kicker {
        margin: 0;
        color: #6b9a9b;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .product-card-title {
        margin: 0;
        color: #063a39;
        font-size: 18px;
        line-height: 1.25;
        letter-spacing: 0;
        overflow-wrap: anywhere;
    }

    .product-card-title a {
        color: inherit;
        text-decoration: none;
    }

    .product-card-title a:hover {
        text-decoration: underline;
        text-underline-offset: 4px;
    }

    .product-card-meta {
        display: grid;
        gap: 5px;
        color: #6a8588;
        font-size: 13px;
        line-height: 1.4;
        min-width: 0;
    }

    .product-card-meta span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .product-card-price {
        margin: 2px 0 0;
        color: #00594d;
        font-size: 21px;
        font-weight: 900;
        line-height: 1;
    }

    .product-card-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        min-width: 0;
        padding: 0 18px 18px;
    }

    .product-card-action {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 12px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 900;
        min-width: 0;
    }

    .product-card-action--edit {
        border: 1px solid #00594d;
        background: #00594d;
        color: #ffffff;
    }

    .product-card-delete {
        margin: 0;
    }

    .product-card-delete .product-card-action {
        width: 42px;
        padding: 0;
        border: 1px solid #ffd1d1;
        background: #fff7f7;
        color: #d82121;
        cursor: pointer;
    }

    .product-card-delete span {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
    }

    .products-admin-store-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 16px;
    }

    .products-store-card {
        display: grid;
        gap: 18px;
        padding: 20px;
        border: 1px solid #dfe6ea;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 14px 32px rgba(15, 23, 42, .055);
    }

    .products-store-card h3 {
        margin: 0;
        color: #063a39;
        font-size: 18px;
    }

    .products-store-card p {
        margin: 5px 0 0;
        color: #6a8588;
        font-size: 13px;
    }

    .products-store-count {
        width: fit-content;
        padding: 5px 10px;
        border-radius: 999px;
        background: #eafff3;
        color: #00594d;
        font-size: 12px;
        font-weight: 900;
    }

    .products-empty {
        display: grid;
        place-items: center;
        gap: 12px;
        min-height: 280px;
        padding: 34px;
        border: 1px solid #dfe6ea;
        border-radius: 18px;
        background: #ffffff;
        text-align: center;
        box-shadow: 0 14px 32px rgba(15, 23, 42, .055);
    }

    .product-empty-icon {
        width: 56px;
        height: 56px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: #eafff3;
        color: #00594d;
    }

    .products-empty h3 {
        margin: 0;
        color: #063a39;
        font-size: 22px;
    }

    .products-empty p {
        max-width: 440px;
        margin: 0;
        color: #6a8588;
        line-height: 1.55;
    }

    @media (max-width: 760px) {
        .products-console-head,
        .products-owner-panel {
            align-items: stretch;
            flex-direction: column;
        }

        .products-tabs,
        .products-actions,
        .products-action,
        .products-toolbar,
        .products-toolbar-actions {
            width: 100%;
        }

        .products-tab {
            flex: 1 1 0;
            justify-content: center;
            padding-inline: 12px;
        }

        .products-toolbar {
            grid-template-columns: 1fr;
            padding: 16px;
        }

        .products-toolbar-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .products-toolbar-actions .btn,
        .products-toolbar-actions .btn-secondary {
            width: 100%;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 180px), 1fr));
            gap: 14px;
        }

        .product-card {
            border-radius: 16px;
        }

        .product-card-media {
            aspect-ratio: 1 / 1;
        }

        .product-card-body {
            padding: 14px 14px 12px;
        }

        .product-card-title {
            font-size: 16px;
        }

        .product-card-price {
            font-size: 18px;
        }

        .product-card-actions {
            padding: 0 14px 14px;
        }
    }

    @media (max-width: 520px) {
        .products-console {
            gap: 16px;
        }

        .products-console-head {
            gap: 12px;
        }

        .products-tabs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
            gap: 5px;
        }

        .products-tab {
            min-height: 42px;
            gap: 6px;
            padding-inline: 8px;
            font-size: 12px;
        }

        .products-tab svg,
        .products-action svg,
        .products-search-icon svg,
        .product-card-action svg,
        .product-empty-icon svg {
            width: 18px;
            height: 18px;
        }

        .products-action {
            min-height: 44px;
            padding-inline: 14px;
            border-radius: 12px;
        }

        .products-limit,
        .products-toolbar,
        .products-owner-panel,
        .products-empty {
            border-radius: 14px;
        }

        .products-toolbar {
            padding: 12px;
        }

        .products-search-field input {
            min-height: 46px;
            padding-left: 42px;
            font-size: 16px;
        }

        .products-search-icon {
            left: 13px;
        }

        .products-toolbar-actions {
            grid-template-columns: 1fr;
        }

        .products-grid,
        .products-admin-store-grid {
            grid-template-columns: 1fr;
        }

        .product-card-actions {
            grid-template-columns: minmax(0, 1fr) 44px;
            gap: 8px;
        }

        .product-card-action {
            min-height: 44px;
        }

        .product-card-delete .product-card-action {
            width: 44px;
        }
    }

    .products-console .products-tabs,
    .products-console .products-limit,
    .products-console .products-store-count,
    .products-console .product-empty-icon,
    .products-console .product-card-placeholder span {
        background: var(--vendly-brand-soft);
    }

    .products-console .products-tab,
    .products-console .products-tab:hover,
    .products-console .products-tab.is-active,
    .products-console .products-owner-panel h3,
    .products-console .products-store-card h3,
    .products-console .products-empty h3,
    .products-console .product-card-title,
    .products-console .products-limit,
    .products-console .products-store-count,
    .products-console .product-empty-icon,
    .products-console .product-card-placeholder,
    .products-console .product-card-price {
        color: var(--vendly-brand);
    }

    .products-console .products-action,
    .products-console .product-card-action--edit {
        border-color: var(--vendly-brand);
        background: var(--vendly-brand);
        color: var(--vendly-brand-contrast);
        box-shadow: 0 12px 24px var(--vendly-brand-shadow);
    }

    .products-console .products-action:hover {
        background: var(--vendly-brand);
        box-shadow: 0 16px 30px var(--vendly-brand-shadow);
    }

    .products-console .products-action--secondary {
        border-color: #d8e2e8;
        background: #ffffff;
        color: #063a39;
        box-shadow: 0 10px 20px rgba(15, 23, 42, .08);
    }

    .products-console .products-action--secondary:hover {
        background: #f8fbfc;
        color: #063a39;
        box-shadow: 0 14px 26px rgba(15, 23, 42, .11);
    }

    .products-console .products-search-icon {
        color: var(--vendly-brand);
    }

    .products-console .products-search-field input:focus {
        border-color: var(--vendly-brand);
        box-shadow: 0 0 0 4px var(--vendly-brand-focus);
    }
</style>

<div class="products-console">
    <div class="products-console-head">
        <div class="products-console-title">
            <h1>Productos</h1>
            <p>Gestiona los productos de tu tienda.</p>
        </div>

        <div class="products-actions">
            <a href="{{ route('admin.products.import') }}" class="products-action products-action--secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>
                Importar
            </a>
            @if($canManageCategories)
                <a href="{{ auth()->user()->isAdmin() && ! empty($selectedStore) ? route('admin.stores.categories.index', $selectedStore) : route('admin.categories.index') }}" class="products-action products-action--secondary">
                    Categorías
                </a>
            @endif
            <a href="/admin/products/create" class="products-action">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                Agregar producto
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    @if($currentProductStore && $productLimit)
        <div class="products-limit">
            {{ $storeProductCount }} de {{ $productLimit }} productos disponibles
        </div>
    @endif

    <form method="GET" action="{{ url()->current() }}" class="products-toolbar" data-admin-live-search>
        <div class="products-search-field">
            <span class="products-search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
            </span>
            <input
                id="productSearchInput"
                type="search"
                name="q"
                value="{{ $productSearch }}"
                placeholder="{{ auth()->user()->isAdmin() && empty($selectedStore) ? 'Buscar tienda o producto...' : 'Buscar productos...' }}"
                autocomplete="off"
            >
        </div>
        <div class="products-toolbar-actions">
            <button type="submit" class="btn">Buscar</button>
            @if($isProductSearchActive)
                <a href="{{ url()->current() }}" class="btn btn-secondary">Limpiar</a>
            @endif
        </div>
    </form>

    @if(auth()->user()->isAdmin() && empty($selectedStore))
        <div class="products-admin-store-grid">
            @foreach(($stores ?? collect()) as $storeOption)
                <article class="products-store-card">
                    <div>
                        <h3>{{ $storeOption->name }}</h3>
                        <p>Productos registrados en esta tienda.</p>
                    </div>
                    <span class="products-store-count">{{ $storeOption->products_count }} producto(s)</span>
                    <a href="{{ route('admin.stores.products.index', $storeOption) }}" class="btn">Ver productos</a>
                </article>
            @endforeach
        </div>

        @if(($stores ?? collect())->count() === 0 && $isProductSearchActive)
            <div class="products-empty">
                <span class="product-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
                </span>
                <h3>No encontramos tiendas o productos</h3>
                <p>Prueba con otro nombre de tienda o producto.</p>
                <a href="{{ url()->current() }}" class="btn btn-secondary">Limpiar búsqueda</a>
            </div>
        @endif

        @if(($stores ?? null) && method_exists($stores, 'hasPages') && $stores->hasPages())
            <div class="list-card admin-pagination">
                {{ $stores->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        @endif
    @else
        @if(auth()->user()->isAdmin() && ! empty($selectedStore))
            <div class="products-owner-panel">
                <div>
                    <h3>{{ $selectedStore->name }}</h3>
                    <p>Productos de esta tienda</p>
                </div>
                <a href="/admin/products" class="btn btn-secondary">Volver a tiendas</a>
            </div>
        @endif

        @if($products->isEmpty())
            <div class="products-empty">
                <span class="product-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 16-9 5-9-5"></path><path d="m21 12-9 5-9-5"></path><path d="m12 3 9 5-9 5-9-5 9-5Z"></path></svg>
                </span>
                @if($isProductSearchActive)
                    <h3>No encontramos productos</h3>
                    <p>Prueba con otro nombre, categoría, material o descripción.</p>
                    <a href="{{ url()->current() }}" class="btn btn-secondary">Limpiar búsqueda</a>
                @else
                    <h3>No hay productos registrados</h3>
                    <p>Agrega el primer producto para empezar a mostrar el catálogo en la tienda.</p>
                    <a href="/admin/products/create" class="btn">Nuevo producto</a>
                @endif
            </div>
        @else
            <div class="vendly-table-card products-table-card">
                <div class="vendly-table-scroll">
                    <table class="vendly-data-table products-data-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Inventario</th>
                                <th>Estado</th>
                                @if(auth()->user()->isAdmin())
                                    <th>Tienda</th>
                                @endif
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                @php
                                    $isSoldOut = (bool) ($product->is_sold_out ?? false);
                                    $inventoryLabel = ($product->store?->isReservationStore() ?? false) ? 'No aplica' : ($product->stockLabel() ?? 'Ilimitado');
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="vendly-product-cell">
                                            <span class="vendly-product-thumb">
                                                @if($product->image)
                                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}">
                                                @else
                                                    <span>{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
                                                @endif
                                            </span>
                                            <span>
                                                <strong>{{ $product->name }}</strong>
                                                @if($product->material)
                                                    <small>Material: {{ $product->material }}</small>
                                                @endif
                                            </span>
                                        </a>
                                    </td>
                                    <td>{{ $product->category ?: 'Sin categoría' }}</td>
                                    <td><strong>$ {{ number_format((float) $product->price, 0, ',', '.') }}</strong></td>
                                    <td>{{ $inventoryLabel }}</td>
                                    <td>
                                        <span @class(['vendly-status', 'is-danger' => $isSoldOut, 'is-success' => ! $isSoldOut])>
                                            {{ $isSoldOut ? 'Agotado' : 'Disponible' }}
                                        </span>
                                    </td>
                                    @if(auth()->user()->isAdmin())
                                        <td>{{ $product->store?->name ?? 'Sin tienda' }}</td>
                                    @endif
                                    <td>
                                        <div class="vendly-row-actions">
                                            <a href="{{ route('admin.products.edit', $product) }}" aria-label="Editar {{ $product->name }}">Editar</a>
                                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm-delete data-confirm-message="¿Seguro que quieres eliminar este producto? Esta acción no se puede deshacer.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" aria-label="Eliminar {{ $product->name }}">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(method_exists($products, 'hasPages') && $products->hasPages())
            <div class="list-card admin-pagination">
                {{ $products->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        @endif
    @endif
</div>
@endsection
