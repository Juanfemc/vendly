<section class="catalog-section category-showcase" id="catalogo" data-default-catalog>
    @php
        $renderedDefaultProductIds = collect();
        $hasDefaultProducts = false;
        $defaultFilterProducts = collect($visibleCategorySections ?? [])
            ->flatMap(fn ($section) => collect($section['products'] ?? []))
            ->merge(collect($otherProducts ?? []))
            ->unique('id')
            ->values();
        $defaultSizeOptions = $defaultFilterProducts
            ->flatMap(fn ($product) => collect(is_array($product->sizes) ? $product->sizes : []))
            ->map(function ($size) {
                $label = trim((string) $size);

                return [
                    'label' => $label,
                    'slug' => \Illuminate\Support\Str::slug($label),
                ];
            })
            ->filter(fn ($size) => $size['label'] !== '' && $size['slug'] !== '')
            ->unique('slug')
            ->values();
    @endphp

    @if($visibleCategorySections->isNotEmpty() || $otherProducts->isNotEmpty())
        <section class="default-filter-panel" aria-label="Filtrar productos" data-default-filter-panel>
            <div class="default-filter-panel-head">
                <span class="default-filter-panel-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 7h16M7 12h10M10 17h4"/><circle cx="8" cy="7" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="11" cy="17" r="2"/></svg>
                </span>
                <strong>Filtrar productos</strong>
            </div>

            <div class="default-filter-groups">
                <div class="default-filter-group" aria-label="Disponibilidad">
                    <span class="default-filter-label">Disponibilidad</span>
                    <div role="group" aria-label="Filtrar por disponibilidad">
                        <button type="button" class="default-filter-pill is-active" data-default-availability-filter="all" aria-pressed="true">Todos</button>
                        <button type="button" class="default-filter-pill" data-default-availability-filter="available" aria-pressed="false">Disponibles</button>
                        <button type="button" class="default-filter-pill" data-default-availability-filter="soldout" aria-pressed="false">Agotados</button>
                    </div>
                </div>

                <div class="default-filter-group" aria-label="Ofertas">
                    <span class="default-filter-label">Ofertas</span>
                    <div role="group" aria-label="Filtrar por ofertas">
                        <button type="button" class="default-filter-pill is-active" data-default-offer-filter="all" aria-pressed="true">Todo</button>
                        <button type="button" class="default-filter-pill" data-default-offer-filter="offer" aria-pressed="false">En oferta</button>
                    </div>
                </div>

                @if($defaultSizeOptions->isNotEmpty())
                    <div class="default-filter-group" aria-label="Tallas">
                        <span class="default-filter-label">Tallas</span>
                        <div role="group" aria-label="Filtrar por tallas">
                            <button type="button" class="default-filter-pill is-active" data-default-size-filter="all" aria-pressed="true">Todas</button>
                            @foreach($defaultSizeOptions as $sizeOption)
                                <button type="button" class="default-filter-pill" data-default-size-filter="{{ $sizeOption['slug'] }}" aria-pressed="false">{{ $sizeOption['label'] }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <label class="default-filter-sort">
                    <span class="default-filter-label">Orden</span>
                    <select data-default-sort aria-label="Ordenar productos">
                        <option value="default">Destacados</option>
                        <option value="price-asc">Menor precio</option>
                        <option value="price-desc">Mayor precio</option>
                        <option value="name-asc">Nombre A-Z</option>
                    </select>
                </label>
            </div>
        </section>

        <div class="products-grid" data-default-category-grid>
            @foreach($visibleCategorySections as $section)
                @php
                    $sectionCategory = $section['category'];
                @endphp
                @foreach($section['products'] as $product)
                    @if(! $renderedDefaultProductIds->contains($product->id))
                        @php
                            $defaultProductIndex = $renderedDefaultProductIds->count();
                            $renderedDefaultProductIds->push($product->id);
                            $hasDefaultProducts = true;
                            $defaultCategoryProduct = $sectionCategory->slug;
                            $defaultProductCategoryLabel = $sectionCategory->name;
                        @endphp
                        @include('storefront.partials.product-card')
                    @endif
                @endforeach
            @endforeach

            @foreach($otherProducts as $product)
                @if(! $renderedDefaultProductIds->contains($product->id))
                    @php
                        $defaultProductIndex = $renderedDefaultProductIds->count();
                        $renderedDefaultProductIds->push($product->id);
                        $hasDefaultProducts = true;
                        $defaultCategoryProduct = '__other';
                        $defaultProductCategoryLabel = 'Otros';
                    @endphp
                    @include('storefront.partials.product-card')
                @endif
            @endforeach
        </div>
    @endif

    @unless($hasDefaultProducts)
        <div class="empty-state">Aún no hay {{ $itemsLabel }} publicados.</div>
    @endunless

    <div class="empty-state default-category-empty" data-default-category-empty hidden>
        No hay productos para los filtros seleccionados.
    </div>
</section>
