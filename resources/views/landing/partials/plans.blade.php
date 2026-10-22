<section class="landing-section plans-section" id="planes">
    <div class="landing-shell">
        <div class="section-head section-head--center">
            <h2>Planes simples. Sin letras pequeñas.</h2>
            <p>Elige cómo quieres pagar y ahorra en los ciclos más largos.</p>
        </div>

        <div class="billing-cycle-picker" role="group" aria-label="Periodo de facturación" data-billing-config='@json(config('plans.cycles'))'>
            @foreach(config('plans.cycles') as $cycleKey => $cycle)
                <button
                    type="button"
                    class="billing-cycle-option {{ $cycleKey === 'monthly' ? 'is-active' : '' }}"
                    data-billing-cycle="{{ $cycleKey }}"
                    aria-pressed="{{ $cycleKey === 'monthly' ? 'true' : 'false' }}"
                >
                    <span>{{ $cycle['label'] }}</span>
                    @if($cycle['discount'] > 0)
                        <small>Ahorra {{ $cycle['discount'] }}%</small>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="plans-grid">
            @foreach($plans as $plan)
                <article class="plan-card {{ ($plan['name'] ?? '') === 'Pro' ? 'plan-card--featured' : '' }}">
                    <div class="plan-card-head">
                        <div>
                            <h3>{{ $plan['name'] }}</h3>
                            <span>{{ $plan['summary'] }}</span>
                        </div>

                        @if(! empty($plan['badge']))
                            <span class="plan-badge">{{ $plan['badge'] }}</span>
                        @endif
                    </div>

                    <div class="plan-price" data-plan-prices='@json($plan['prices'])'>
                        <strong class="plan-price-value">{{ $plan['price'] }}</strong>
                        <span class="plan-price-period">/mes</span>
                    </div>
                    <p class="plan-price-note" data-plan-price-note></p>

                    @if(! empty($plan['features']))
                        <ul class="plan-features">
                            @foreach($plan['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="plan-pending">Funciones por definir.</p>
                    @endif

                    <a href="{{ route('trial-signup.create') }}" class="btn {{ ($plan['name'] ?? '') === 'Pro' ? 'btn--primary' : 'btn--dark' }}" data-meta-event="Lead" data-plan-link data-plan-key="{{ $plan['key'] }}">
                        {{ $plan['button'] }}
                    </a>
                </article>
            @endforeach
        </div>
    </div>
</section>
