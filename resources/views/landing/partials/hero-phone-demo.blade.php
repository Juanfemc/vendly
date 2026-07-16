@php
    $heroVideoOptimizedPath = public_path('videos/landing-demo.webm');
    $heroVideoFallbackPath = public_path('videos/landing-demo-optimized.mp4');
    $heroVideoPosterPath = public_path('images/landing-demo-poster.jpg');
    $hasOptimizedHeroVideo = file_exists($heroVideoOptimizedPath);
    $hasFallbackHeroVideo = file_exists($heroVideoFallbackPath);
    $heroVideoSrc = $hasOptimizedHeroVideo ? asset('videos/landing-demo.webm') : null;
    $heroVideoFallbackSrc = $hasFallbackHeroVideo ? asset('videos/landing-demo-optimized.mp4') : null;
    $heroVideoPoster = file_exists($heroVideoPosterPath)
        ? asset('images/landing-demo-poster.jpg')
        : asset('images/vendly-whatsapp-dark.png');
@endphp

<div class="hero-phone-demo" aria-label="Vista previa de Vendly en movil">
    <div class="hero-phone-frame">
        <span class="hero-phone-notch" aria-hidden="true"></span>
        @if ($hasOptimizedHeroVideo)
            <video
                class="hero-phone-video"
                autoplay
                muted
                loop
                playsinline
                preload="none"
                poster="{{ $heroVideoPoster }}"
                data-hero-video-src="{{ $heroVideoSrc }}"
                @if ($hasFallbackHeroVideo)
                    data-hero-video-fallback-src="{{ $heroVideoFallbackSrc }}"
                @endif
            >
            </video>
        @else
            <div class="hero-phone-fallback">
                <img src="{{ $heroVideoPoster }}" alt="" loading="eager" decoding="async">
            </div>
        @endif
    </div>
</div>

@if ($hasOptimizedHeroVideo)
    @once
    <script>
        window.addEventListener('load', function () {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            var shouldDelayVideo = navigator.connection
                && (navigator.connection.saveData || /(^|-)2g$/.test(navigator.connection.effectiveType || ''));

            document.querySelectorAll('[data-hero-video-src]').forEach(function (video) {
                var loadVideo = function (src) {
                    if (!src || video.dataset.loaded === 'true') {
                        return;
                    }

                    video.src = src;
                    video.dataset.loaded = 'true';
                    video.load();

                    var playPromise = video.play();

                    if (playPromise && typeof playPromise.catch === 'function') {
                        playPromise.catch(function () {});
                    }
                };

                video.addEventListener('error', function () {
                    if (video.dataset.heroVideoFallbackSrc && video.src !== video.dataset.heroVideoFallbackSrc) {
                        video.dataset.loaded = 'false';
                        loadVideo(video.dataset.heroVideoFallbackSrc);
                    }
                }, { once: true });

                if (shouldDelayVideo && 'IntersectionObserver' in window) {
                    var observer = new IntersectionObserver(function (entries) {
                        if (!entries.some(function (entry) { return entry.isIntersecting; })) {
                            return;
                        }

                        observer.disconnect();
                        loadVideo(video.dataset.heroVideoSrc);
                    }, { rootMargin: '160px 0px' });

                    observer.observe(video);
                    return;
                }

                loadVideo(video.dataset.heroVideoSrc);
            });
        }, { once: true });
    </script>
    @endonce
@endif
