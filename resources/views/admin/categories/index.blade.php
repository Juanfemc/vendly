@extends('layouts.admin')

@section('content')
@php
    $currentCategoryStore = $selectedStore ?? $store ?? null;
    $categoryProductCounts = $categoryProductCounts ?? collect();
    $productsUrl = auth()->user()->isAdmin() && ! empty($selectedStore)
        ? route('admin.stores.products.index', $selectedStore)
        : route('admin.products.index');
    $categoriesUrl = auth()->user()->isAdmin() && ! empty($selectedStore)
        ? route('admin.stores.categories.index', $selectedStore)
        : route('admin.categories.index');
@endphp

<style>
    .categories-console {
        display: grid;
        gap: 24px;
    }

    .categories-console-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .catalog-tabs {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px;
        border: 1px solid #dfe6ea;
        border-radius: 16px;
        background: #f1f4f6;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .72);
    }

    .catalog-tab {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 0 18px;
        border-radius: 12px;
        color: #557679;
        font-weight: 800;
        text-decoration: none;
        transition: background .18s ease, color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .catalog-tab:hover {
        transform: translateY(-1px);
        color: #063a39;
    }

    .catalog-tab.is-active {
        background: #ffffff;
        color: #063a39;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .09), inset 0 0 0 1px rgba(15, 23, 42, .04);
    }

    .catalog-tab svg,
    .category-main-action svg,
    .category-row-action svg,
    .category-empty-icon svg {
        width: 20px;
        height: 20px;
        flex: 0 0 auto;
    }

    .category-main-action {
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
        cursor: pointer;
    }

    .category-main-action:hover {
        transform: translateY(-1px);
        background: #00483f;
        box-shadow: 0 16px 30px rgba(0, 89, 77, .26);
    }

    .category-create-card {
        overflow: hidden;
        border: 1px solid #dfe6ea;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 14px 32px rgba(15, 23, 42, .055);
    }

    .category-create-card summary {
        list-style: none;
    }

    .category-create-card summary::-webkit-details-marker {
        display: none;
    }

    .category-create-trigger {
        width: 100%;
        min-height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 22px;
        color: #063a39;
        font-weight: 900;
        cursor: pointer;
    }

    .category-create-trigger small {
        display: block;
        margin-top: 3px;
        color: #6a8588;
        font-weight: 600;
    }

    .category-create-body {
        display: grid;
        gap: 16px;
        padding: 0 22px 22px;
    }

    .category-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .category-field-wide {
        grid-column: 1 / -1;
    }

    .category-form-grid label {
        display: grid;
        gap: 7px;
        margin: 0;
        color: #063a39;
        font-weight: 800;
    }

    .category-form-grid input,
    .category-form-grid textarea,
    .category-form-grid select {
        width: 100%;
        margin: 0;
    }

    .category-form-help {
        margin: 0;
        color: #6a8588;
        font-size: 12px;
        line-height: 1.45;
    }

    .category-inline-check {
        display: inline-flex !important;
        align-items: center;
        gap: 8px !important;
        width: fit-content;
    }

    .category-inline-check input {
        width: auto;
        margin: 0;
    }

    .category-owner-panel,
    .category-lock-panel,
    .category-empty-panel {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 18px 20px;
        border: 1px solid #dfe6ea;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .045);
    }

    .category-owner-panel h3,
    .category-lock-panel h3,
    .category-empty-panel h3 {
        margin: 0;
        color: #063a39;
        font-size: 18px;
    }

    .category-owner-panel p,
    .category-lock-panel p,
    .category-empty-panel p {
        margin: 4px 0 0;
        color: #6a8588;
        font-size: 13px;
    }

    .category-list {
        display: grid;
        gap: 10px;
    }

    .category-row {
        min-width: 0;
        display: grid;
        grid-template-columns: 26px 52px minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border: 1px solid #a9f4dd;
        border-radius: 12px;
        background: #f4fffb;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
    }

    .category-row--child {
        margin-left: 32px;
        border-color: #d9e5ea;
        background: #ffffff;
    }

    .category-row-order {
        display: grid;
        gap: 2px;
        color: #b5c5ca;
    }

    .category-row-order svg {
        width: 14px;
        height: 14px;
    }

    .category-row-mark {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 10px;
        background: #dffcf4;
        color: #007c68;
        font-weight: 900;
    }

    .category-row-mark img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .category-row-content {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .category-row-title {
        min-width: 0;
        margin: 0;
        color: #063a39;
        font-size: 15px;
        font-weight: 900;
        line-height: 1.2;
    }

    .category-row-parent {
        color: #6a8588;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .category-row-count {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #37817b;
        font-size: 14px;
        font-weight: 700;
        white-space: nowrap;
    }

    .category-row-count svg {
        width: 16px;
        height: 16px;
    }

    .category-row-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }

    .category-row-actions form {
        margin: 0;
    }

    .category-row-action {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: #8a9aa5;
        text-decoration: none;
        cursor: pointer;
        transition: background .18s ease, color .18s ease;
    }

    .category-row-action:hover {
        background: #ffffff;
        color: #00594d;
    }

    .category-row-action--danger:hover {
        color: #d82121;
    }

    .category-admin-store-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 16px;
    }

    .category-store-card {
        display: grid;
        gap: 18px;
        padding: 20px;
        border: 1px solid #dfe6ea;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 14px 32px rgba(15, 23, 42, .055);
    }

    .category-store-card h3 {
        margin: 0;
        color: #063a39;
        font-size: 18px;
    }

    .category-store-card p {
        margin: 5px 0 0;
        color: #6a8588;
        font-size: 13px;
    }

    .category-store-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .category-store-pill {
        width: fit-content;
        padding: 5px 10px;
        border-radius: 999px;
        background: #eafff3;
        color: #00594d;
        font-size: 12px;
        font-weight: 900;
    }

    .category-empty-panel {
        justify-content: center;
        min-height: 190px;
        text-align: center;
    }

    .category-empty-icon {
        width: 52px;
        height: 52px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        border-radius: 16px;
        background: #eafff3;
        color: #00594d;
    }

    @media (max-width: 760px) {
        .categories-console-head,
        .category-owner-panel,
        .category-lock-panel {
            align-items: stretch;
            flex-direction: column;
        }

        .catalog-tabs,
        .category-main-action {
            width: 100%;
        }

        .catalog-tab {
            flex: 1 1 0;
            justify-content: center;
            padding-inline: 12px;
        }

        .category-form-grid {
            grid-template-columns: 1fr;
        }

        .category-row {
            grid-template-columns: 42px minmax(0, 1fr) auto;
        }

        .category-row-order {
            display: none;
        }

        .category-row-content {
            align-items: flex-start;
            flex-direction: column;
            gap: 5px;
        }

        .category-row-actions {
            gap: 2px;
        }
    }

    .categories-console .catalog-tabs,
    .categories-console .category-row,
    .categories-console .category-row-mark,
    .categories-console .category-store-pill,
    .categories-console .category-empty-icon {
        background: var(--vendly-brand-soft);
    }

    .categories-console .catalog-tab,
    .categories-console .catalog-tab:hover,
    .categories-console .catalog-tab.is-active,
    .categories-console .category-create-trigger,
    .categories-console .category-form-grid label,
    .categories-console .category-owner-panel h3,
    .categories-console .category-lock-panel h3,
    .categories-console .category-empty-panel h3,
    .categories-console .category-row-title,
    .categories-console .category-row-count,
    .categories-console .category-row-mark,
    .categories-console .category-store-card h3,
    .categories-console .category-store-pill,
    .categories-console .category-empty-icon {
        color: var(--vendly-brand);
    }

    .categories-console .category-main-action {
        border-color: var(--vendly-brand);
        background: var(--vendly-brand);
        color: var(--vendly-brand-contrast);
        box-shadow: 0 12px 24px var(--vendly-brand-shadow);
    }

    .categories-console .category-main-action:hover {
        background: var(--vendly-brand);
        box-shadow: 0 16px 30px var(--vendly-brand-shadow);
    }

    .categories-console .category-row {
        border-color: var(--vendly-brand-tint);
    }

    .categories-console .category-row-action:hover {
        color: var(--vendly-brand);
    }
