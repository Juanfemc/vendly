<section class="tech-filter-panel" aria-label="Filtros de productos">
    <div class="tech-filter-panel-head">
        <span>{!! $icons::icon('settings') !!}</span>
        <strong>Filtrar productos</strong>
    </div>

    <div class="tech-filter-controls">
        <div class="tech-filter-group" role="group" aria-label="Disponibilidad">
            <button type="button" class="is-active" data-tech-filter="availability" data-value="all" aria-pressed="true">Todos</button>
            <button type="button" data-tech-filter="availability" data-value="available" aria-pressed="false">Disponibles</button>
            <button type="button" data-tech-filter="availability" data-value="soldout" aria-pressed="false">Agotados</button>
        </div>

        <div class="tech-filter-group" role="group" aria-label="Ofertas">
            <button type="button" class="is-active" data-tech-filter="offer" data-value="all" aria-pressed="true">Todo</button>
            <button type="button" data-tech-filter="offer" data-value="offers" aria-pressed="false">En oferta</button>
        </div>

        <label class="tech-filter-select">
            <span>Precio</span>
            <select data-tech-filter-select="price">
                <option value="all">Todos los precios</option>
                <option value="under-50000">Hasta $50.000</option>
                <option value="50000-150000">$50.000 - $150.000</option>
                <option value="over-150000">Más de $150.000</option>
            </select>
        </label>

        <label class="tech-filter-select">
            <span>Orden</span>
            <select data-tech-filter-select="sort">
                <option value="default">Destacados</option>
                <option value="price-asc">Menor precio</option>
                <option value="price-desc">Mayor precio</option>
                <option value="name-asc">Nombre A-Z</option>
            </select>
        </label>

        <button type="button" class="tech-filter-clear" data-tech-filter-clear hidden>Limpiar filtros</button>
    </div>
</section>
