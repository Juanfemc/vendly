@extends('layouts.admin')

@section('content')
<div class="admin-brand-hero">
    <div class="admin-brand-copy">
        <span class="admin-brand-mark"><img src="{{ asset('images/vendly-logo.svg') }}" alt=""></span>
        <div>
            <p class="admin-brand-eyebrow">Confianza</p>
            <h2 class="admin-brand-title">Editar testimonio</h2>
            <p class="admin-brand-text">Mantén actualizada la historia que ayuda a convertir nuevos clientes.</p>
        </div>
    </div>
    <div class="admin-brand-actions">
        <a href="{{ route('admin.testimonials.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="{{ route('admin.testimonials.update', $testimonial) }}">
        @csrf
        @method('PUT')

        @include('admin.testimonials.form', ['testimonial' => $testimonial])

        <button class="btn">Actualizar testimonio</button>
    </form>
</div>
@endsection
