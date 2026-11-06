@extends('layouts.admin')

@section('meta_title', 'Integraciones | Vendly')

@section('content')
<div class="integration-page">
    <style>
        .integration-page { max-width:860px; margin:0 auto; padding-bottom:90px; }
        .integration-hero { display:flex; align-items:center; justify-content:space-between; gap:20px; margin-bottom:20px; }
        .integration-title { display:flex; align-items:center; gap:14px; }
        .integration-title h1 { margin:0; color:#172033; font-size:clamp(1.55rem, 3vw, 2rem); }
        .integration-title p { margin:6px 0 0; color:#718096; }
        .integration-back { display:grid; place-items:center; width:40px; height:40px; border:1px solid #dce3ec; border-radius:12px; color:#172033; text-decoration:none; }
        .integration-back svg { width:20px; height:20px; }
        .integration-card { padding:24px; background:#fff; border:1px solid #e4e9f0; border-radius:16px; box-shadow:0 8px 24px rgba(23,32,51,.05); }
        .integration-card h2 { margin:0; color:#172033; font-size:1.15rem; }
        .integration-card > p { margin:7px 0 22px; color:#718096; }
        .integration-field { display:grid; gap:8px; }
        .integration-field label { color:#263449; font-size:.9rem; font-weight:700; }
        .integration-field input { width:100%; min-height:46px; padding:10px 12px; border:1px solid #d7e0ea; border-radius:10px; box-sizing:border-box; color:#172033; font:inherit; }
        .integration-field small { color:#718096; font-size:.88rem; }
        .integration-actions { display:flex; justify-content:flex-end; margin-top:22px; }
        .integration-submit { min-height:46px; padding:0 22px; border:0; border-radius:10px; background:#008b5a; color:#fff; font-weight:800; cursor:pointer; }
        @media (max-width:760px) { .integration-page { padding:0 14px 90px; } .integration-hero { align-items:flex-start; } .integration-card { padding:18px; border-radius:12px; } .integration-submit { width:100%; } }
    </style>

    <div class="integration-hero">
        <div class="integration-title">
            <a href="{{ route('admin.configuration') }}" class="integration-back" aria-label="Volver a configuración">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="m12 19-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1>Integraciones</h1>
                <p>Conecta las herramientas que ayudan a medir tu tienda.</p>
            </div>
        </div>
        <span class="plan-pill">{{ $store->planLabel() }}</span>
    </div>

    @if (session('success'))<div class="flash success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="flash error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <form method="POST" action="{{ route('admin.integrations.meta-pixel.update') }}">
        @csrf
        <section class="integration-card">
            <h2>Meta Pixel</h2>
            <p>Mide visitas y eventos importantes de tu tienda para mejorar tus campañas.</p>
            @if(!\App\Models\Store::supportsMetaPixelColumn())
                <p class="integration-field"><small>Esta integración aún no está disponible en la base de datos.</small></p>
            @elseif($store->allowsMetaPixel())
                <div class="integration-field">
                    <label for="meta_pixel_id">Pixel ID</label>
                    <input id="meta_pixel_id" type="text" inputmode="numeric" pattern="[0-9]*" name="meta_pixel_id" value="{{ old('meta_pixel_id', $store->meta_pixel_id) }}" maxlength="50" placeholder="Ej: 123456789012345">
                    <small>Pega únicamente el ID numérico de tu Pixel de Meta.</small>
                </div>
                <div class="integration-actions"><button type="submit" class="integration-submit">Guardar Meta Pixel</button></div>
            @else
                <p class="integration-field"><small>Actualiza a Premium para activar Meta Pixel.</small></p>
            @endif
        </section>
    </form>
</div>
@endsection
