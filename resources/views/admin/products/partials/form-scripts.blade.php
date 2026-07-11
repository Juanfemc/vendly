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

            content.addEventListener('input', () => {
                input.value = content.innerHTML;
            });

            content.closest('form')?.addEventListener('submit', () => {
                input.value = content.innerHTML;
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
</script>

@if(($aiStore ?? null)?->allowsAiContent())
    <script src="{{ asset('js/admin-ai-content.js') }}?v={{ filemtime(public_path('js/admin-ai-content.js')) }}" defer></script>
@endif
