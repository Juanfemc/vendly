@php
    $pixelStore = $store ?? null;
    $metaPixelId = $pixelStore?->allowsMetaPixel() ? trim((string) $pixelStore->meta_pixel_id) : '';
@endphp

@if($metaPixelId !== '' && preg_match('/^[0-9]+$/', $metaPixelId))
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @js($metaPixelId));
        fbq('track', 'PageView');

        window.vendlyMetaPixelTrack = function (eventName, payload) {
            if (! eventName || typeof fbq !== 'function') {
                return;
            }

            var standardEvents = [
                'AddPaymentInfo',
                'AddToCart',
                'CompleteRegistration',
                'Contact',
                'InitiateCheckout',
                'Lead',
                'PageView',
                'Purchase',
                'Schedule',
                'Search',
                'StartTrial',
                'Subscribe',
                'ViewContent'
            ];
            var method = standardEvents.indexOf(eventName) !== -1 ? 'track' : 'trackCustom';

            try {
                fbq(method, eventName, payload || {});
            } catch (error) {
                if (window.console && typeof window.console.warn === 'function') {
                    window.console.warn('Meta Pixel event failed', error);
                }
            }
        };

        window.vendlyMetaPixelTrackOnce = function (eventKey, eventName, payload) {
            if (! eventKey) {
                window.vendlyMetaPixelTrack(eventName, payload);
                return;
            }

            var storageKey = 'vendly_meta_pixel_' + eventKey;
            var shouldTrack = true;

            try {
                if (window.localStorage && window.localStorage.getItem(storageKey)) {
                    return;
                }
            } catch (error) {
                shouldTrack = true;
            }

            if (! shouldTrack) {
                return;
            }

            window.vendlyMetaPixelTrack(eventName, payload);

            try {
                if (window.localStorage) {
                    window.localStorage.setItem(storageKey, '1');
                }
            } catch (error) {
                // Some browsers block localStorage; the event has already been sent once.
            }
        };

        document.addEventListener('submit', function (event) {
            var form = event.target.closest('[data-storefront-search], [data-fashion-search], .product-search, form[role="search"]');

            if (! form) {
                return;
            }

            var input = form.querySelector('input[type="search"], input[name="q"]');
            var query = input ? input.value.trim() : '';

            if (query !== '') {
                window.vendlyMetaPixelTrack('Search', { search_string: query });
            }
        });

        document.addEventListener('click', function (event) {
            var searchLink = event.target.closest('[data-storefront-search-item], [data-fashion-search-item], [data-storefront-search-all], [data-fashion-search-all]');

            if (searchLink) {
                var searchForm = searchLink.closest('[data-storefront-search], [data-fashion-search]');
                var searchInput = searchForm ? searchForm.querySelector('input[type="search"], input[name="q"]') : null;
                var searchQuery = searchInput ? searchInput.value.trim() : '';

                if (searchQuery !== '') {
                    window.vendlyMetaPixelTrack('Search', { search_string: searchQuery });
                }
            }

            var link = event.target.closest('a[href*="wa.me/"], a[href*="api.whatsapp.com/send"]');

            if (! link) {
                return;
            }

            var href = link.getAttribute('href') || '';
            var isDirectWhatsAppContact = /wa\.me\/\d+/i.test(href) || /[?&]phone=\d+/i.test(href);

            if (isDirectWhatsAppContact) {
                window.vendlyMetaPixelTrack('Contact', {
                    content_name: 'WhatsApp',
                    content_category: 'store_contact'
                });
            }
        });
    </script>
@endif
