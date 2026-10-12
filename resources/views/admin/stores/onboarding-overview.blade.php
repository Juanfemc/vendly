@extends('layouts.admin')

@section('content')
@php
    $progressValue = min(100, max(0, (int) ($progress ?? 0)));
    $allComplete = $progressValue >= 100;
@endphp

<style>
    .setup-shell {
        padding: clamp(1rem, 3vw, 2.5rem);
        background: #f8fafc;
    }

    .setup-wrap {
        width: min(1120px, 100%);
        margin: 0 auto;
    }

    .setup-hero,
    .setup-task,
    .setup-complete-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        box-shadow: 0 18px 55px rgba(15, 23, 42, 0.06);
    }

    .setup-hero {
        padding: clamp(1.25rem, 4vw, 2.5rem);
    }

    .setup-hero-top {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
    }

    .setup-hero h1 {
        margin: .35rem 0 .5rem;
        color: #0f172a;
        font-size: clamp(2rem, 5vw, 3.2rem);
        line-height: 1;
        letter-spacing: 0;
    }

    .setup-hero p,
    .setup-task p,
    .setup-complete-card p {
        color: #64748b;
        line-height: 1.6;
        margin: 0;
    }

    .setup-pill {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .45rem .7rem;
        border-radius: 999px;
        background: #fff7ed;
        color: #f97316;
        font-weight: 900;
        font-size: .82rem;
    }

    .setup-progress {
        margin-top: 1.5rem;
    }

    .setup-progress-head {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        color: #475569;
        font-weight: 800;
        margin-bottom: .65rem;
    }

    .setup-progress-track {
        height: 12px;
        border-radius: 999px;
        background: #e5e7eb;
        overflow: hidden;
    }

    .setup-progress-track span {
        display: block;
        height: 100%;
        width: 0;
        border-radius: inherit;
        background: linear-gradient(90deg, #ff6b00, #14b8a6);
    }

    .setup-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        margin-top: 1.25rem;
    }

    .setup-button,
    .setup-link-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: .75rem 1rem;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #0f172a;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
    }

    .setup-button--primary,
    .setup-link-button--primary {
        border-color: #ff6b00;
        background: #ff6b00;
        color: #fff;
    }

    .setup-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .setup-task {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 1rem;
        padding: 1.25rem;
    }

    .setup-task-dot {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: #f1f5f9;
        color: #64748b;
        font-weight: 900;
    }

    .setup-task.is-complete .setup-task-dot {
        background: #dcfce7;
        color: #15803d;
    }

    .setup-task h2 {
        margin: 0 0 .35rem;
        color: #0f172a;
        font-size: 1.1rem;
        letter-spacing: 0;
    }

    .setup-task-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .65rem;
        margin-top: 1rem;
    }

    .setup-status {
        color: #64748b;
        font-weight: 800;
        font-size: .9rem;
    }

    .setup-task.is-complete .setup-status {
        color: #15803d;
    }

    .setup-complete-card {
        margin-top: 1rem;
        padding: 1.25rem;
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .setup-share-copy {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    @media (max-width: 840px) {
        .setup-shell {
            padding: .75rem;
        }

        .setup-hero-top,
        .setup-grid {
            display: grid;
            grid-template-columns: 1fr;
        }

        .setup-task {
            grid-template-columns: 1fr;
        }

        .setup-button,
        .setup-link-button {
            width: 100%;
        }
    }
</style>

<div class="setup-shell">
    <div class="setup-wrap">
        <section class="setup-hero" aria-labelledby="setup-title">
            <div class="setup-hero-top">
                <div>
                    <span class="setup-pill">Configuración inicial</span>
                    <h1 id="setup-title">Prepara {{ $store->name }}</h1>
                    <p>Completa lo esencial para que tu tienda se vea lista, reciba pedidos y puedas compartirla sin complicarte.</p>
                </div>
                <span class="setup-pill">{{ $completedCount }} de {{ $totalCount }} tareas</span>
            </div>

            <div class="setup-progress" aria-label="Progreso del onboarding">
                <div class="setup-progress-head">
                    <span>Progreso</span>
                    <span>{{ $progressValue }}%</span>
                </div>
                <div class="setup-progress-track" aria-hidden="true">
                    <span style="width: {{ $progressValue }}%"></span>
                </div>
            </div>

            <div class="setup-actions">
                <a class="setup-link-button" href="{{ route('dashboard') }}">Entrar al panel</a>
                <a class="setup-link-button" href="{{ route('admin.store.onboarding.choice') }}">Cambiar opción</a>
                <a class="setup-link-button" href="{{ $supportWhatsappUrl }}" target="_blank" rel="noopener noreferrer">Pedir ayuda</a>
            </div>
        </section>

        @if($allComplete)
            <section class="setup-complete-card" aria-live="polite">
                <h2>Tu tienda ya está lista para moverse</h2>
                <p>
                    Completaste las tareas principales. Desde ahora el onboarding deja de aparecer como elemento principal en el panel.
                </p>
            </section>
        @endif

        <div class="setup-grid">
            @foreach($tasks as $key => $task)
                @php
                    $isComplete = (bool) ($task['complete'] ?? false);
                    $isShare = $key === \App\Support\StoreOnboardingSteps::SETUP_SHARE;
                @endphp

                <article @class(['setup-task', 'is-complete' => $isComplete])>
                    <span class="setup-task-dot" aria-hidden="true">{{ $isComplete ? 'OK' : $loop->iteration }}</span>
                    <div>
                        <h2>{{ $task['title'] ?? $task['label'] }}</h2>
                        <p>{{ $task['description'] }}</p>

                        <div class="setup-task-footer">
                            @if($isShare)
                                <form method="POST" action="{{ route('admin.store.onboarding.share') }}" data-share-copy-form>
                                    @csrf
                                    <input type="hidden" name="channel" value="copy">
                                    <input class="setup-share-copy" value="{{ $storeUrl }}" data-share-copy-input readonly>
                                    <button class="setup-button setup-button--primary" type="submit" data-share-copy-button>Copiar enlace</button>
                                </form>
                                <form method="POST" action="{{ route('admin.store.onboarding.share') }}">
                                    @csrf
                                    <input type="hidden" name="channel" value="whatsapp">
                                    <button class="setup-button" type="submit">Compartir por WhatsApp</button>
                                </form>
                            @else
                                <a class="setup-link-button setup-link-button--primary" href="{{ $task['href'] }}">{{ $task['action'] }}</a>
                            @endif
                            <span class="setup-status">{{ $isComplete ? 'Completado' : 'Pendiente' }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.querySelector('[data-share-copy-form]')?.addEventListener('submit', async (event) => {
        const form = event.currentTarget;
        const input = form.querySelector('[data-share-copy-input]');

        if (!input || form.dataset.copyReady === '1') {
            return;
        }

        event.preventDefault();

        let copied = false;

        if (navigator.clipboard?.writeText) {
            try {
                await navigator.clipboard.writeText(input.value);
                copied = true;
            } catch (error) {
                copied = false;
            }
        }

        if (!copied) {
            input.style.position = 'fixed';
            input.style.left = '-9999px';
            input.style.opacity = '1';
            input.focus();
            input.select();

            try {
                copied = document.execCommand('copy');
            } catch (error) {
                copied = false;
            }

            input.style.position = '';
            input.style.left = '';
            input.style.opacity = '';
        }

        if (copied) {
            form.dataset.copyReady = '1';
            form.submit();
            return;
        }

        alert('No pudimos copiar el enlace automáticamente. Copia este enlace: ' + input.value);
    });
</script>
@endsection
