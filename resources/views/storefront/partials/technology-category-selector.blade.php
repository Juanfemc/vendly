@if($techCategoryItems->isNotEmpty())
    <section class="tech-category-section" aria-label="Comprar por categoría">
        <nav class="tech-category-grid" aria-label="Categorías" data-tech-category-grid>
            @foreach($techCategoryItems as $categoryItem)
                <button
                    type="button"
                    data-minimal-category-link
                    data-minimal-category-url="{{ $categoryItem['url'] }}"
                    aria-pressed="{{ $categoryItem['active'] ? 'true' : 'false' }}"
                    @class(['tech-category-card', 'is-active' => $categoryItem['active']])
                >
                    <span class="tech-category-visual">{!! $categoryItem['icon'] !!}</span>
                    <span class="tech-category-copy">
                        <strong>{{ $categoryItem['name'] }}</strong>
                        <small>{{ $categoryItem['count'] }} {{ $categoryItem['count'] === 1 ? 'producto' : 'productos' }}</small>
                    </span>
                </button>
            @endforeach
            <button type="button" class="tech-category-toggle" data-tech-category-toggle aria-expanded="false" hidden>Ver todas</button>
        </nav>
    </section>
@endif
