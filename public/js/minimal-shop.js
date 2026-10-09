document.addEventListener('DOMContentLoaded', () => {
    const showFallback = (image) => {
        const fallback = image.nextElementSibling;
        const isActive = image.classList.contains('is-active');

        image.hidden = true;
        image.classList.remove('is-active');

        if (fallback && fallback.classList.contains('minimal-shop-card-placeholder')) {
            fallback.hidden = false;
        }

        if (fallback && fallback.classList.contains('minimal-shop-hero-fallback')) {
            fallback.hidden = false;
        }

        if (fallback && fallback.classList.contains('minimal-product-placeholder')) {
            fallback.hidden = false;
            fallback.classList.toggle('is-active', isActive);
        }
    };

    const bindImageFallbacks = (root = document) => {
        root.querySelectorAll('.minimal-shop-hero-image, .minimal-shop-card-image, .minimal-product-image').forEach((image) => {
            if (image.dataset.fallbackBound === 'true') {
                return;
            }

            image.dataset.fallbackBound = 'true';
            image.addEventListener('error', () => showFallback(image), { once: true });

            if (image.complete && image.naturalWidth === 0) {
                showFallback(image);
            }
        });
    };

    window.vendlyBindMinimalImageFallbacks = bindImageFallbacks;

    const page = document.querySelector('.storefront-page--minimal-grid');
    const catalogSection = document.getElementById('catalogo');
    const mobileMenuToggle = document.getElementById('minimalShopMenuToggle');
    const techCategoryGrid = document.querySelector('[data-tech-category-grid]');
    const techCategoryToggle = document.querySelector('[data-tech-category-toggle]');
    const techFilterState = {
        availability: 'all',
        offer: 'all',
        price: 'all',
        sort: 'default',
    };
    const techFilterCount = document.querySelector('[data-tech-filter-count]');
    const techFilterClear = document.querySelector('[data-tech-filter-clear]');

    const hrefForFilterControl = (control) => control?.dataset?.minimalCategoryUrl || control?.href || '';

    const withPartialParam = (href) => {
        const url = new URL(href, window.location.href);

        url.searchParams.set('partial', 'catalogo');
        url.hash = '';

        return url;
    };

    const cleanHistoryUrl = (href) => {
        const url = new URL(href, window.location.href);

        url.searchParams.delete('partial');

        return `${url.pathname}${url.search}${url.hash}`;
    };

    const syncActiveFilters = (href) => {
        const current = new URL(href, window.location.href);
        const selectedCategory = current.searchParams.get('categoria') || '';
        const selectedBadge = current.searchParams.get('etiqueta') || '';

        document.querySelectorAll('[data-minimal-category-link]').forEach((link) => {
            const linkUrl = new URL(hrefForFilterControl(link), window.location.href);
            const linkCategory = linkUrl.searchParams.get('categoria') || '';
            const isActive = !selectedBadge && linkCategory === selectedCategory;

            link.classList.toggle('is-active', isActive);
            link.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        document.querySelectorAll('[data-minimal-badge-link]').forEach((link) => {
            const linkUrl = new URL(link.href, window.location.href);
            link.classList.toggle('is-active', (linkUrl.searchParams.get('etiqueta') || '') === selectedBadge);
        });
    };

    const closeMobileMenu = () => {
        if (mobileMenuToggle) {
            mobileMenuToggle.checked = false;
        }
    };

    const syncTechCategoryToggle = () => {
        if (!techCategoryGrid || !techCategoryToggle) {
            return;
        }

        const isExpanded = techCategoryGrid.classList.contains('is-expanded');
        const categoryCards = Array.from(techCategoryGrid.querySelectorAll('.tech-category-card'));
        const isTouchRow = window.matchMedia('(max-width: 900px)').matches;

        categoryCards.forEach((card) => {
            card.hidden = false;
        });

        if (isExpanded) {
            techCategoryToggle.hidden = false;
            techCategoryToggle.textContent = 'Ver menos';
            techCategoryToggle.setAttribute('aria-expanded', 'true');
            return;
        }

        techCategoryToggle.hidden = true;
        techCategoryToggle.textContent = 'Ver todas';
        techCategoryToggle.setAttribute('aria-expanded', 'false');

        window.requestAnimationFrame(() => {
            const hasOverflow = techCategoryGrid.scrollWidth > techCategoryGrid.clientWidth + 2;

            techCategoryToggle.hidden = !hasOverflow;

            if (!hasOverflow || isTouchRow) {
                return;
            }

            const visibleCards = [];
            const activeCard = categoryCards.find((card) => card.classList.contains('is-active'));
            const availableWidth = Math.max(0, techCategoryGrid.clientWidth - techCategoryToggle.offsetWidth - 16);
            const gap = Number.parseFloat(window.getComputedStyle(techCategoryGrid).columnGap || '10') || 10;
            let usedWidth = 0;

            categoryCards.forEach((card) => {
                const cardWidth = card.getBoundingClientRect().width;
                const nextWidth = usedWidth + (visibleCards.length > 0 ? gap : 0) + cardWidth;

                if (nextWidth <= availableWidth || visibleCards.length === 0) {
                    visibleCards.push(card);
                    usedWidth = nextWidth;
                }
            });

            if (activeCard && !visibleCards.includes(activeCard) && visibleCards.length > 1) {
                visibleCards[visibleCards.length - 1] = activeCard;
            }

            categoryCards.forEach((card) => {
                card.hidden = !visibleCards.includes(card);
            });
        });
    };

    const techPriceMatches = (price, range) => {
        if (range === 'under-50000') {
            return price <= 50000;
        }

        if (range === '50000-150000') {
            return price >= 50000 && price <= 150000;
        }

        if (range === 'over-150000') {
            return price > 150000;
        }

        return true;
    };

    const isTechFilterActive = () => (
        techFilterState.availability !== 'all'
        || techFilterState.offer !== 'all'
        || techFilterState.price !== 'all'
        || techFilterState.sort !== 'default'
    );

    const syncTechFilterControls = () => {
        document.querySelectorAll('[data-tech-filter]').forEach((button) => {
            const key = button.dataset.techFilter;
            const isActive = techFilterState[key] === button.dataset.value;

            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        document.querySelectorAll('[data-tech-filter-select]').forEach((select) => {
            const key = select.dataset.techFilterSelect;
            select.value = techFilterState[key] || 'all';
        });

        if (techFilterClear) {
            techFilterClear.hidden = !isTechFilterActive();
        }
    };

    const applyTechProductFilters = () => {
        const shell = document.querySelector('[data-minimal-catalog-shell]');

        if (!shell) {
            return;
        }

        const grid = shell.querySelector('.minimal-shop-product-grid');
        const cards = Array.from(shell.querySelectorAll('[data-tech-product-card]'));
        const pagination = shell.querySelector('.minimal-shop-pagination');
        const endMessage = shell.querySelector('.minimal-shop-end-message');

        if (!grid || cards.length === 0) {
            if (techFilterCount) {
                techFilterCount.textContent = '0';
            }
            return;
        }

        let emptyState = shell.querySelector('[data-tech-filter-empty]');

        if (!emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'minimal-shop-empty-state tech-filter-empty';
            emptyState.dataset.techFilterEmpty = 'true';
            emptyState.textContent = 'No encontramos productos con esos filtros.';
            emptyState.hidden = true;
            grid.after(emptyState);
        }

        cards.forEach((card, index) => {
            if (!card.dataset.techOriginalIndex) {
                card.dataset.techOriginalIndex = String(index);
            }
        });

        const sortedCards = [...cards].sort((first, second) => {
            const firstPrice = Number.parseFloat(first.dataset.techProductPrice || '0');
            const secondPrice = Number.parseFloat(second.dataset.techProductPrice || '0');
            const firstName = first.dataset.techProductName || '';
            const secondName = second.dataset.techProductName || '';

            if (techFilterState.sort === 'price-asc') {
                return firstPrice - secondPrice;
            }

            if (techFilterState.sort === 'price-desc') {
                return secondPrice - firstPrice;
            }

            if (techFilterState.sort === 'name-asc') {
                return firstName.localeCompare(secondName, 'es');
            }

            return Number.parseInt(first.dataset.techOriginalIndex || '0', 10)
                - Number.parseInt(second.dataset.techOriginalIndex || '0', 10);
        });

        sortedCards.forEach((card) => grid.appendChild(card));

        let visibleCount = 0;

        sortedCards.forEach((card) => {
            const price = Number.parseFloat(card.dataset.techProductPrice || '0');
            const available = card.dataset.techProductAvailable === '1';
            const offer = card.dataset.techProductOffer === '1';
            const availabilityMatches = techFilterState.availability === 'all'
                || (techFilterState.availability === 'available' && available)
                || (techFilterState.availability === 'soldout' && !available);
            const offerMatches = techFilterState.offer === 'all'
                || (techFilterState.offer === 'offers' && offer);
            const priceMatches = techPriceMatches(price, techFilterState.price);
            const isVisible = availabilityMatches && offerMatches && priceMatches;

            card.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        emptyState.hidden = visibleCount > 0;

        if (techFilterCount) {
            techFilterCount.textContent = String(visibleCount);
        }

        if (pagination) {
            pagination.hidden = isTechFilterActive();
        }

        if (endMessage) {
            endMessage.hidden = isTechFilterActive() && visibleCount === 0;
        }

        syncTechFilterControls();
    };

    const resetTechFilters = () => {
        techFilterState.availability = 'all';
        techFilterState.offer = 'all';
        techFilterState.price = 'all';
        techFilterState.sort = 'default';
        applyTechProductFilters();
    };

    const scrollToCatalog = () => {
        if (!catalogSection) {
            return;
        }

        const navHeight = document.querySelector('.minimal-shop-nav')?.offsetHeight || 0;
        const targetTop = catalogSection.getBoundingClientRect().top + window.scrollY - navHeight - 14;

        window.scrollTo({
            top: Math.max(0, targetTop),
            behavior: 'smooth',
        });
    };

    const replaceCatalog = async (href, shouldPushState = true) => {
        const currentShell = document.querySelector('[data-minimal-catalog-shell]');

        if (!currentShell) {
            window.location.href = href;
            return;
        }

        currentShell.classList.add('is-loading');

        const response = await fetch(withPartialParam(href), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        });

        if (!response.ok) {
            throw new Error('No se pudo cargar el catalogo.');
        }

        const html = await response.text();
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        const nextShell = template.content.querySelector('[data-minimal-catalog-shell]');

        if (!nextShell) {
            throw new Error('Respuesta de catalogo invalida.');
        }

        currentShell.replaceWith(nextShell);
        bindImageFallbacks(nextShell);
        if (typeof window.vendlyInitializeInfiniteProducts === 'function') {
            window.vendlyInitializeInfiniteProducts(nextShell);
        }
        syncActiveFilters(href);
        applyTechProductFilters();

        if (shouldPushState) {
            window.history.pushState({ minimalCatalogUrl: href }, '', cleanHistoryUrl(href));
        }

        scrollToCatalog();
    };

    document.addEventListener('click', async (event) => {
        if (!page || !catalogSection) {
            return;
        }

        const toggle = event.target.closest('[data-tech-category-toggle]');

        if (toggle && techCategoryGrid) {
            const isExpanded = techCategoryGrid.classList.toggle('is-expanded');

            toggle.textContent = isExpanded ? 'Ver menos' : 'Ver todas';
            toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            syncTechCategoryToggle();
            return;
        }

        const filterButton = event.target.closest('[data-tech-filter]');

        if (filterButton) {
            techFilterState[filterButton.dataset.techFilter] = filterButton.dataset.value || 'all';
            applyTechProductFilters();
            return;
        }

        if (event.target.closest('[data-tech-filter-clear]')) {
            resetTechFilters();
            return;
        }

        const link = event.target.closest('[data-minimal-category-link], [data-minimal-badge-link], [data-minimal-catalog-shell] .minimal-shop-pagination a');

        if (!link) {
            return;
        }

        event.preventDefault();

        if (link.closest('.minimal-shop-mobile-menu')) {
            closeMobileMenu();
        }

        try {
            await replaceCatalog(hrefForFilterControl(link));
        } catch (error) {
            window.location.href = hrefForFilterControl(link);
        }
    });

    document.addEventListener('change', (event) => {
        const select = event.target.closest('[data-tech-filter-select]');

        if (!select) {
            return;
        }

        techFilterState[select.dataset.techFilterSelect] = select.value;
        applyTechProductFilters();
    });

    window.addEventListener('popstate', async () => {
        if (!page || !catalogSection) {
            return;
        }

        try {
            await replaceCatalog(window.location.href, false);
        } catch (error) {
            window.location.reload();
        }
    });

    bindImageFallbacks();
    applyTechProductFilters();
    syncTechCategoryToggle();
    window.addEventListener('resize', syncTechCategoryToggle);
});
