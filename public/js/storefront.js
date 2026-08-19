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

    const forms = document.querySelectorAll('.add-to-cart-form');
    const cartLink = document.querySelector('.cart-link');
    const feedback = document.getElementById('cartFeedback');
    const cartDrawer = document.querySelector('[data-cart-drawer]');
    const cartDrawerToggle = document.getElementById('minimalShopCartToggle');
    const storeCartBackdrop = document.querySelector('.store-cart-backdrop, .fashion-cart-backdrop');
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
    const fashionProducts = Array.from(document.querySelectorAll('[data-fashion-product]'));
    const fashionEmptyState = document.querySelector('[data-fashion-empty-state]');
    const fashionEndMessage = document.querySelector('[data-fashion-end-message]');
    const fashionSearchForms = Array.from(document.querySelectorAll('[data-fashion-search]'));
    const fashionProductGrid = document.querySelector('[data-fashion-product-grid]');
    const fashionSizeButtons = Array.from(document.querySelectorAll('[data-fashion-size-option]'));
    const fashionSortSelect = document.querySelector('[data-fashion-sort]');
    const announcementMessages = Array.from(document.querySelectorAll('[data-announcement-message]'));
    const storefrontTopbar = document.querySelector('[data-storefront-topbar]');
    const fashionCompactToggles = Array.from(document.querySelectorAll('[data-fashion-compact-toggle]'));
    const csrfToken = page.dataset.csrf || '';
    const addingText = page.dataset.addingText || 'Agregando...';
    const addedText = page.dataset.feedbackAdded || 'Producto agregado al carrito';
    const addErrorText = page.dataset.feedbackError || 'No pudimos agregar el producto';
    const compactFashionCartDelay = 1000;
    let feedbackTimer;
    let activeFashionCategory = 'all';
    let activeFashionSize = 'all';

    resolveBrandContrast();

    fashionCompactToggles.forEach((toggle) => {
        const card = toggle.closest('[data-fashion-compact-card]');
        const rest = card?.querySelector('.fashion-product-compact-rest');
        const ellipsis = card?.querySelector('.fashion-product-compact-ellipsis');

        if (!card || !rest) {
            toggle.hidden = true;
            return;
        }

        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            const nextExpanded = !isExpanded;

            toggle.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
            card.classList.toggle('is-expanded', nextExpanded);
            rest.hidden = !nextExpanded;

            if (ellipsis) {
                ellipsis.hidden = nextExpanded;
            }
        });
    });

    const applyFashionCatalogFilters = () => {
        if (!fashionProducts.length) {
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
            const matchesCategory = activeFashionCategory === 'all' || productCategories.includes(activeFashionCategory);
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

    const syncFashionCategory = (category) => {
        activeFashionCategory = category || 'all';
        applyFashionCatalogFilters();
    };

    const normalizeSearchText = (value) => String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const setupFashionSearchSuggestions = () => {
        if (!fashionSearchForms.length) {
            return;
        }

        fashionSearchForms.forEach((form) => {
            const input = form.querySelector('[data-fashion-search-input]');
            const clearButton = form.querySelector('[data-fashion-search-clear]');
            const results = form.querySelector('[data-fashion-search-results]');
            const allLink = form.querySelector('[data-fashion-search-all]');
            const items = Array.from(form.querySelectorAll('[data-fashion-search-item]'));

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

    setupFashionSearchSuggestions();

    if (fashionCategoryButtons.length && fashionProducts.length) {
        fashionCategoryButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
            event.preventDefault();
            syncFashionCategory(button.dataset.fashionCategoryFilter);
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
            const detail = escapeHtml(item.color || item.size || 'Sin variante');
            const badge = escapeHtml(item.color || 'Otros');

            return `
                <article class="minimal-shop-cart-item" data-cart-drawer-item data-cart-key="${key}">
                    <div class="minimal-shop-cart-thumb">${image}</div>
                    <div class="minimal-shop-cart-info">
                        <span>${badge}</span>
                        <strong>${name}</strong>
                        <small>${detail}</small>
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

    forms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const originalText = button ? button.textContent : '';
            const originalHtml = button ? button.innerHTML : '';
            const isCompactFashionCart = form.matches('[data-compact-fashion-cart]');
            const startedAt = Date.now();

            if (button) {
                button.disabled = true;
                button.classList.add('is-loading');
                const label = button.querySelector('[data-variant-label]');

                if (isCompactFashionCart) {
                    button.setAttribute('aria-busy', 'true');
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
})();
