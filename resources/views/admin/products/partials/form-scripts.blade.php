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
        const modal = document.querySelector('[data-category-create-modal]');
        const openButton = document.querySelector('[data-open-category-create]');
        const closeButtons = document.querySelectorAll('[data-close-category-create]');
        const nameInput = document.querySelector('[data-category-create-name]');
        const storeSelect = document.querySelector('#store_id');
        const storeInput = document.querySelector('[data-category-create-store]');
        const submitButton = document.querySelector('[data-submit-category-create]');
        const select = document.querySelector('#category_select');
        const feedback = document.querySelector('[data-category-create-feedback]');

        if (!modal || !openButton || !nameInput || !submitButton || !select) {
            return;
        }

        const setFeedback = (message = '', isError = true) => {
            feedback.textContent = message;
            feedback.hidden = !message;
            feedback.style.color = isError ? '#b42318' : '#007c68';
        };

        const closeModal = () => {
            modal.hidden = true;
            setFeedback();
            submitButton.disabled = false;
            submitButton.textContent = 'Crear categoría';
        };

        const openModal = () => {
            if (storeInput && storeSelect) {
                storeInput.value = storeSelect.value;
            }

            modal.hidden = false;

            if (storeSelect && !storeSelect.value) {
                setFeedback('Selecciona primero la tienda del producto.');
                storeSelect.focus();
                return;
            }

            nameInput.focus();
        };

        openButton.addEventListener('click', openModal);
        closeButtons.forEach((button) => button.addEventListener('click', closeModal));
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });

        storeSelect?.addEventListener('change', () => {
            if (storeInput) {
                storeInput.value = storeSelect.value;
            }
        });

        const createCategory = async () => {
            const name = nameInput.value.trim();
            if (!name) {
                setFeedback('Escribe un nombre para la categoría.');
                nameInput.focus();
                return;
            }

            const token = document.querySelector('input[name="_token"]')?.value;
            const payload = new FormData();
            payload.append('_token', token || '');
            payload.append('name', name);
            payload.append('is_active', '1');

            if (storeInput?.value) {
                payload.append('store_id', storeInput.value);
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Creando...';
            setFeedback();

            try {
                const response = await fetch(@json(route('admin.categories.store')), {
                    method: 'POST',
                    body: payload,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const validationMessage = Object.values(result.errors || {}).flat()[0];
                    throw new Error(validationMessage || result.message || 'No se pudo crear la categoría.');
                }

                const option = new Option(result.category.label, result.category.value, true, true);
                select.append(option);
                select.value = result.category.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                closeModal();
            } catch (error) {
                submitButton.disabled = false;
                submitButton.textContent = 'Crear categoría';
                setFeedback(error.message || 'No se pudo crear la categoría.');
            }
        };

        submitButton.addEventListener('click', createCategory);
        nameInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                createCategory();
            }
        });
    })();
</script>

@if(($aiStore ?? null)?->allowsAiContent())
    <script src="{{ asset('js/admin-ai-content.js') }}?v={{ filemtime(public_path('js/admin-ai-content.js')) }}" defer></script>
@endif
