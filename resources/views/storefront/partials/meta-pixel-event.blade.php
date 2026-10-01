@php
    $metaPixelEventName = trim((string) ($event ?? ''));
    $metaPixelEventIsValid = $metaPixelEventName !== '' && preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $metaPixelEventName);
    $metaPixelPayload = is_array($payload ?? null) ? $payload : [];
    $metaPixelEventKey = trim((string) ($eventKey ?? ''));
@endphp

@if($metaPixelEventIsValid)
    <script>
        (function () {
            var run = function () {
                if (@js($metaPixelEventKey) !== '' && typeof window.vendlyMetaPixelTrackOnce === 'function') {
                    window.vendlyMetaPixelTrackOnce(@js($metaPixelEventKey), @js($metaPixelEventName), @js($metaPixelPayload));
                    return;
                }

                if (typeof window.vendlyMetaPixelTrack === 'function') {
                    window.vendlyMetaPixelTrack(@js($metaPixelEventName), @js($metaPixelPayload));
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', run, { once: true });
            } else {
                run();
            }
        })();
    </script>
@endif
