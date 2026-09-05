(function () {
    const page = document.querySelector('.cart-page');

    if (!page) {
        return;
    }

    const feedback = document.getElementById('cartFeedback');
    const checkoutForm = document.getElementById('checkoutForm') || document.querySelector('.fashion-checkout-form');
    const totalEls = Array.from(document.querySelectorAll('[data-role="total"]'));
    const grandTotalEls = Array.from(document.querySelectorAll('[data-role="grand-total"]'));
    const shippingTotalEls = Array.from(document.querySelectorAll('[data-role="shipping-total"]'));
    const shippingCostField = document.querySelector('[data-shipping-cost-field]');
    const discountTotalEls = Array.from(document.querySelectorAll('[data-role="discount-total"]'));
    const discountRow = document.querySelector('[data-discount-row]');
    const discountCodeInput = document.querySelector('[data-discount-code]');
    const discountApplyButton = document.querySelector('[data-discount-apply]');
    const discountMessage = document.querySelector('[data-discount-message]');
    const discountCodeLabel = document.querySelector('[data-discount-code-label]');
    const shippingOptions = Array.from(document.querySelectorAll('[data-shipping-option]'));
    const departmentSelect = document.querySelector('[data-department-select]');
    const cityInput = document.querySelector('[data-city-input]');
    const cityOptions = cityInput?.matches('select') ? Array.from(cityInput.options) : [];
    const localDeliveryPreview = document.querySelector('[data-local-delivery-preview]');
    const localDeliveryLabel = document.querySelector('[data-local-delivery-label]');
    const localDeliveryPrice = document.querySelector('[data-local-delivery-price]');
    const clearCartButton = document.getElementById('clearCartButton');
    const paymentChoices = Array.from(document.querySelectorAll('[data-payment-choice]'));
    const paymentSubmitButtons = Array.from(document.querySelectorAll('[data-payment-submit]'));
    const checkoutSummary = document.querySelector('.fashion-checkout-summary');
    const checkoutSummaryToggle = document.querySelector('[data-checkout-summary-toggle]');
    const checkoutSummaryToggleLabel = document.querySelector('[data-checkout-summary-toggle-label]');
    const csrfToken = page.dataset.csrf || '';
    const updatedText = page.dataset.feedbackUpdated || 'Carrito actualizado';
    const updateErrorText = page.dataset.feedbackUpdateError || 'No se pudo actualizar el carrito.';
    const emptyErrorText = page.dataset.feedbackEmptyError || 'No se pudo vaciar el carrito.';
    const storeSlug = page.dataset.storeSlug || '';
    const couponPreviewUrl = page.dataset.couponPreviewUrl || '';
    const freeShippingMinimum = Number(page.dataset.freeShippingMinimum || 0);
    const localDeliveryEnabled = page.dataset.localDeliveryEnabled === '1';
    const localDeliveryArea = page.dataset.localDeliveryArea || '';
    const localDeliveryCityCode = page.dataset.localDeliveryCityCode || '';
    const localDeliveryCost = Number(page.dataset.localDeliveryCost || 0);
    const outsideDeliveryCost = Number(page.dataset.outsideDeliveryCost || 0);
    let subtotal = Number(page.dataset.cartSubtotal || 0);
    let discountAmount = Number(page.dataset.cartDiscount || 0);
    let feedbackTimer;
    let discountRefreshTimer;
    let lastTermsTrigger = null;
    let summaryWasToggled = false;

    const checkoutSummaryMedia = window.matchMedia('(max-width: 767px)');
    const setCheckoutSummaryExpanded = (expanded) => {
        if (!checkoutSummary || !checkoutSummaryToggle || !checkoutSummaryToggleLabel) {
            return;
        }

        checkoutSummary.classList.toggle('is-expanded', expanded);
        checkoutSummaryToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        checkoutSummaryToggleLabel.textContent = expanded ? 'Ocultar detalle' : 'Ver detalle';
    };

    const syncCheckoutSummaryMode = () => {
        if (!checkoutSummary || !checkoutSummaryToggle) {
            return;
        }

        if (!checkoutSummaryMedia.matches) {
            setCheckoutSummaryExpanded(true);
            checkoutSummaryToggle.disabled = true;
            return;
        }

        checkoutSummaryToggle.disabled = false;

        if (!summaryWasToggled) {
            setCheckoutSummaryExpanded(false);
        }
    };

    const money = (value) => `$ ${new Intl.NumberFormat('es-CO').format(value || 0)}`;
    const grandTotal = (cost) => Math.max(0, subtotal + cost - discountAmount);
    const freeShippingApplies = () => freeShippingMinimum > 0 && subtotal >= freeShippingMinimum;
    const normalizeArea = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9\s-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    const selectedCityName = () => {
        if (!cityInput) {
            return '';
        }

        if (!cityInput.matches('select')) {
            return cityInput.value;
        }

        return cityInput.selectedOptions[0]?.dataset.cityName || '';
    };
    const selectedCityCode = () => {
        if (!cityInput?.matches('select')) {
            return '';
        }

        return cityInput.value || '';
    };
    const isLocalDeliveryCity = () => {
        const cityCode = selectedCityCode();

        if (localDeliveryCityCode && cityCode) {
            return localDeliveryCityCode === cityCode;
        }

        return normalizeArea(selectedCityName()) === normalizeArea(localDeliveryArea);
    };
    const hasSelectedCity = () => normalizeArea(selectedCityName()) !== '';

    const showFeedback = (message) => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.classList.add('is-visible');
        clearTimeout(feedbackTimer);
        feedbackTimer = setTimeout(() => feedback.classList.remove('is-visible'), 1800);
    };

    const selectedPaymentChoice = () => paymentChoices.find((choice) => choice.checked);

    const syncPaymentChoice = () => {
        const selected = selectedPaymentChoice();

        if (!selected) {
            return;
        }

        paymentSubmitButtons.forEach((button) => {
            const label = selected.dataset.paymentLabel;
            const labelEl = button.querySelector('span');

            if (labelEl && label) {
                labelEl.textContent = label;
                return;
            }

            if (label) {
                button.textContent = label;
            }
        });
    };

    const shippingCost = () => {
        const selected = shippingOptions.find((option) => option.checked);

        if (selected) {
            const baseCost = Number(selected.dataset.shippingCost || 0);

            return freeShippingApplies() ? 0 : baseCost;
        }

        if (localDeliveryEnabled) {
            if (!hasSelectedCity()) {
                return 0;
            }

            const baseCost = isLocalDeliveryCity()
                ? localDeliveryCost
                : outsideDeliveryCost;

            return freeShippingApplies() ? 0 : baseCost;
        }
    };

    const updateShippingLabels = () => {
        if (localDeliveryEnabled) {
            if (!localDeliveryLabel || !localDeliveryPrice) {
                return;
            }

            if (!hasSelectedCity()) {
                localDeliveryLabel.textContent = 'Envio por ciudad';
                localDeliveryPrice.textContent = 'Por calcular';
                return;
            }

            const isLocal = isLocalDeliveryCity();
            const baseCost = isLocal ? localDeliveryCost : outsideDeliveryCost;
            const nextCost = freeShippingApplies() ? 0 : baseCost;
            localDeliveryLabel.textContent = isLocal
                ? `Envio local: ${localDeliveryArea}`
                : `Envio fuera de ${localDeliveryArea}`;
            localDeliveryPrice.textContent = nextCost > 0 ? money(nextCost) : 'Gratis';
            localDeliveryPreview?.classList.toggle('is-local', isLocal);
        }

        shippingOptions.forEach((option) => {
            const price = option.closest('.shipping-option, .fashion-shipping-option')?.querySelector('[data-shipping-price]');

            if (!price) {
                return;
            }

            const baseCost = Number(option.dataset.shippingCost || 0);
            const nextCost = freeShippingApplies() ? 0 : baseCost;
            price.textContent = nextCost > 0 ? money(nextCost) : 'Gratis';
        });
    };

    const updateSummary = (data = null) => {
        if (data && typeof data.total !== 'undefined') {
            subtotal = Number(data.total || 0);
            page.dataset.cartSubtotal = String(subtotal);
        }

        const cost = shippingCost();
        const hasSelectedShippingOption = shippingOptions.some((option) => option.checked);
        const awaitingCity = localDeliveryEnabled && !hasSelectedCity() && !hasSelectedShippingOption;

        updateShippingLabels();
        totalEls.forEach((element) => {
            element.textContent = money(subtotal);
        });
        discountTotalEls.forEach((element) => {
            element.textContent = `- ${money(discountAmount)}`;
        });
        if (discountRow) discountRow.classList.toggle('is-hidden', discountAmount <= 0);
        shippingTotalEls.forEach((element) => {
            element.textContent = awaitingCity ? 'Por calcular' : (cost > 0 ? money(cost) : 'Gratis');
        });
        if (shippingCostField) {
            shippingCostField.value = String(awaitingCity ? 0 : cost);
        }
        grandTotalEls.forEach((element) => {
            element.textContent = money(grandTotal(awaitingCity ? 0 : cost));
        });
    };

    const updateItemQuantity = (item, quantity) => {
        const qtyEl = item.querySelector('[data-role="quantity"]');
        const qtyBadge = item.querySelector('[data-role="quantity-badge"]');

        if (qtyEl) qtyEl.textContent = quantity;
        if (qtyBadge) qtyBadge.textContent = quantity;
    };

    const sendCartRequest = async (url, method, body = null) => {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        };

        if (body) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(url, {
            method,
            headers,
            body,
        });

        const text = await response.text();
        let data = {};

        if (text) {
            try {
                data = JSON.parse(text);
            } catch (error) {
                data = { message: response.ok ? '' : updateErrorText };
            }
        }

        if (!response.ok) {
            throw new Error(data.message || updateErrorText);
        }

        return data;
    };

    const openTermsModal = (modal, trigger = null) => {
        if (!modal) {
            return;
        }

        lastTermsTrigger = trigger;
        modal.hidden = false;
        document.body.classList.add('has-checkout-modal');
        modal.querySelector('.checkout-terms-modal-panel')?.focus();
    };

    const closeTermsModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.hidden = true;
        document.body.classList.remove('has-checkout-modal');

        if (lastTermsTrigger && document.contains(lastTermsTrigger)) {
            lastTermsTrigger.focus();
        }

        lastTermsTrigger = null;
    };

    page.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-terms-open]');

        if (openButton) {
            event.preventDefault();
            openTermsModal(document.getElementById(openButton.dataset.termsOpen), openButton);
            return;
        }

        const closeButton = event.target.closest('[data-terms-close]');

        if (closeButton) {
            event.preventDefault();
            closeTermsModal(closeButton.closest('.checkout-terms-modal'));
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        closeTermsModal(document.querySelector('.checkout-terms-modal:not([hidden])'));
    });

    const setDiscountMessage = (message = '', isError = false) => {
        if (!discountMessage) {
            return;
        }

        discountMessage.textContent = message;
        discountMessage.classList.toggle('is-error', isError);
    };

    const previewDiscount = async () => {
        if (!couponPreviewUrl || !discountCodeInput || !discountApplyButton) {
            return;
        }

        const code = discountCodeInput.value.trim();

        try {
            discountApplyButton.disabled = true;
            setDiscountMessage('');

            const data = await sendCartRequest(couponPreviewUrl, 'POST', JSON.stringify({
                store: storeSlug,
                discount_code: code,
                shipping_cost: shippingCost(),
            }));

            discountAmount = Number(data.discount_amount || 0);
            page.dataset.cartDiscount = String(discountAmount);

            if (discountCodeLabel) {
                discountCodeLabel.textContent = data.discount_code || '';
            }

            setDiscountMessage(data.message || (discountAmount > 0 ? 'Cupon aplicado.' : 'Cupon removido.'));
            updateSummary();
        } catch (error) {
            discountAmount = 0;
            page.dataset.cartDiscount = '0';

            if (discountCodeLabel) {
                discountCodeLabel.textContent = '';
            }

            setDiscountMessage(error.message || 'No se pudo aplicar el cupon.', true);
            updateSummary();
        } finally {
            discountApplyButton.disabled = false;
        }
    };

    const refreshDiscountOrSummary = () => {
        if (discountCodeInput?.value.trim()) {
            clearTimeout(discountRefreshTimer);
            discountRefreshTimer = setTimeout(previewDiscount, 250);
            return;
        }

        updateSummary();
    };

    discountApplyButton?.addEventListener('click', previewDiscount);
    discountCodeInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            previewDiscount();
        }
    });

    paymentChoices.forEach((choice) => {
        choice.addEventListener('change', syncPaymentChoice);
    });

    checkoutSummaryToggle?.addEventListener('click', () => {
        if (!checkoutSummaryMedia.matches || !checkoutSummary) {
            return;
        }

        summaryWasToggled = true;
        setCheckoutSummaryExpanded(!checkoutSummary.classList.contains('is-expanded'));
    });

    checkoutSummaryMedia.addEventListener?.('change', syncCheckoutSummaryMode);

    checkoutForm?.addEventListener('submit', (event) => {
        const selected = selectedPaymentChoice();
        const action = selected?.dataset.paymentAction;

        if (action) {
            checkoutForm.action = action;
        }

        if (!checkoutForm.checkValidity()) {
            event.preventDefault();
            checkoutForm.reportValidity();

            const invalidField = checkoutForm.querySelector(':invalid');
            invalidField
                ?.closest('.fashion-checkout-block, .fashion-field, .checkout-terms, .checkout-card')
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const loadingLabel = selected?.value === 'whatsapp'
            ? 'Preparando WhatsApp...'
            : 'Redirigiendo al pago...';

        paymentSubmitButtons.forEach((button) => {
            button.disabled = true;
            button.classList.add('is-loading');

            const labelEl = button.querySelector('span');
            if (labelEl) {
                labelEl.textContent = loadingLabel;
                return;
            }

            button.textContent = loadingLabel;
        });
    });

    page.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-action]');

        if (!button) {
            return;
        }

        event.preventDefault();

        const productId = button.dataset.productId;
        const item = button.closest('[data-cart-item]');

        if (!item) return;

        const qtyEl = item.querySelector('[data-role="quantity"]');
        const itemTotalEl = item.querySelector('[data-role="item-total"]');
        const currentQty = Number(qtyEl?.textContent || 1);
        const originalQty = currentQty;

        try {
            let data;
            button.disabled = true;

            if (button.dataset.action === 'remove') {
                data = await sendCartRequest(`/cart/item/${encodeURIComponent(productId)}`, 'DELETE');
                item.remove();
            } else {
                const nextQty = button.dataset.action === 'increase' ? currentQty + 1 : currentQty - 1;

                if (nextQty < 1) {
                    data = await sendCartRequest(`/cart/item/${encodeURIComponent(productId)}`, 'DELETE');
                    item.remove();
                } else {
                    updateItemQuantity(item, nextQty);
                    data = await sendCartRequest(`/cart/item/${encodeURIComponent(productId)}`, 'PATCH', JSON.stringify({ quantity: nextQty }));
                    updateItemQuantity(item, data.item_quantity || nextQty);
                    if (itemTotalEl && data.item_total !== null) {
                        itemTotalEl.textContent = money(data.item_total);
                    }
                }
            }

            updateSummary(data);
            if (discountCodeInput?.value.trim()) {
                await previewDiscount();
            }
            showFeedback(data.message || updatedText);

            if (data.cart_is_empty) {
                window.location.reload();
            }
        } catch (error) {
            updateItemQuantity(item, originalQty);
            showFeedback(error.message || updateErrorText);
        } finally {
            button.disabled = false;
        }
    });

    if (clearCartButton) {
        clearCartButton.addEventListener('click', async () => {
            try {
                const clearUrl = storeSlug ? `/cart?store=${encodeURIComponent(storeSlug)}` : '/cart';
                const data = await sendCartRequest(clearUrl, 'DELETE');
                updateSummary(data);
                showFeedback(data.message || updatedText);

                if (data.cart_is_empty) {
                    window.location.reload();
                }
            } catch (error) {
                showFeedback(error.message || emptyErrorText);
            }
        });
    }

    shippingOptions.forEach((option) => {
        option.addEventListener('change', refreshDiscountOrSummary);
    });

    const syncCityOptions = () => {
        if (!departmentSelect || !cityInput?.matches('select')) {
            return;
        }

        const department = departmentSelect.value;

        cityInput.disabled = department === '';

        cityOptions.forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = department !== '' && option.dataset.department !== department;
        });

        if (cityInput.selectedOptions[0]?.hidden) {
            cityInput.value = '';
        }

        refreshDiscountOrSummary();
    };

    departmentSelect?.addEventListener('change', syncCityOptions);
    cityInput?.addEventListener('input', refreshDiscountOrSummary);
    cityInput?.addEventListener('change', refreshDiscountOrSummary);

    syncCityOptions();
    syncPaymentChoice();
    syncCheckoutSummaryMode();
    updateSummary();
})();