</style>

<div class="categories-console">
    <div class="categories-console-head">
        <nav class="catalog-tabs" aria-label="Gestion de catálogo">
            <a href="{{ $productsUrl }}" class="catalog-tab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 16-9 5-9-5"></path><path d="m21 12-9 5-9-5"></path><path d="m12 3 9 5-9 5-9-5 9-5Z"></path></svg>
                Productos
            </a>
            <a href="{{ $categoriesUrl }}" class="catalog-tab is-active">
                <svg viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="4" width="6" height="6" rx="1"></rect><rect x="14" y="4" width="6" height="6" rx="1"></rect><rect x="4" y="14" width="6" height="6" rx="1"></rect><rect x="14" y="14" width="6" height="6" rx="1"></rect></svg>
                Categorías
            </a>
        </nav>

        @if(! auth()->user()->isAdmin() || ! empty($selectedStore))
            @if(empty($categoriesLocked))
                <a href="#nueva-categoría" class="category-main-action" data-category-create-link>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                    Nueva Categoría
                </a>
            @endif
        @endif
    </div>

    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(auth()->user()->isAdmin() && empty($selectedStore))
        <div class="category-admin-store-grid">
            @foreach(($stores ?? collect()) as $storeOption)
                <article class="category-store-card">
                    <div>
                        <h3>{{ $storeOption->name }}</h3>
                        <p>Gestiona las categorías de esta tienda.</p>
                    </div>
                    <div class="category-store-meta">
                        <span class="category-store-pill">Plan {{ $storeOption->planLabel() }}</span>
                        <span class="category-store-pill">{{ $storeOption->categories_count }} categoría(s)</span>
                    </div>
                    <a href="{{ route('admin.stores.categories.index', $storeOption) }}" class="btn">
                        {{ $storeOption->allowsCategories() ? 'Ver categorías' : 'Ver límite' }}
                    </a>
                </article>
            @endforeach
        </div>

        @if(($stores ?? null) && method_exists($stores, 'hasPages') && $stores->hasPages())
            <div class="list-card admin-pagination">
                {{ $stores->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        @endif
    @else
        @if(auth()->user()->isAdmin() && ! empty($selectedStore))
            <div class="category-owner-panel">
                <div>
                    <h3>{{ $selectedStore->name }}</h3>
                    <p>Categorías de esta tienda</p>
                </div>
                <a href="/admin/categories" class="btn btn-secondary">Volver a tiendas</a>
            </div>
        @endif

        @if(! empty($categoriesLocked))
            <div class="category-lock-panel">
                <div>
                    <h3>Categorías no disponibles</h3>
                    <p>El plan {{ $store->planLabel() }} no incluye categorías. Los productos se muestran como un catálogo simple.</p>
                </div>
            </div>
        @else
            <details class="category-create-card" id="nueva-categoría" @if($errors->any()) open @endif>
                <summary class="category-create-trigger">
                    <span>
                        Crear categoría
                        <small>Agrega nombre, imagen y posición en la tienda.</small>
                    </span>
                    <span aria-hidden="true">+</span>
                </summary>

                <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="category-create-body">
                    @csrf
                    @if(auth()->user()->isAdmin())
                        <input type="hidden" name="store_id" value="{{ $store->id }}">
                    @endif

                    <div class="category-form-grid">
                        <label>
                            Nombre de la categoría
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Ej: Camisetas" required>
                        </label>

                        <label>
                            Slug opcional
                            <input type="text" name="slug" value="{{ old('slug') }}" placeholder="camisetas">
                        </label>

                        @if($store->allowsSubcategories())
                            <label>
                                Categoría principal
                                <select name="parent_id">
                                    <option value="">Sin categoría principal</option>
                                    @foreach(($parentCategoryOptions ?? collect()) as $parentCategory)
                                        <option value="{{ $parentCategory->id }}" @selected((int) old('parent_id') === (int) $parentCategory->id)>
                                            {{ $parentCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="category-form-help">Úsalo para crear subcategorías como Ropa / Camisetas.</span>
                            </label>
                        @endif

                        <label class="category-field-wide">
                            Descripción corta
                            <textarea name="description" rows="3" placeholder="Texto breve para mostrar en la tienda">{{ old('description') }}</textarea>
                        </label>

                        <label>
                            Imagen
                            <input type="file" name="image" accept="image/*" data-optimize-image data-max-width="1600" data-max-height="1200" data-quality="0.84" data-output="webp" data-max-size="8388608">
                            <span class="category-form-help">JPG, PNG o WebP. Máximo 8 MB.</span>
                        </label>

                        <label>
                            Posicion en la tienda
                            <select name="sort_order">
                                @foreach([
                                    0 => 'Normal',
                                    10 => 'Primero',
                                    20 => 'Segundo',
                                    30 => 'Tercero',
                                    40 => 'Cuarto',
                                    50 => 'Quinto',
                                    100 => 'Al final',
                                ] as $orderValue => $orderLabel)
                                    <option value="{{ $orderValue }}" @selected((int) old('sort_order', 0) === $orderValue)>{{ $orderLabel }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="category-inline-check category-field-wide">
                            <input type="checkbox" name="is_active" value="1" checked>
                            Categoría visible
                        </label>
                    </div>

                    <div>
                        <button class="btn" type="submit">Agregar categoría</button>
                    </div>
                </form>
            </details>

            @if ($categories->isEmpty())
                <div class="category-empty-panel">
                    <div>
                        <span class="category-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg>
                        </span>
                        <h3>No hay categorías registradas</h3>
                        <p>Crea categorías para ordenar el catálogo y facilitar la exploración de productos.</p>
                    </div>
                </div>
            @else
                <div class="category-list">
                    @foreach ($categories as $category)
                        @php
                            $categoryInitial = mb_strtoupper(mb_substr($category->name, 0, 1));
                            $categoryProductsCount = (int) ($categoryProductCounts[$category->name] ?? 0);
                        @endphp

                        <article class="category-row {{ $category->parent_id ? 'category-row--child' : '' }}">
                            <span class="category-row-order" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 15-6 6-6-6"></path></svg>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 9-6-6-6 6"></path></svg>
                            </span>

                            <span class="category-row-mark">
                                @if($category->image)
                                    <img src="{{ asset('storage/' . $category->image) }}" alt="">
                                @else
                                    {{ $categoryInitial }}
                                @endif
                            </span>

                            <div class="category-row-content">
                                <h3 class="category-row-title">{{ $category->name }}</h3>
                                @if($category->parent)
                                    <span class="category-row-parent">Subcategoría de {{ $category->parent->name }}</span>
                                @endif
                                <span class="category-row-count">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 16-9 5-9-5"></path><path d="m12 3 9 5-9 5-9-5 9-5Z"></path></svg>
                                    {{ $categoryProductsCount }} {{ $categoryProductsCount === 1 ? 'producto' : 'productos' }}
                                </span>
                            </div>

                            <div class="category-row-actions">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="category-row-action" aria-label="Editar {{ $category->name }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"></path></svg>
                                </a>

                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm-delete data-confirm-message="Eliminar esta categoría? Los productos quedarán sin categoría.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="category-row-action category-row-action--danger" aria-label="Eliminar {{ $category->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    @endif
</div>

<script>
    document.querySelector('[data-category-create-link]')?.addEventListener('click', function () {
        const panel = document.getElementById('nueva-categoría');

        if (panel) {
            panel.open = true;
        }
    });
</script>
@endsection
