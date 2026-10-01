@php
    $landingMetaPixelId = trim((string) config('services.meta.landing_pixel_id'));
    $metaPixelEvent = trim((string) ($event ?? ''));
    $metaPixelPayload = is_array($payload ?? null) ? $payload : [];
    $metaPixelEventKey = trim((string) ($eventKey ?? ''));
    $metaPixelStandardEvents = [
        'AddPaymentInfo',
        'CompleteRegistration',
        'Contact',
        'Lead',
        'PageView',
        'Schedule',
        'StartTrial',
        'Subscribe',
        'ViewContent',
    ];
@endphp

@if($landingMetaPixelId !== '' && $metaPixelEvent !== '' && preg_match('/^[0-9]+$/', $landingMetaPixelId) && preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $metaPixelEvent))
    <script>
        (function () {
            var eventName = @js($metaPixelEvent);
            var payload = @js($metaPixelPayload);
            var eventKey = @js($metaPixelEventKey);
            var isStandardEvent = @js(in_array($metaPixelEvent, $metaPixelStandardEvents, true));

            if (typeof window.fbq !== 'function') {
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window, document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');

                fbq('init', @js($landingMetaPixelId));
            }

            var track = function () {
                var options = eventKey ? { eventID: eventKey } : {};

                fbq(isStandardEvent ? 'track' : 'trackCustom', eventName, payload || {}, options);
            };

            if (! eventKey) {
                track();
                return;
            }

            var storageKey = 'vendly_landing_meta_pixel_' + eventKey;

            try {
                if (window.localStorage && window.localStorage.getItem(storageKey)) {
                    return;
                }
            } catch (error) {}

            track();

            try {
                if (window.localStorage) {
                    window.localStorage.setItem(storageKey, '1');
                }
            } catch (error) {}
        })();
    </script>
@endif
