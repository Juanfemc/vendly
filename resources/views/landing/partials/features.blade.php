<section class="landing-section" id="funciones">
    <div class="landing-shell">
        <div class="section-head section-head--center">
            <h2>Todo lo que necesitas para vender más</h2>
        </div>

        <div class="feature-grid">
            @foreach($features as $feature)
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        @switch($feature['icon'])
                            @case('catalog')
                                <svg viewBox="0 0 24 24">
                                    <path d="M6 4h12a2 2 0 0 1 2 2v13a1 1 0 0 1-1.45.9L16 18.62l-2.55 1.28a1 1 0 0 1-.9 0L10 18.62 7.45 19.9A1 1 0 0 1 6 19V4Z"></path>
                                    <path d="M9 8h6M9 12h4"></path>
                                </svg>
                                @break
                            @case('whatsapp')
                                <svg viewBox="0 0 24 24">
                                    <path d="M5.2 18.8 6 15.9a7.3 7.3 0 1 1 2.8 2.8l-3.6.1Z"></path>
                                    <path d="M9.3 8.7c.2-.4.4-.4.7-.4h.5c.2 0 .4.1.5.4l.7 1.7c.1.2 0 .5-.1.6l-.5.6c.7 1.3 1.7 2.2 3 2.9l.7-.6c.2-.2.4-.2.7-.1l1.6.8c.3.1.4.3.4.6 0 .7-.6 1.8-1.5 1.9-1.7.2-5.7-1.5-7.3-5.3-.6-1.4-.5-2.4.1-3.1Z"></path>
                                </svg>
                                @break
                            @case('sparkles')
                                <svg viewBox="0 0 24 24">
                                    <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path>
                                    <path d="m19 14 .8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8L19 14Z"></path>
                                    <path d="m5 15 .6 1.4L7 17l-1.4.6L5 19l-.6-1.4L3 17l1.4-.6L5 15Z"></path>
                                </svg>
                                @break
                            @case('chart')
                                <svg viewBox="0 0 24 24">
                                    <path d="M4 19V5"></path>
                                    <path d="M4 19h16"></path>
                                    <path d="M8 16v-5"></path>
                                    <path d="M12 16V8"></path>
                                    <path d="M16 16v-3"></path>
                                    <path d="m7 8 4-3 4 4 4-6"></path>
                                </svg>
                                @break
                            @default
                                <svg viewBox="0 0 24 24">
                                    <path d="M14 5h5v5"></path>
                                    <path d="m19 5-7 7"></path>
                                    <path d="M10 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-4"></path>
                                </svg>
                        @endswitch
                    </span>
                    <h3>{{ $feature['title'] }}</h3>
                    <p>{{ $feature['copy'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
