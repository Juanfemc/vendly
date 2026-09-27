@if($store ?? null)
    <style>
        :root {
            {{ $store->storefrontCssVariables($brandTheme, $responsiveProductColumns ?? 2) }}
        }

        @if($store->isTechnologyStore())
            html,
            body {
                scrollbar-color: transparent transparent;
                scrollbar-width: none;
            }

            html::-webkit-scrollbar,
            body::-webkit-scrollbar {
                width: 0;
                height: 0;
            }

            html::-webkit-scrollbar-track,
            body::-webkit-scrollbar-track {
                background-color: #eef3f8;
            }

            html::-webkit-scrollbar-thumb,
            body::-webkit-scrollbar-thumb {
                background-color: {{ $brandTheme->color }};
                border: 3px solid #eef3f8;
                border-radius: 999px;
            }

            html::-webkit-scrollbar-thumb:hover,
            body::-webkit-scrollbar-thumb:hover {
                background-color: color-mix(in srgb, {{ $brandTheme->color }} 82%, #0f172a);
            }
        @endif
    </style>
    @if($store->isTechnologyStore())
        <script>
            document.documentElement.classList.add('storefront-root--technology');
        </script>
    @endif
@endif
