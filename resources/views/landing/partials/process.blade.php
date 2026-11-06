<section class="landing-section catalog-section" id="catalogo">
    <div class="landing-shell">
        <div class="catalog-showcase">
            <div class="catalog-showcase-copy">
                <span class="section-kicker">Tu catálogo</span>
                <h2>Tu catálogo, <strong>como lo imaginas.</strong></h2>
                <p>Muestra tus productos, organiza tu tienda y recibe pedidos por WhatsApp desde un solo lugar.</p>
                <a href="#portafolio" class="btn btn--ghost">Ver tiendas reales</a>
            </div>

            <div class="catalog-showcase-products" aria-label="Ejemplos de tiendas creadas con Vendly">
                @forelse($portfolioStores->take(4) as $portfolioStore)
                    @php($catalogImage = $portfolioStore->cover_image ?: $portfolioStore->logo_image)
                    <a
                        href="{{ app(\App\Services\StorefrontUrlService::class)->publicHome($portfolioStore) }}"
                        class="catalog-product"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span class="catalog-product-media">
                            <img
                                src="{{ $catalogImage ? asset('storage/' . $catalogImage) : asset('images/landing-demo-poster.jpg') }}"
                                alt="Vista de {{ $portfolioStore->name }}"
                                loading="lazy"
                                decoding="async"
                            >
                        </span>
                        <span class="catalog-product-name">{{ $portfolioStore->name }}</span>
                        <span class="catalog-product-type">{{ $portfolioStore->businessTypeLabel() }}</span>
                    </a>
                @empty
                    <div class="catalog-empty-state">
                        <img src="{{ asset('images/landing-demo-poster.jpg') }}" alt="Vista previa de un catálogo Vendly" loading="lazy" decoding="async">
                        <div>
                            <strong>Una tienda pensada para mostrar tus productos.</strong>
                            <span>Los ejemplos públicos aparecerán aquí.</span>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="process-intro">
            <div class="section-head section-head--center">
                <h2>Cómo funciona</h2>
                <p>Empieza con lo esencial y configura tu tienda paso a paso.</p>
            </div>

            <div class="steps">
                @foreach($steps as $step)
                    <article class="step-card">
                        <span>{{ $step['number'] }}</span>
                        <strong>{{ $step['title'] }}</strong>
                        <p>{{ $step['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
