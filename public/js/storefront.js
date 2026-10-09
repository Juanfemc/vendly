(function () {
    const page = document.querySelector('.storefront-page');

    if (!page) {
        return;
    }

    const resolveBrandContrast = () => {
        const brandColor = getComputedStyle(page).getPropertyValue('--brand-color').trim();

        if (!brandColor) {
            return;
        }

        document.documentElement.style.setProperty('--brand-color', brandColor);
        document.documentElement.style.scrollbarColor = `${brandColor} #eef3f8`;
        page.style.scrollbarColor = `${brandColor} #eef3f8`;

        const probe = document.createElement('span');
        probe.style.color = brandColor;
        probe.style.display = 'none';
        document.body.appendChild(probe);

        const computedColor = getComputedStyle(probe).color;
        document.body.removeChild(probe);

        const match = computedColor.match(/\d+/g);

        if (!match || match.length < 3) {
            return;
        }

        const [red, green, blue] = match.slice(0, 3).map(Number);
        const luminance = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;
        const contrast = luminance < 0.55 ? '#ffffff' : '#111111';

        page.style.setProperty('--brand-contrast', contrast);
    };

    const cartLink = document.querySelector('.cart-link');
    const feedback = document.getElementById('cartFeedback');
    const cartDrawer = document.querySelector('[data-cart-drawer]');
    const cartDrawerToggle = document.getElementById('minimalShopCartToggle');
    const minimalMenuToggle = document.querySelector('.minimal-shop-menu-state');
    const minimalSearchToggle = document.querySelector('.minimal-shop-search-state');
    const storeCartBackdrop = document.querySelector('.store-cart-backdrop, .fashion-cart-backdrop, .minimal-shop-cart-backdrop');
    const cartDrawerItems = document.querySelector('[data-cart-drawer-items]');
    const cartDrawerCount = document.querySelector('[data-cart-drawer-count]');
    const cartDrawerSubtotal = document.querySelector('[data-cart-drawer-subtotal]');
    const cartDrawerShipping = document.querySelector('[data-cart-drawer-shipping]');
    const cartDrawerTotal = document.querySelector('[data-cart-drawer-total]');
    const navToggle = document.querySelector('.nav-toggle');
    const navbar = document.querySelector('.navbar');
    const navClose = document.querySelector('.nav-close');
    const navBackdrop = document.querySelector('.nav-backdrop');
    const navPanelLinks = document.querySelectorAll('.nav-panel a');
    const navDropdowns = document.querySelectorAll('.nav-dropdown');
    const fashionCategoryButtons = Array.from(document.querySelectorAll('[data-fashion-category-filter]'));
    let fashionProducts = Array.from(document.querySelectorAll('[data-fashion-product]'));
    const fashionEmptyState = document.querySelector('[data-fashion-empty-state]');
    const fashionEndMessage = document.querySelector('[data-fashion-end-message]');
    const storefrontSearchForms = Array.from(document.querySelectorAll('[data-storefront-search], [data-fashion-search]'));
    const fashionProductGrid = document.querySelector('[data-fashion-product-grid]');
    const fashionSizeButtons = Array.from(document.querySelectorAll('[data-fashion-size-option]'));
    const fashionSortSelect = document.querySelector('[data-fashion-sort]');
    const defaultCategoryButtons = Array.from(document.querySelectorAll('[data-default-category-filter]'));
    const defaultCategorySections = Array.from(document.querySelectorAll('[data-default-category-section]'));
    let defaultCategoryProducts = Array.from(document.querySelectorAll('[data-default-category-product]'));
    const defaultCategoryEmptyState = document.querySelector('[data-default-category-empty]');
    const defaultCategoryCount = document.querySelector('.home-categories-head [data-default-category-count]');
    const defaultCategoryTrack = document.querySelector('[data-default-category-tabs]');
    const defaultCategoryScrollButtons = Array.from(document.querySelectorAll('[data-home-category-scroll]'));
    const defaultProductGrid = document.querySelector('[data-default-category-grid]');
    const defaultAvailabilityButtons = Array.from(document.querySelectorAll('[data-default-availability-filter]'));
    const defaultOfferButtons = Array.from(document.querySelectorAll('[data-default-offer-filter]'));
    const defaultSizeButtons = Array.from(document.querySelectorAll('[data-default-size-filter]'));
    const defaultSortSelect = document.querySelector('[data-default-sort]');
    const announcementMessages = Array.from(document.querySelectorAll('[data-announcement-message]'));
    const storefrontTopbar = document.querySelector('[data-storefront-topbar]');
    const fashionAnnouncement = document.querySelector('[data-fashion-announcement]');
    const fashionCompactBlocks = Array.from(document.querySelectorAll('[data-fashion-collapsible]'));
    const csrfToken = page.dataset.csrf || '';
    const addingText = page.dataset.addingText || 'Agregando...';
    const addedText = page.dataset.feedbackAdded || 'Producto agregado al carrito';
    const addErrorText = page.dataset.feedbackError || 'No pudimos agregar el producto';
    const compactFashionCartDelay = 1000;
    let feedbackTimer;
    const initialFashionCategory = document.querySelector('[data-fashion-category-filter].is-active')?.dataset.fashionCategoryFilter || 'all';
    let activeFashionCategory = initialFashionCategory;
    let fashionCatalogVersion = 0;
    let fashionCatalogIsServerFiltered = initialFashionCategory !== 'all';
    let fashionCategoryRequestController = null;
    let activeFashionSize = 'all';
    let activeDefaultCategory = document.querySelector('[data-default-category-filter].is-active')?.dataset.defaultCategoryFilter || 'all';
    let activeDefaultAvailability = 'all';
    let activeDefaultOffer = 'all';
    let activeDefaultSize = 'all';
    let activeDefaultSort = 'default';
    let defaultCatalogVersion = 0;
    let defaultCatalogIsServerFiltered = activeDefaultCategory !== 'all';
    let defaultCategoryRequestController = null;
    let defaultInfiniteRequestController = null;
    let lockedScrollY = 0;
    let isScrollLocked = false;

    resolveBrandContrast();

    const infiniteUrl = (href) => {
        const url = new URL(href, window.location.href);
        url.searchParams.set('infinite', '1');
        return url.toString();
    };

    const minimalOverlayToggles = [minimalMenuToggle, cartDrawerToggle, minimalSearchToggle].filter(Boolean);
    let activeFilterDrawerName = null;

    const filterDrawerSelector = (name) => `[data-filter-drawer="${window.CSS?.escape ? CSS.escape(name) : name}"]`;
    const filterDrawerBackdropSelector = (name) => `[data-filter-drawer-close="${window.CSS?.escape ? CSS.escape(name) : name}"]`;

    const setFilterDrawerOpen = (name, isOpen) => {
        const drawer = document.querySelector(filterDrawerSelector(name));
        const backdrop = Array.from(document.querySelectorAll(filterDrawerBackdropSelector(name)))
            .find((element) => element.classList.contains(`${name === 'technology' ? 'tech' : name}-filter-backdrop`) || element.classList.contains('default-filter-backdrop'));
        const triggers = Array.from(document.querySelectorAll(`[data-filter-drawer-open="${name}"]`));

        if (!drawer) {
            return;
        }

        if (isOpen) {
            if (activeFilterDrawerName && activeFilterDrawerName !== name) {
                setFilterDrawerOpen(activeFilterDrawerName, false);
            }

            drawer.hidden = false;
            if (backdrop) {
                backdrop.hidden = false;
            }

            window.requestAnimationFrame(() => {
                drawer.classList.add('is-open');
                backdrop?.classList.add('is-open');
            });

            activeFilterDrawerName = name;
            document.documentElement.classList.add('storefront-filter-drawer-open');
            document.body.classList.add('storefront-filter-drawer-open');
            triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'true'));
            drawer.querySelector('button, select, input, a')?.focus({ preventScroll: true });
            return;
        }

        drawer.classList.remove('is-open');
        backdrop?.classList.remove('is-open');
        triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));

        window.setTimeout(() => {
            if (!drawer.classList.contains('is-open')) {
                drawer.hidden = true;
            }

            if (backdrop && !backdrop.classList.contains('is-open')) {
                backdrop.hidden = true;
            }
        }, 220);

        if (activeFilterDrawerName === name) {
            activeFilterDrawerName = null;
            document.documentElement.classList.remove('storefront-filter-drawer-open');
            document.body.classList.remove('storefront-filter-drawer-open');
        }
    };

    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-filter-drawer-open]');

        if (openButton) {
            event.preventDefault();
            setFilterDrawerOpen(openButton.dataset.filterDrawerOpen, true);
            return;
        }

        const closeButton = event.target.closest('[data-filter-drawer-close]');

        if (closeButton) {
            event.preventDefault();
            setFilterDrawerOpen(closeButton.dataset.filterDrawerClose, false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeFilterDrawerName) {
            setFilterDrawerOpen(activeFilterDrawerName, false);
        }
    });

    const syncMinimalOverlayScrollLock = () => {
        if (!page.classList.contains('storefront-page--minimal-grid')) {
            return;
        }

        const hasOpenOverlay = minimalOverlayToggles.some((toggle) => toggle.checked);
        const hasOpenMenu = Boolean(minimalMenuToggle?.checked);

        document.documentElement.classList.toggle('storefront-minimal-overlay-open', hasOpenOverlay);
        document.body.classList.toggle('storefront-minimal-overlay-open', hasOpenOverlay);
        document.documentElement.classList.toggle('storefront-minimal-menu-open', hasOpenMenu);
        document.body.classList.toggle('storefront-minimal-menu-open', hasOpenMenu);
        page.classList.toggle('is-minimal-overlay-open', hasOpenOverlay);
        page.classList.toggle('is-minimal-menu-open', hasOpenMenu);

        if (hasOpenOverlay && !isScrollLocked) {
            lockedScrollY = window.scrollY || document.documentElement.scrollTop || 0;
            document.body.style.setProperty('--storefront-lock-top', `-${lockedScrollY}px`);
            isScrollLocked = true;
        }

        if (!hasOpenOverlay && isScrollLocked) {
            document.body.style.removeProperty('--storefront-lock-top');
            isScrollLocked = false;
            window.scrollTo(0, lockedScrollY);
        }
    };

    minimalOverlayToggles.forEach((toggle) => {
        toggle.addEventListener('change', syncMinimalOverlayScrollLock);
    });

    syncMinimalOverlayScrollLock();

    const setupTechnologyCustomScrollbar = () => {
        if (!page.classList.contains('storefront-page--technology')) {
            return;
        }

        const scrollRoot = document.scrollingElement || document.documentElement;
        const scrollbar = document.createElement('div');
        const thumb = document.createElement('div');
        const brandColor = getComputedStyle(page).getPropertyValue('--brand-color').trim() || '#2563eb';

        scrollbar.className = 'tech-custom-scrollbar';
        scrollbar.style.setProperty('--tech-scrollbar-color', brandColor);
        thumb.className = 'tech-custom-scrollbar-thumb';
        scrollbar.appendChild(thumb);
        document.body.appendChild(scrollbar);

        let scrollSyncFrame = null;

        const sync = () => {
            scrollSyncFrame = null;

            if (document.documentElement.classList.contains('storefront-minimal-overlay-open')) {
                scrollbar.hidden = true;
                return;
            }

            const scrollHeight = scrollRoot.scrollHeight || 0;
            const viewportHeight = window.innerHeight || scrollRoot.clientHeight || 0;
            const maxScroll = Math.max(0, scrollHeight - viewportHeight);

            if (maxScroll <= 2) {
                scrollbar.hidden = true;
                return;
            }

            scrollbar.hidden = false;

            const thumbHeight = Math.max(42, Math.round((viewportHeight / scrollHeight) * viewportHeight));
            const maxThumbTop = Math.max(0, viewportHeight - thumbHeight);
            const thumbTop = Math.round((scrollRoot.scrollTop / maxScroll) * maxThumbTop);

            thumb.style.height = `${thumbHeight}px`;
            thumb.style.transform = `translateY(${thumbTop}px)`;
        };

        const scheduleSync = () => {
            if (scrollSyncFrame !== null) {
                return;
            }

            scrollSyncFrame = window.requestAnimationFrame(sync);
        };

        window.addEventListener('scroll', scheduleSync, { passive: true });
        window.addEventListener('resize', sync);
        window.addEventListener('load', sync);
        minimalOverlayToggles.forEach((toggle) => {
            toggle.addEventListener('change', sync);
        });
        sync();
    };

    setupTechnologyCustomScrollbar();

    document.querySelectorAll('.minimal-product-tab-control[role="button"]').forEach((control) => {
        control.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            control.click();
        });
    });

    const getRenderedLineHeight = (element) => {
        const styles = window.getComputedStyle(element);
        const parsedLineHeight = Number.parseFloat(styles.lineHeight);
        const fontSize = Number.parseFloat(styles.fontSize) || 14;

        return Number.isFinite(parsedLineHeight) ? parsedLineHeight : fontSize * 1.5;
    };

    const measureRenderedTextHeight = (element) => {
        const width = element.getBoundingClientRect().width;

        if (width <= 0) {
            return 0;
        }

        const textValue = element.innerText.trim();

        if (!textValue) {
            return 0;
        }

        const styles = window.getComputedStyle(element);
        const meter = document.createElement('div');
        meter.textContent = textValue;
        Object.assign(meter.style, {
            position: 'absolute',
            visibility: 'hidden',
            pointerEvents: 'none',
            left: '-9999px',
            top: '0',
            width: `${width}px`,
            height: 'auto',
            maxHeight: 'none',
            overflow: 'visible',
            boxSizing: 'border-box',
            margin: '0',
            padding: '0',
            border: '0',
            fontFamily: styles.fontFamily,
            fontSize: styles.fontSize,
            fontStyle: styles.fontStyle,
            fontWeight: styles.fontWeight,
            lineHeight: styles.lineHeight,
            letterSpacing: styles.letterSpacing,
            textTransform: styles.textTransform,
            wordSpacing: styles.wordSpacing,
            whiteSpace: 'pre-line',
            overflowWrap: styles.overflowWrap,
            wordBreak: styles.wordBreak,
            hyphens: styles.hyphens,
        });
        document.body.appendChild(meter);

        const height = meter.getBoundingClientRect().height;
        meter.remove();

        return height;
    };

    const syncFashionCompactBlock = (copy) => {
        const card = copy.closest('[data-fashion-compact-card]');
        const toggle = card?.querySelector('[data-fashion-compact-toggle]');

        if (!card || !toggle) {
            return;
        }

        const isExpanded = copy.dataset.expanded === 'true';
        copy.classList.remove('is-collapsed');
        copy.style.removeProperty('--fashion-collapsed-height');

        const lineHeight = getRenderedLineHeight(copy);
        const maxThreeLinesHeight = lineHeight * 3;
        const measuredTextHeight = measureRenderedTextHeight(copy);
        const needsReadMore = measuredTextHeight > maxThreeLinesHeight + 2;

        if (!needsReadMore) {
            copy.dataset.expanded = 'false';
            copy.classList.remove('is-collapsed');
            copy.style.removeProperty('--fashion-collapsed-height');
            card.classList.remove('is-expanded');
            toggle.hidden = true;
            toggle.style.display = 'none';
            toggle.setAttribute('aria-expanded', 'false');
            return;
        }

        toggle.hidden = false;
        toggle.style.removeProperty('display');
        copy.style.setProperty('--fashion-collapsed-height', `${maxThreeLinesHeight}px`);
        copy.classList.toggle('is-collapsed', !isExpanded);
        card.classList.toggle('is-expanded', isExpanded);
        toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    };

    let fashionCompactResizeTimer;

    fashionCompactBlocks.forEach((copy) => {
        const card = copy.closest('[data-fashion-compact-card]');
        const toggle = card?.querySelector('[data-fashion-compact-toggle]');

        if (!toggle) {
            return;
        }

        copy.dataset.expanded = 'false';
        toggle.addEventListener('click', () => {
            copy.dataset.expanded = copy.dataset.expanded === 'true' ? 'false' : 'true';
            syncFashionCompactBlock(copy);
        });
        window.requestAnimationFrame(() => syncFashionCompactBlock(copy));
    });

    if (document.fonts?.ready) {
        document.fonts.ready.then(() => {
            fashionCompactBlocks.forEach(syncFashionCompactBlock);
        }).catch(() => {});
    }

    window.addEventListener('resize', () => {
        window.clearTimeout(fashionCompactResizeTimer);
        fashionCompactResizeTimer = window.setTimeout(() => {
            fashionCompactBlocks.forEach(syncFashionCompactBlock);
        }, 120);
    });

    const applyFashionCatalogFilters = () => {
        if (!fashionProducts.length) {
            if (fashionEmptyState) {
                fashionEmptyState.hidden = false;
            }

            if (fashionEndMessage) {
                fashionEndMessage.hidden = true;
            }

            return;
        }

        let visibleCount = 0;
        const sortedProducts = [...fashionProducts];

        sortedProducts.sort((first, second) => {
            const sort = fashionSortSelect?.value || 'default';
            const firstName = first.dataset.fashionName || '';
            const secondName = second.dataset.fashionName || '';
            const firstPrice = Number.parseFloat(first.dataset.fashionPrice || '0');
            const secondPrice = Number.parseFloat(second.dataset.fashionPrice || '0');

            if (sort === 'name-asc') {
                return firstName.localeCompare(secondName, 'es');
            }

            if (sort === 'name-desc') {
                return secondName.localeCompare(firstName, 'es');
            }

            if (sort === 'price-desc') {
                return secondPrice - firstPrice;
            }

            if (sort === 'price-asc') {
                return firstPrice - secondPrice;
            }

            return fashionProducts.indexOf(first) - fashionProducts.indexOf(second);
        });

        if (fashionProductGrid) {
            sortedProducts.forEach((product) => fashionProductGrid.appendChild(product));
        }

        fashionProducts.forEach((product) => {
            const productSizes = (product.dataset.fashionSizes || '').split(',').filter(Boolean);
            const productCategories = (product.dataset.fashionCategories || product.dataset.fashionCategory || '').split(',').filter(Boolean);
            const matchesCategory = fashionCatalogIsServerFiltered || activeFashionCategory === 'all' || productCategories.includes(activeFashionCategory);
            const matchesSize = activeFashionSize === 'all' || productSizes.includes(activeFashionSize);
            const isVisible = matchesCategory && matchesSize;

            product.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        fashionCategoryButtons.forEach((button) => {
            const isActive = button.dataset.fashionCategoryFilter === activeFashionCategory;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        fashionSizeButtons.forEach((button) => {
            const isActive = button.dataset.fashionSizeOption === activeFashionSize;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (fashionEmptyState) {
            fashionEmptyState.hidden = visibleCount > 0;
        }

        if (fashionEndMessage) {
            fashionEndMessage.hidden = visibleCount === 0;
        }
    };

    const replaceFashionCatalogProducts = (html) => {
        if (!fashionProductGrid) {
            return;
        }

        const template = document.createElement('template');
        template.innerHTML = (html || '').trim();
        fashionProductGrid.replaceChildren(...Array.from(template.content.children));
        bindAddToCartForms(fashionProductGrid);
        fashionProducts = Array.from(fashionProductGrid.querySelectorAll('[data-fashion-product]'));
        applyFashionCatalogFilters();
    };

    const loadFashionCategoryProducts = async (button) => {
        const feed = button.closest('[data-infinite-products]') || document.querySelector('.fashion-arrivals[data-infinite-products]');
        const categoryUrl = button.dataset.fashionCategoryUrl;

        if (!feed || !categoryUrl || !fashionProductGrid) {
            return false;
        }

        fashionCatalogVersion += 1;
        const requestVersion = fashionCatalogVersion;

        if (fashionCategoryRequestController) {
            fashionCategoryRequestController.abort();
        }

        if (defaultInfiniteRequestController) {
            defaultInfiniteRequestController.abort();
            defaultInfiniteRequestController = null;
        }

        fashionCategoryRequestController = 'AbortController' in window
            ? new AbortController()
            : null;
        const requestController = fashionCategoryRequestController;

        const loader = feed.querySelector('[data-infinite-loader]');
        const sentinel = feed.querySelector('[data-infinite-sentinel]');
        const endMessage = feed.querySelector('[data-infinite-end]');
        defaultCatalogVersion += 1;
        feed.dataset.infiniteCatalogVersion = String(defaultCatalogVersion);
        feed.dataset.fashionCatalogVersion = String(requestVersion);
        feed.dataset.infiniteNextPageUrl = '';

        if (loader) {
            loader.hidden = false;
        }

        if (sentinel) {
            sentinel.hidden = true;
        }

        if (endMessage) {
            endMessage.hidden = true;
        }

        try {
            const response = await fetch(infiniteUrl(categoryUrl), {
                signal: fashionCategoryRequestController?.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('No se pudieron cargar los productos de la categoría.');
            }

            const data = await response.json();

            if (requestVersion !== fashionCatalogVersion || activeFashionCategory !== (button.dataset.fashionCategoryFilter || 'all')) {
                return true;
            }

            replaceFashionCatalogProducts(data.html || '');
            feed.dataset.infiniteNextPageUrl = data.next_page_url || '';

            if (sentinel) {
                sentinel.hidden = !data.next_page_url;
            }

            if (!data.next_page_url && endMessage && fashionProducts.length > 0) {
                endMessage.hidden = false;
            }

            if (window.history?.replaceState) {
                window.history.replaceState({}, '', categoryUrl);
            }

            return true;
        } catch (error) {
            if (error.name === 'AbortError') {
                return null;
            }

            if (sentinel) {
                sentinel.hidden = true;
            }

            return false;
        } finally {
            if (fashionCategoryRequestController === requestController) {
                fashionCategoryRequestController = null;
            }

            if (requestVersion === fashionCatalogVersion && loader) {
                loader.hidden = true;
            }
        }
    };

    const syncFashionCategory = (category) => {
        activeFashionCategory = category || 'all';
        fashionCatalogIsServerFiltered = activeFashionCategory !== 'all';
        applyFashionCatalogFilters();
    };

    const normalizeSearchText = (value) => String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const searchField = (form, role) => form.querySelector(`[data-storefront-search-${role}], [data-fashion-search-${role}]`);

    const setupStorefrontSearchSuggestions = () => {
        if (!storefrontSearchForms.length) {
            return;
        }

        storefrontSearchForms.forEach((form) => {
            const input = searchField(form, 'input');
            const clearButton = searchField(form, 'clear');
            const results = searchField(form, 'results');
            const allLink = searchField(form, 'all');
            const items = Array.from(form.querySelectorAll('[data-storefront-search-item], [data-fashion-search-item]'));

            if (!input) {
                return;
            }

            const sync = () => {
                const query = input.value.trim();
                const normalizedQuery = normalizeSearchText(query);
                let visibleCount = 0;

                form.classList.toggle('has-value', query !== '');

                if (clearButton) {
                    clearButton.hidden = query === '';
                }

                if (!results) {
                    return;
                }

                if (query === '') {
                    results.hidden = true;
                    return;
                }

                items.forEach((item) => {
                    const matches = normalizeSearchText(item.dataset.searchName).includes(normalizedQuery);
                    item.hidden = !matches;

                    if (matches) {
                        visibleCount += 1;
                    }
                });

                if (allLink) {
                    const action = form.getAttribute('action') || window.location.pathname;
                    allLink.href = `${action}?q=${encodeURIComponent(query)}`;
                    allLink.style.display = 'block';
                    allLink.textContent = visibleCount > 0
                        ? 'Ver todos los resultados'
                        : `Buscar "${query}"`;
                }

                results.hidden = visibleCount === 0 && !allLink;
            };

            input.addEventListener('input', sync);
            input.addEventListener('focus', sync);

            clearButton?.addEventListener('click', () => {
                input.value = '';
                input.focus();
                sync();
            });

            document.addEventListener('click', (event) => {
                if (results && !form.contains(event.target)) {
                    results.hidden = true;
                }
            });

            sync();
        });
    };

    setupStorefrontSearchSuggestions();

    const applyDefaultCategoryFilter = (options = {}) => {
        if (!defaultCategoryButtons.length || (!defaultCategorySections.length && !defaultCategoryProducts.length)) {
            return;
        }

        let visibleSections = 0;
        let visibleProducts = 0;
        const productsToFilter = options.products || defaultCategoryProducts;
        const shouldSort = options.sort !== false;
        const shouldSyncControls = options.syncControls !== false;
        const shouldUpdateEmptyState = options.updateEmptyState !== false;
        const ignoreCategory = options.ignoreCategory === true || defaultCatalogIsServerFiltered;

        const syncFilterButtons = (buttons, activeValue, dataKey) => {
            buttons.forEach((button) => {
                const isActive = (button.dataset[dataKey] || 'all') === activeValue;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        const matchesAvailability = (product) => {
            if (activeDefaultAvailability === 'all') {
                return true;
            }

            const isAvailable = product.dataset.defaultProductAvailable === 'true';

            return activeDefaultAvailability === 'available' ? isAvailable : !isAvailable;
        };

        const matchesOffer = (product) => {
            return activeDefaultOffer === 'all' || product.dataset.defaultProductOffer === 'true';
        };

        const matchesSize = (product) => {
            if (activeDefaultSize === 'all') {
                return true;
            }

            const productSizes = (product.dataset.defaultProductSizes || '')
                .split(',')
                .map((size) => size.trim())
                .filter(Boolean);

            return productSizes.includes(activeDefaultSize);
        };

        const sortProducts = () => {
            if (!defaultProductGrid || !defaultCategoryProducts.length) {
                return;
            }

            const sortedProducts = [...defaultCategoryProducts].sort((firstProduct, secondProduct) => {
                const firstPrice = Number.parseFloat(firstProduct.dataset.defaultProductPrice || '0');
                const secondPrice = Number.parseFloat(secondProduct.dataset.defaultProductPrice || '0');
                const firstName = (firstProduct.dataset.defaultProductName || '').toLocaleLowerCase();
                const secondName = (secondProduct.dataset.defaultProductName || '').toLocaleLowerCase();
                const firstIndex = Number.parseInt(firstProduct.dataset.defaultProductIndex || '0', 10);
                const secondIndex = Number.parseInt(secondProduct.dataset.defaultProductIndex || '0', 10);

                if (activeDefaultSort === 'price-asc') {
                    return firstPrice - secondPrice;
                }

                if (activeDefaultSort === 'price-desc') {
                    return secondPrice - firstPrice;
                }

                if (activeDefaultSort === 'name-asc') {
                    return firstName.localeCompare(secondName, 'es', { sensitivity: 'base' });
                }

                return firstIndex - secondIndex;
            });

            sortedProducts.forEach((product) => defaultProductGrid.appendChild(product));
        };

        if (shouldSort) {
            sortProducts();
        }

        if (defaultCategoryProducts.length) {
            productsToFilter.forEach((product) => {
                const productCategories = (product.dataset.defaultCategoryProduct || '')
                    .split(',')
                    .map((category) => category.trim())
                    .filter(Boolean);
                const matchesCategory = ignoreCategory || activeDefaultCategory === 'all' || productCategories.includes(activeDefaultCategory);
                const isVisible = matchesCategory
                    && matchesAvailability(product)
                    && matchesOffer(product)
                    && matchesSize(product);

                product.hidden = !isVisible;
                product.style.display = isVisible ? '' : 'none';
                product.setAttribute('aria-hidden', isVisible ? 'false' : 'true');

                if (isVisible) {
                    visibleProducts += 1;
                }
            });
        } else {
            defaultCategorySections.forEach((section) => {
                const sectionCategory = section.dataset.defaultCategorySection || '';
                const isVisible = activeDefaultCategory === 'all' || sectionCategory === activeDefaultCategory;

                section.hidden = !isVisible;

                if (isVisible) {
                    visibleSections += 1;
                }
            });
        }

        if (!options.products) {
            visibleProducts = defaultCategoryProducts.filter((product) => !product.hidden).length;
        }

        if (shouldSyncControls) {
            syncFilterButtons(defaultAvailabilityButtons, activeDefaultAvailability, 'defaultAvailabilityFilter');
            syncFilterButtons(defaultOfferButtons, activeDefaultOffer, 'defaultOfferFilter');
            syncFilterButtons(defaultSizeButtons, activeDefaultSize, 'defaultSizeFilter');

            defaultCategoryButtons.forEach((button) => {
                const isActive = (button.dataset.defaultCategoryFilter || 'all') === activeDefaultCategory;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');

                if (isActive && defaultCategoryCount) {
                    const count = Number.parseInt(button.dataset.defaultCategoryCount || '0', 10);
                    defaultCategoryCount.textContent = `${count} ${count === 1 ? 'producto' : 'productos'}`;
                }
            });
        }

        if (defaultCategoryEmptyState && shouldUpdateEmptyState) {
            const hasVisibleContent = defaultCategoryProducts.length ? visibleProducts > 0 : visibleSections > 0;

            defaultCategoryEmptyState.hidden = hasVisibleContent;
        }
    };

    const replaceDefaultCatalogProducts = (html, options = {}) => {
        if (!defaultProductGrid) {
            return;
        }

        const template = document.createElement('template');
        template.innerHTML = (html || '').trim();
        defaultProductGrid.replaceChildren(...Array.from(template.content.children));
        bindAddToCartForms(defaultProductGrid);

        if (typeof window.vendlyBindMinimalImageFallbacks === 'function') {
            window.vendlyBindMinimalImageFallbacks(defaultProductGrid);
        }

        defaultCategoryProducts = Array.from(defaultProductGrid.querySelectorAll('[data-default-category-product]'));
        applyDefaultCategoryFilter({
            ignoreCategory: options.ignoreCategory === true,
        });
    };

    const loadDefaultCategoryProducts = async (button) => {
        const feed = button.closest('[data-default-catalog]') || document.querySelector('[data-default-catalog]');
        const categoryUrl = button.dataset.defaultCategoryUrl;

        if (!feed || !categoryUrl || !defaultProductGrid) {
            return false;
        }

        defaultCatalogVersion += 1;
        const requestVersion = defaultCatalogVersion;

        if (defaultCategoryRequestController) {
            defaultCategoryRequestController.abort();
        }

        if (defaultInfiniteRequestController) {
            defaultInfiniteRequestController.abort();
            defaultInfiniteRequestController = null;
        }

        defaultCategoryRequestController = 'AbortController' in window
            ? new AbortController()
            : null;

        const loader = feed.querySelector('[data-infinite-loader]');
        const sentinel = feed.querySelector('[data-infinite-sentinel]');
        const endMessage = feed.querySelector('[data-infinite-end]');
        feed.dataset.infiniteCatalogVersion = String(requestVersion);
        feed.dataset.infiniteNextPageUrl = '';

        if (loader) {
            loader.hidden = false;
        }

        if (sentinel) {
            sentinel.hidden = true;
        }

        if (endMessage) {
            endMessage.hidden = true;
        }

        try {
            const response = await fetch(infiniteUrl(categoryUrl), {
                signal: defaultCategoryRequestController?.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('No se pudieron cargar los productos de la categoría.');
            }

            const data = await response.json();

            if (requestVersion !== defaultCatalogVersion) {
                return true;
            }

            defaultCatalogIsServerFiltered = activeDefaultCategory !== 'all';
            replaceDefaultCatalogProducts(data.html || '', {
                ignoreCategory: defaultCatalogIsServerFiltered,
            });
            feed.dataset.infiniteNextPageUrl = data.next_page_url || '';
            feed.dataset.infiniteCatalogVersion = String(defaultCatalogVersion);

            if (sentinel) {
                sentinel.hidden = !data.next_page_url;
            }

            if (!data.next_page_url && endMessage && defaultCategoryProducts.length > 0) {
                endMessage.hidden = false;
            }

            if (window.history?.replaceState) {
                window.history.replaceState({}, '', categoryUrl);
            }

            return true;
        } catch (error) {
            if (error.name === 'AbortError') {
                return null;
            }

            if (requestVersion === defaultCatalogVersion && sentinel) {
                sentinel.hidden = true;
            }

            return false;
        } finally {
            if (requestVersion === defaultCatalogVersion) {
                defaultCategoryRequestController = null;

                if (loader) {
                    loader.hidden = true;
                }
            }
        }
    };

    if (defaultCategoryButtons.length && (defaultCategorySections.length || defaultCategoryProducts.length)) {
        defaultCategoryButtons.forEach((button) => {
            button.addEventListener('click', async (event) => {
                const category = button.dataset.defaultCategoryFilter || 'all';

                event.preventDefault();
                activeDefaultCategory = category;
                defaultCatalogIsServerFiltered = category !== 'all';

                const loadedFromServer = await loadDefaultCategoryProducts(button);

                if (loadedFromServer === null || category !== activeDefaultCategory) {
                    return;
                }

                if (!loadedFromServer) {
                    window.location.href = button.dataset.defaultCategoryUrl || button.href;
                    return;
                }

                document.getElementById('catalogo')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        defaultAvailabilityButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeDefaultAvailability = button.dataset.defaultAvailabilityFilter || 'all';
                applyDefaultCategoryFilter();
            });
        });

        defaultOfferButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeDefaultOffer = button.dataset.defaultOfferFilter || 'all';
                applyDefaultCategoryFilter();
            });
        });

        defaultSizeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeDefaultSize = button.dataset.defaultSizeFilter || 'all';
                applyDefaultCategoryFilter();
            });
        });

        if (defaultSortSelect) {
            defaultSortSelect.addEventListener('change', () => {
                activeDefaultSort = defaultSortSelect.value || 'default';
                applyDefaultCategoryFilter();
            });
        }

        applyDefaultCategoryFilter();
    }

    if (defaultCategoryTrack && defaultCategoryScrollButtons.length) {
        const updateCategoryScrollButtons = () => {
            const maxScroll = Math.max(0, defaultCategoryTrack.scrollWidth - defaultCategoryTrack.clientWidth);
            const currentScroll = defaultCategoryTrack.scrollLeft;
            const hasOverflow = maxScroll > 2;

            defaultCategoryScrollButtons.forEach((button) => {
                const direction = button.dataset.homeCategoryScroll;
                const isAtEdge = (direction === 'prev' && currentScroll <= 2)
                    || (direction === 'next' && currentScroll >= maxScroll - 2);

                button.hidden = !hasOverflow || isAtEdge;
                button.disabled = !hasOverflow || isAtEdge;
            });
        };

        defaultCategoryScrollButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const direction = button.dataset.homeCategoryScroll === 'prev' ? -1 : 1;
                const amount = Math.max(defaultCategoryTrack.clientWidth * 0.78, 220);

                defaultCategoryTrack.scrollBy({
                    left: direction * amount,
                    behavior: 'smooth',
                });
            });
        });

        defaultCategoryTrack.addEventListener('scroll', updateCategoryScrollButtons, { passive: true });
        window.addEventListener('resize', updateCategoryScrollButtons);
        window.addEventListener('load', updateCategoryScrollButtons);

        if (document.fonts?.ready) {
            document.fonts.ready.then(updateCategoryScrollButtons).catch(() => {});
        }

        updateCategoryScrollButtons();
    }

    if (fashionCategoryButtons.length) {
        fashionCategoryButtons.forEach((button) => {
            button.addEventListener('click', async (event) => {
                event.preventDefault();
                const category = button.dataset.fashionCategoryFilter || 'all';

                activeFashionCategory = category;
                fashionCatalogIsServerFiltered = category !== 'all';
                activeFashionSize = 'all';
                applyFashionCatalogFilters();

                const loadedFromServer = await loadFashionCategoryProducts(button);

                if (loadedFromServer === null || category !== activeFashionCategory) {
                    return;
                }

                if (!loadedFromServer) {
                    window.location.href = button.dataset.fashionCategoryUrl || button.dataset.fashionCategoryFilter || '#catalogo';
                    return;
                }

                document.getElementById('catalogo')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    if (fashionSizeButtons.length && fashionProducts.length) {
        fashionSizeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeFashionSize = button.dataset.fashionSizeOption || 'all';
                applyFashionCatalogFilters();
            });
        });
    }

    fashionSortSelect?.addEventListener('change', applyFashionCatalogFilters);

    applyFashionCatalogFilters();

    const syncTopbarHeight = () => {
        if (!storefrontTopbar) {
            page.style.setProperty('--storefront-topbar-height', '0px');
            return;
        }

        page.style.setProperty('--storefront-topbar-height', `${storefrontTopbar.offsetHeight}px`);
    };

    syncTopbarHeight();
    window.addEventListener('load', syncTopbarHeight);
    window.addEventListener('resize', syncTopbarHeight);

    const syncAnnouncementMarquee = () => {
        if (!announcementMessages.length) {
            return;
        }

        announcementMessages.forEach((message) => {
            const group = message.querySelector('.store-announcement-group');
            const bar = message.closest('[data-announcement-bar]');
            const configuredSpeed = Number.parseFloat(bar?.dataset.announcementSpeed || '42');
            const pixelsPerSecond = Number.isFinite(configuredSpeed)
                ? Math.max(24, configuredSpeed)
                : 42;

            if (!group || !group.offsetWidth) {
                return;
            }

            const distance = group.offsetWidth;
            const duration = Math.max(18, distance / pixelsPerSecond);

            message.style.setProperty('--announcement-distance', `${distance}px`);
            message.style.setProperty('--announcement-duration', `${duration.toFixed(2)}s`);
        });
    };

    syncAnnouncementMarquee();
    window.addEventListener('load', syncAnnouncementMarquee);
    window.addEventListener('resize', syncAnnouncementMarquee);

    if (document.fonts?.ready) {
        document.fonts.ready.then(syncAnnouncementMarquee).catch(() => {});
    }

    if (fashionAnnouncement) {
        const slides = Array.from(fashionAnnouncement.querySelectorAll('[data-fashion-announcement-message]'));
        const dots = Array.from(fashionAnnouncement.querySelectorAll('[data-fashion-announcement-dot]'));
        const prev = fashionAnnouncement.querySelector('[data-fashion-announcement-prev]');
        const next = fashionAnnouncement.querySelector('[data-fashion-announcement-next]');
        const currentCounter = fashionAnnouncement.querySelector('[data-tech-announcement-current]');
        const interval = Math.max(3200, Number(fashionAnnouncement.dataset.fashionAnnouncementInterval || 5200));
        fashionAnnouncement.style.setProperty('--fashion-announcement-interval', `${interval}ms`);
        let activeIndex = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
        let announcementTimer;
        let announcementAnimationTimer;

        slides.forEach((slide, slideIndex) => {
            const isActive = slideIndex === activeIndex;
            slide.hidden = false;
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            slide.classList.toggle('is-active', isActive);
            slide.classList.remove('is-leaving');
        });

        const showFashionAnnouncement = (index) => {
            if (!slides.length) {
                return;
            }

            const nextIndex = (index + slides.length) % slides.length;

            if (nextIndex === activeIndex && slides[activeIndex]?.classList.contains('is-active')) {
                return;
            }

            window.clearTimeout(announcementAnimationTimer);

            slides.forEach((slide, slideIndex) => {
                const isCurrent = slideIndex === activeIndex;
                const isNext = slideIndex === nextIndex;

                slide.hidden = false;
                slide.classList.toggle('is-leaving', isCurrent && !isNext);
                slide.classList.toggle('is-active', isNext);
                slide.setAttribute('aria-hidden', isNext ? 'false' : 'true');
            });

            announcementAnimationTimer = window.setTimeout(() => {
                slides.forEach((slide) => {
                    slide.classList.toggle('is-leaving', false);
                });
            }, 420);

            activeIndex = nextIndex;

            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === activeIndex;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            if (currentCounter) {
                currentCounter.textContent = String(activeIndex + 1);
            }

            syncTopbarHeight();
        };

        const stopFashionAnnouncement = () => {
            window.clearInterval(announcementTimer);
        };

        const startFashionAnnouncement = () => {
            stopFashionAnnouncement();

            if (slides.length < 2) {
                return;
            }

            announcementTimer = window.setInterval(() => {
                showFashionAnnouncement(activeIndex + 1);
            }, interval);
        };

        prev?.addEventListener('click', () => {
            showFashionAnnouncement(activeIndex - 1);
            startFashionAnnouncement();
        });

        next?.addEventListener('click', () => {
            showFashionAnnouncement(activeIndex + 1);
            startFashionAnnouncement();
        });

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                showFashionAnnouncement(Number(dot.dataset.fashionAnnouncementDot || 0));
                startFashionAnnouncement();
            });
        });

        fashionAnnouncement.addEventListener('mouseenter', stopFashionAnnouncement);
        fashionAnnouncement.addEventListener('mouseleave', startFashionAnnouncement);
        fashionAnnouncement.addEventListener('focusin', stopFashionAnnouncement);
        fashionAnnouncement.addEventListener('focusout', startFashionAnnouncement);

        showFashionAnnouncement(activeIndex);
        startFashionAnnouncement();
    }

    const showFeedback = (message) => {
        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.classList.add('is-visible');

        window.clearTimeout(feedbackTimer);
        feedbackTimer = window.setTimeout(() => {
            feedback.classList.remove('is-visible');
        }, 1800);
    };

    const formatMoney = (value) => `$${Number(value || 0).toLocaleString('es-CO', {
        maximumFractionDigits: 0,
        minimumFractionDigits: 0,
    })}`;
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    const syncStoreCartDrawer = () => {
        if (!cartDrawer || !cartDrawerToggle) {
            return;
        }

        const isOpen = cartDrawerToggle.checked;
        cartDrawer.classList.toggle('is-open', isOpen);
        page.classList.toggle('is-cart-open', isOpen);
        storeCartBackdrop?.classList.toggle('is-open', isOpen);
        cartDrawer.style.removeProperty('right');
        cartDrawer.style.removeProperty('transform');
        syncMinimalOverlayScrollLock();
    };

    cartDrawerToggle?.addEventListener('change', syncStoreCartDrawer);
    document.querySelectorAll('label[for="minimalShopCartToggle"].cart-link').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            if (!cartDrawerToggle || !cartDrawer) {
                return;
            }

            event.stopPropagation();
            event.preventDefault();
            cartDrawerToggle.checked = true;
            syncStoreCartDrawer();
        }, true);
    });
    syncStoreCartDrawer();

    const updateCartBadge = (count) => {
        if (!cartLink) {
            return;
        }

        const ensureBadge = (link) => {
            let badge = link.querySelector('.cart-badge');

            if (!badge && count > 0) {
                badge = document.createElement('span');
                badge.className = 'cart-badge';
                link.appendChild(badge);
            }

            return badge;
        };

        document.querySelectorAll('.cart-link').forEach((link) => {
            const badge = ensureBadge(link);

            if (!badge) {
                return;
            }

            badge.textContent = count;
            badge.hidden = count < 1;
        });

        document.querySelectorAll('[data-cart-count-badge]').forEach((badge) => {
            badge.textContent = count;
            badge.hidden = count < 1;
        });
    };

    const renderCartDrawer = (data) => {
        if (!cartDrawer || !cartDrawerItems) {
            return;
        }

        const items = Array.isArray(data.cart_items) ? data.cart_items : [];
        const subtotal = Number(data.total || 0);
        const shipping = Number(cartDrawer.dataset.cartShipping || 0);
        const count = Number(data.cart_count || 0);

        if (cartDrawerCount) {
            cartDrawerCount.textContent = count;
        }

        document.querySelectorAll('[data-cart-count-badge]').forEach((badge) => {
            badge.textContent = count;
            badge.hidden = count < 1;
        });

        cartDrawer.classList.toggle('is-empty', items.length < 1);

        if (cartDrawerSubtotal) {
            cartDrawerSubtotal.textContent = formatMoney(subtotal);
        }

        if (cartDrawerShipping) {
            cartDrawerShipping.textContent = shipping > 0 ? formatMoney(shipping) : 'Por calcular';
        }

        if (cartDrawerTotal) {
            cartDrawerTotal.textContent = formatMoney(subtotal + shipping);
        }

        cartDrawer.dataset.cartSubtotal = String(subtotal);

        if (!items.length) {
            const storeUrl = escapeHtml(cartDrawer.dataset.storeUrl || '/');
            cartDrawerItems.innerHTML = `
                <div class="minimal-shop-cart-empty" data-cart-drawer-empty>
                    <strong>Tu carrito está vacío</strong>
                    <a href="${storeUrl}">Volver a la tienda</a>
                </div>
            `;
            return;
        }

        cartDrawerItems.innerHTML = items.map((item) => {
            const name = escapeHtml(item.name || 'Producto');
            const imageUrl = escapeHtml(item.image_url || '');
            const key = escapeHtml(item.key || '');
            const image = item.image_url
                ? `<img src="${imageUrl}" alt="${name}">`
                : `<span>${escapeHtml(String(item.name || 'P').charAt(0).toUpperCase())}</span>`;

            return `
                <article class="minimal-shop-cart-item" data-cart-drawer-item data-cart-key="${key}">
                    <div class="minimal-shop-cart-thumb">${image}</div>
                    <div class="minimal-shop-cart-info">
                        <strong>${name}</strong>
                        <b data-cart-item-total>${formatMoney(item.item_total || 0)}</b>
                    </div>
                    <div class="minimal-shop-cart-controls">
                        <button type="button" data-cart-drawer-minus aria-label="Restar">−</button>
                        <span data-cart-drawer-quantity>${item.quantity || 1}</span>
                        <button type="button" data-cart-drawer-plus aria-label="Sumar">+</button>
                        <button type="button" data-cart-drawer-remove aria-label="Eliminar">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 14h10l1-14"></path><path d="M9 7V4h6v3"></path></svg>
                        </button>
                    </div>
                </article>
            `;
        }).join('');
    };

    const sendCartDrawerRequest = async (url, method, body = null) => {
        const response = await fetch(url, {
            method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body,
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || addErrorText);
        }

        return data;
    };

    const closeMenu = () => {
        if (!navbar || !navToggle) {
            return;
        }

        navbar.classList.remove('is-open');
        page.classList.remove('is-menu-open');
        navToggle.setAttribute('aria-expanded', 'false');
    };

    document.querySelectorAll('label.cart-link[tabindex="0"]').forEach((label) => {
        label.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            label.click();
        });
    });

    const closeDropdowns = (currentDropdown = null) => {
        navDropdowns.forEach((dropdown) => {
            if (dropdown === currentDropdown) {
                return;
            }

            dropdown.classList.remove('is-open');
            dropdown.querySelector('.nav-dropdown-button')?.setAttribute('aria-expanded', 'false');
        });
    };

    navDropdowns.forEach((dropdown) => {
        const button = dropdown.querySelector('.nav-dropdown-button');

        button?.addEventListener('click', (event) => {
            event.stopPropagation();

            const isOpen = dropdown.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            closeDropdowns(dropdown);
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.nav-dropdown')) {
            closeDropdowns();
        }
    });

    if (navToggle && navbar) {
        navToggle.addEventListener('click', () => {
            const isOpen = navbar.classList.toggle('is-open');
            page.classList.toggle('is-menu-open', isOpen);
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        navClose?.addEventListener('click', closeMenu);
        navBackdrop?.addEventListener('click', closeMenu);

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMenu();
                closeDropdowns();
                if (cartDrawerToggle) {
                    cartDrawerToggle.checked = false;
                    syncStoreCartDrawer();
                }
            }
        });

        navPanelLinks.forEach((link) => {
            link.addEventListener('click', () => {
                closeDropdowns();

                if (window.innerWidth <= 900) {
                    closeMenu();
                }
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) {
                closeMenu();
            }

            closeDropdowns();
        });
    }

    cartDrawerItems?.addEventListener('click', async (event) => {
        const button = event.target.closest('button');

        if (!button) {
            return;
        }

        const item = button.closest('[data-cart-drawer-item]');
        const cartKey = item?.dataset.cartKey;

        if (!item || !cartKey) {
            return;
        }

        const quantityEl = item.querySelector('[data-cart-drawer-quantity]');
        const currentQuantity = Number(quantityEl?.textContent || 1);
        const shouldRemove = button.matches('[data-cart-drawer-remove]');
        const nextQuantity = button.matches('[data-cart-drawer-minus]')
            ? currentQuantity - 1
            : currentQuantity + 1;

        try {
            button.disabled = true;
            const data = shouldRemove || nextQuantity < 1
                ? await sendCartDrawerRequest(`/cart/item/${encodeURIComponent(cartKey)}`, 'DELETE')
                : await sendCartDrawerRequest(`/cart/item/${encodeURIComponent(cartKey)}`, 'PATCH', JSON.stringify({ quantity: nextQuantity }));

            updateCartBadge(data.cart_count || 0);
            renderCartDrawer(data);
            showFeedback(data.message || 'Carrito actualizado');
        } catch (error) {
            showFeedback(error.message || addErrorText);
        } finally {
            button.disabled = false;
        }
    });

    const bindAddToCartForms = (root = document) => {
        root.querySelectorAll('.add-to-cart-form').forEach((form) => {
            if (form.dataset.cartFormBound === 'true') {
                return;
            }

            form.dataset.cartFormBound = 'true';
            form.addEventListener('submit', async (event) => {
            if (event.submitter?.matches('[data-direct-submit]')) {
                return;
            }

            event.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const originalText = button ? button.textContent : '';
            const originalHtml = button ? button.innerHTML : '';
            const isCompactFashionCart = form.matches('[data-compact-fashion-cart]');
            const isResponsiveProductCard = form.closest('.product-card')
                && window.matchMedia('(max-width: 760px)').matches;
            const useCompactTechnologyLoading = page.classList.contains('storefront-page--technology')
                && window.matchMedia('(max-width: 760px)').matches;
            const startedAt = Date.now();

            if (button) {
                button.disabled = true;
                button.classList.add('is-loading');
                button.setAttribute('aria-busy', 'true');
                const label = button.querySelector('[data-variant-label]');

                if (isCompactFashionCart || useCompactTechnologyLoading || isResponsiveProductCard) {
                    button.setAttribute('aria-label', addingText);
                } else if (label) {
                    label.textContent = addingText;
                } else {
                    button.textContent = addingText;
                }
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new FormData(form),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || addErrorText);
                }

                updateCartBadge(data.cart_count || 0);
                renderCartDrawer(data);

                if (data.meta_event && typeof window.vendlyMetaPixelTrack === 'function') {
                    window.vendlyMetaPixelTrack(data.meta_event.event, data.meta_event.payload || {});
                }

                if (cartDrawerToggle) {
                    cartDrawerToggle.checked = true;
                    syncStoreCartDrawer();
                }

                if (!isCompactFashionCart) {
                    showFeedback(data.message || addedText);
                }
            } catch (error) {
                showFeedback(error.message || addErrorText);
            } finally {
                if (isCompactFashionCart) {
                    const elapsed = Date.now() - startedAt;
                    if (elapsed < compactFashionCartDelay) {
                        await new Promise((resolve) => setTimeout(resolve, compactFashionCartDelay - elapsed));
                    }
                }

                if (button) {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                    button.removeAttribute('aria-busy');
                    button.innerHTML = originalHtml || originalText;
                }
            }
            });
        });
    };

    const initializeInfiniteProducts = (root = document) => {
        const feeds = [
            ...(root.matches?.('[data-infinite-products]') ? [root] : []),
            ...Array.from(root.querySelectorAll('[data-infinite-products]')),
        ];

        if (feeds.length === 0 || !('IntersectionObserver' in window)) {
            return;
        }

        feeds.forEach((feed) => {
            if (feed.dataset.infiniteBound === 'true') {
                return;
            }

            const grid = feed.querySelector('[data-infinite-grid]');
            const pagination = feed.querySelector('[data-infinite-pagination]');
            const loader = feed.querySelector('[data-infinite-loader]');
            const sentinel = feed.querySelector('[data-infinite-sentinel]');
            let endMessage = feed.querySelector('[data-infinite-end]');

            if (!grid || !sentinel) {
                return;
            }

            const nextLink = () => pagination?.querySelector('a[rel="next"]');
            let nextPageUrl = nextLink()?.href || null;
            let isLoading = false;
            let activeInfiniteController = null;
            feed.dataset.infiniteBound = 'true';
            feed.dataset.infiniteNextPageUrl = nextPageUrl || '';
            feed.dataset.infiniteCatalogVersion = feed.dataset.infiniteCatalogVersion || String(defaultCatalogVersion);

            if (pagination) {
                pagination.classList.add('is-infinite-hidden');
            }

            if (!nextPageUrl) {
                sentinel.hidden = true;
            }

            const showEndMessage = () => {
                if (!endMessage) {
                    endMessage = document.createElement('p');
                    endMessage.className = feed.classList.contains('minimal-shop-catalog-shell')
                        ? 'catalog-end-message minimal-shop-end-message'
                        : 'catalog-end-message';
                    endMessage.dataset.infiniteEnd = 'true';
                    endMessage.textContent = 'Has visto todos los productos';
                    feed.appendChild(endMessage);
                }

                endMessage.hidden = false;
            };

            const appendProducts = (html) => {
                const template = document.createElement('template');
                template.innerHTML = html.trim();
                const fragment = document.createDocumentFragment();
                const appendedNodes = Array.from(template.content.children);

                appendedNodes.forEach((node) => {
                    fragment.appendChild(node);
                });

                bindAddToCartForms(fragment);

                if (typeof window.vendlyBindMinimalImageFallbacks === 'function') {
                    window.vendlyBindMinimalImageFallbacks(fragment);
                }

                grid.appendChild(fragment);

                if (feed.matches('[data-default-catalog]')) {
                    const appendedDefaultProducts = appendedNodes.filter((node) => node.matches?.('[data-default-category-product]'));
                    defaultCategoryProducts = Array.from(grid.querySelectorAll('[data-default-category-product]'));

                    if (activeDefaultSort === 'default') {
                        applyDefaultCategoryFilter({
                            products: appendedDefaultProducts,
                            ignoreCategory: defaultCatalogIsServerFiltered,
                            sort: false,
                            syncControls: false,
                            updateEmptyState: false,
                        });
                    } else {
                        applyDefaultCategoryFilter();
                    }
                }

                if (feed.classList.contains('fashion-arrivals')) {
                    fashionProducts = Array.from(feed.querySelectorAll('[data-fashion-product]'));
                    applyFashionCatalogFilters();
                }
            };

            const loadNextPage = async () => {
                nextPageUrl = feed.dataset.infiniteNextPageUrl || nextPageUrl;
                const requestVersion = Number.parseInt(feed.dataset.infiniteCatalogVersion || '0', 10);

                if (isLoading || !nextPageUrl) {
                    return;
                }

                isLoading = true;

                let infiniteController = null;

                const isCancelableCatalog = feed.matches('[data-default-catalog]') || feed.classList.contains('fashion-arrivals');

                if (isCancelableCatalog && 'AbortController' in window) {
                    if (defaultInfiniteRequestController) {
                        defaultInfiniteRequestController.abort();
                    }

                    infiniteController = new AbortController();
                    defaultInfiniteRequestController = infiniteController;
                }

                activeInfiniteController = infiniteController;

                if (loader) {
                    loader.hidden = false;
                }

                try {
                    const response = await fetch(infiniteUrl(nextPageUrl), {
                        signal: infiniteController?.signal,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudieron cargar más productos.');
                    }

                    const data = await response.json();

                    if (requestVersion !== Number.parseInt(feed.dataset.infiniteCatalogVersion || '0', 10)) {
                        return;
                    }

                    if (data.html) {
                        appendProducts(data.html);
                    }

                    nextPageUrl = data.next_page_url || null;
                    feed.dataset.infiniteNextPageUrl = nextPageUrl || '';

                    if (!nextPageUrl) {
                        sentinel.hidden = true;
                        showEndMessage();

                        if (!feed.matches('[data-default-catalog]') && !feed.classList.contains('fashion-arrivals')) {
                            observer.disconnect();
                        }
                    }
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    if (!feed.matches('[data-default-catalog]') && !feed.classList.contains('fashion-arrivals')) {
                        observer.disconnect();
                    }

                    if (pagination) {
                        pagination.classList.remove('is-infinite-hidden');
                    }
                } finally {
                    if (
                        (feed.matches('[data-default-catalog]') || feed.classList.contains('fashion-arrivals'))
                        && infiniteController
                        && defaultInfiniteRequestController === infiniteController
                    ) {
                        defaultInfiniteRequestController = null;
                    }

                    if (
                        (!feed.matches('[data-default-catalog]') && !feed.classList.contains('fashion-arrivals'))
                        || activeInfiniteController === infiniteController
                    ) {
                        isLoading = false;
                        activeInfiniteController = null;
                    }

                    if (requestVersion === Number.parseInt(feed.dataset.infiniteCatalogVersion || '0', 10) && loader) {
                        loader.hidden = true;
                    }
                }
            };

            const observer = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    loadNextPage();
                }
            }, {
                rootMargin: feed.matches('[data-default-catalog]') ? '220px 0px' : '520px 0px',
                threshold: 0,
            });

            observer.observe(sentinel);
        });
    };

    window.vendlyInitializeInfiniteProducts = initializeInfiniteProducts;

    bindAddToCartForms();
    initializeInfiniteProducts();
})();
