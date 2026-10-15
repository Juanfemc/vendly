@extends('layouts.admin')

@section('content')
<style>
    .setup-choice-shell {
        min-height: calc(100vh - 140px);
        display: grid;
        place-items: center;
        padding: clamp(1rem, 4vw, 3rem);
        background: #f8fafc;
    }

    .setup-choice-card {
        width: min(960px, 100%);
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.08);
        padding: clamp(1.25rem, 4vw, 3rem);
    }

    .setup-choice-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .75rem;
        border-radius: 999px;
        background: #fff7ed;
        color: #f97316;
        font-weight: 800;
        font-size: .82rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .setup-choice-card h1 {
        margin: 1rem 0 .5rem;
        color: #0f172a;
        font-size: clamp(2rem, 5vw, 3.5rem);
        line-height: 1;
        letter-spacing: 0;
    }

    .setup-choice-card p {
        margin: 0;
        color: #64748b;
        font-size: 1rem;
        line-height: 1.6;
    }

    .setup-choice-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 2rem;
    }

    .setup-choice-option {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: .85rem;
        padding: 1.25rem;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        background: #fff;
        text-align: left;
    }

    .setup-choice-option strong {
        color: #0f172a;
        font-size: 1.2rem;
    }

    .setup-choice-option span {
        color: #64748b;
        line-height: 1.5;
    }

    .setup-choice-option button,
    .setup-choice-link {
        margin-top: auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        padding: .8rem 1.1rem;
        border-radius: 999px;
        border: 0;
        background: #ff6b00;
        color: #fff;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
    }

    .setup-choice-option--light button {
        background: #0f172a;
    }

    .setup-choice-foot {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 1.5rem;
        color: #64748b;
        font-size: .92rem;
    }

    .setup-choice-foot a {
        color: #0f172a;
        font-weight: 800;
    }

    @media (max-width: 760px) {
        .setup-choice-shell {
            place-items: stretch;
            padding: .75rem;
        }

        .setup-choice-card {
            border-radius: 18px;
        }

        .setup-choice-grid,
        .setup-choice-foot {
            grid-template-columns: 1fr;
            flex-direction: column;
        }
    }
</style>

<div class="setup-choice-shell">
    <section class="setup-choice-card" aria-labelledby="setup-choice-title">
        <span class="setup-choice-eyebrow">Vendly Suite</span>
        <h1 id="setup-choice-title">¡Vamos a preparar tu tienda!</h1>
        <p>
            Tu tienda {{ $store->name }} ya existe. Puedes configurarla con una guía rápida o pedir ayuda para dejarla lista.
            El panel seguirá disponible aunque no termines todo hoy.
        </p>

        <div class="setup-choice-grid">
            <form method="POST" action="{{ route('admin.store.onboarding.choice.store') }}" class="setup-choice-option">
                @csrf
                <input type="hidden" name="choice" value="guided">
                <strong>Quiero crear mi tienda</strong>
                <span>Te mostramos cuatro pasos concretos: producto, apariencia, entregas y compartir.</span>
                <button type="submit">Iniciar guía</button>
            </form>

            <form method="POST" action="{{ route('admin.store.onboarding.choice.store') }}" class="setup-choice-option setup-choice-option--light">
                @csrf
                <input type="hidden" name="choice" value="assisted">
                <strong>Quiero que me ayuden</strong>
                <span>Abriremos WhatsApp con un mensaje listo para solicitar asistencia sobre tu tienda.</span>
                <button type="submit">Pedir ayuda por WhatsApp</button>
            </form>
        </div>

        <div class="setup-choice-foot">
            <span>Puedes cambiar esta decisión más adelante.</span>
            <span>
                <a href="{{ $storeUrl }}" target="_blank" rel="noopener noreferrer">Ver mi tienda</a>
                ·
                <a href="{{ route('dashboard') }}">Entrar al panel</a>
            </span>
        </div>
    </section>
</div>
@endsection
