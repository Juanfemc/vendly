@if($store?->requiresTermsAcceptance())
    @php
        $termsMode = $mode ?? 'default';
        $termsTitle = $store->termsAcceptanceTitle();
        $termsCopy = trim(strip_tags((string) $store->terms_content));
        $termsLink = trim((string) $store->terms_url);
        $termsModalId = 'checkoutTermsModal-' . ($store->id ?? 'store');
    @endphp

    <div class="checkout-terms checkout-terms--{{ $termsMode }}">
        <label class="checkout-terms-label">
            <input
                type="checkbox"
                name="terms_acceptance"
                value="1"
                @checked(old('terms_acceptance'))
                required
            >
            <span>
                <strong>{{ $termsTitle }}</strong>
            </span>
        </label>

        @if($termsCopy !== '' || $termsLink !== '')
            <div class="checkout-terms-actions">
                @if($termsCopy !== '')
                    <button type="button" class="checkout-terms-link" data-terms-open="{{ $termsModalId }}">Ver terminos</button>
                @endif
                @if($termsLink !== '')
                    <a href="{{ $termsLink }}" target="_blank" rel="noopener noreferrer">{{ $termsCopy !== '' ? 'Ver pagina completa' : 'Ver terminos' }}</a>
                @endif
            </div>
        @endif

        @if($termsCopy !== '')
            <p class="checkout-terms-copy">{{ \Illuminate\Support\Str::limit($termsCopy, 220) }}</p>
        @endif

        @error('terms_acceptance')
            <p class="checkout-terms-error">{{ $message }}</p>
        @enderror
    </div>

    @if($termsCopy !== '')
        <div class="checkout-terms-modal" id="{{ $termsModalId }}" role="dialog" aria-modal="true" aria-labelledby="{{ $termsModalId }}Title" hidden>
            <div class="checkout-terms-modal-backdrop" data-terms-close></div>
            <section class="checkout-terms-modal-panel" tabindex="-1">
                <div class="checkout-terms-modal-head">
                    <div>
                        <p>Terminos de la tienda</p>
                        <h2 id="{{ $termsModalId }}Title">{{ $termsTitle }}</h2>
                    </div>
                    <button type="button" class="checkout-terms-modal-close" data-terms-close aria-label="Cerrar terminos">&times;</button>
                </div>
                <div class="checkout-terms-modal-body">
                    {!! nl2br(e($termsCopy)) !!}
                </div>
                <div class="checkout-terms-modal-footer">
                    @if($termsLink !== '')
                        <a href="{{ $termsLink }}" target="_blank" rel="noopener noreferrer">Abrir pagina completa</a>
                    @endif
                    <button type="button" data-terms-close>Entendido</button>
                </div>
            </section>
        </div>
    @endif
@endif
