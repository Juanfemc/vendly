<script>
    (() => {
        document.querySelectorAll('[data-rich-editor]').forEach((editor) => {
            const content = editor.querySelector('[data-rich-content]');
            const input = editor.querySelector('[data-rich-input]');

            if (!content || !input) {
                return;
            }

            editor.querySelectorAll('[data-command]').forEach((button) => {
                button.addEventListener('click', () => {
                    content.focus();
                    document.execCommand(button.dataset.command, false, null);
                    input.value = content.innerHTML;
                });
            });

            content.addEventListener('paste', (event) => {
                const clipboard = event.clipboardData || window.clipboardData;
                const text = clipboard?.getData('text/plain');

                if (!text) {
                    return;
                }

                event.preventDefault();
                document.execCommand('insertText', false, text);
                input.value = content.innerText;
            });

            content.addEventListener('input', () => {
                input.value = content.innerText;
            });

            content.closest('form')?.addEventListener('submit', () => {
                input.value = content.innerText;
            });
        });
    })();

    (() => {
        const toggle = document.querySelector('[data-offer-toggle]');
        const pricing = document.querySelector('[data-offer-pricing]');

        if (!toggle || !pricing) {
            return;
        }

        const syncOfferPricing = () => {
            pricing.hidden = !toggle.checked;
        };

        toggle.addEventListener('change', syncOfferPricing);
        syncOfferPricing();
    })();

    (() => {
        const preview = document.querySelector('[data-product-preview]');

        if (!preview) {
            return;
        }

        const fields = {
            name: document.querySelector('[data-product-preview-field="name"]'),
            category: document.querySelector('[data-product-preview-field="category"]'),
            price: document.querySelector('[data-product-preview-field="price"]'),
            priceBefore: document.querySelector('[data-product-preview-field="priceBefore"]'),
            stock: document.querySelector('[data-product-preview-field="stock"]'),
            soldout: document.querySelector('[data-product-preview-field="soldout"]'),
            offer: document.querySelector('[data-product-preview-field="offer"]'),
            badges: document.querySelector('[data-product-preview-field="badges"]'),
            image: document.querySelector('[data-product-preview-field="image"]'),
        };

        const nodes = {
            name: preview.querySelector('[data-product-preview-name]'),
            category: preview.querySelector('[data-product-preview-category]'),
            price: preview.querySelector('[data-product-preview-price]'),
            priceBefore: preview.querySelector('[data-product-preview-price-before]'),
            stock: preview.querySelector('[data-product-preview-stock]'),
            badges: preview.querySelector('[data-product-preview-badges]'),
            image: preview.querySelector('[data-product-preview-image]'),
            placeholder: preview.querySelector('[data-product-preview-placeholder]'),
        };

        const money = new Intl.NumberFormat('es-CO', {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0,
        });

        const formatMoney = (value) => {
            const number = Number.parseFloat(String(value || '').replace(',', '.'));
            return `$${money.format(Number.isFinite(number) ? number : 0)}`;
        };

        const selectedCategoryText = () => {
            const select = fields.category;
            const option = select?.selectedOptions?.[0];
            const text = option?.textContent?.trim() || '';

            return text !== '' && option?.value ? text : 'Categoria';
        };

        const syncBadges = () => {
            if (!nodes.badges) {
                return;
            }

            const items = [];

            if (fields.offer?.checked) {
                items.push('Oferta');
            }

            String(fields.badges?.value || '')
                .split(',')
                .map((badge) => badge.trim())
                .filter(Boolean)
                .slice(0, 3)
                .forEach((badge) => items.push(badge));

            nodes.badges.hidden = items.length === 0;
            nodes.badges.replaceChildren(...items.map((badge) => {
                const item = document.createElement('span');
                item.textContent = badge;

                return item;
            }));
        };

        const syncPreview = () => {
            const productName = fields.name?.value.trim() || 'Nombre del producto';
            const soldOut = Boolean(fields.soldout?.checked);
            const stock = fields.stock?.value;

            if (nodes.name) {
                nodes.name.textContent = productName;
            }

            if (nodes.category) {
                nodes.category.textContent = selectedCategoryText();
            }

            if (nodes.price) {
                nodes.price.textContent = formatMoney(fields.price?.value);
            }

            if (nodes.priceBefore) {
                const showBefore = Boolean(fields.offer?.checked && fields.priceBefore?.value);
                nodes.priceBefore.hidden = !showBefore;
                nodes.priceBefore.textContent = showBefore ? formatMoney(fields.priceBefore.value) : '';
            }

            if (nodes.stock) {
                nodes.stock.textContent = soldOut
                    ? 'Agotado'
                    : (stock !== undefined && stock !== '' ? `${stock} disponibles` : 'Disponible');
                nodes.stock.classList.toggle('is-sold-out', soldOut);
            }

            if (nodes.placeholder) {
                nodes.placeholder.textContent = productName.charAt(0).toUpperCase() || 'P';
            }

            syncBadges();
        };

        Object.values(fields).forEach((field) => {
            field?.addEventListener('input', syncPreview);
            field?.addEventListener('change', syncPreview);
        });

        fields.image?.addEventListener('change', () => {
            const file = fields.image.files?.[0];

            if (!file || !nodes.image) {
                syncPreview();
                return;
            }

            const imageUrl = URL.createObjectURL(file);
            nodes.image.src = imageUrl;
            nodes.image.alt = fields.name?.value || 'Producto';
            nodes.image.hidden = false;
            nodes.placeholder?.setAttribute('hidden', 'hidden');
        });

        syncPreview();
    })();
</script>

@if(($aiStore ?? null)?->allowsAiContent())
    <script src="{{ asset('js/admin-ai-content.js') }}?v={{ filemtime(public_path('js/admin-ai-content.js')) }}" defer></script>
@endif
