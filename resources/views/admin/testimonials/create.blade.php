@extends('layouts.admin')

@section('content')
<div class="admin-brand-hero">
    <div class="admin-brand-copy">
        <span class="admin-brand-mark"><img src="{{ asset('images/vendly-logo.svg') }}" alt=""></span>
        <div>
            <p class="admin-brand-eyebrow">Confianza</p>
            <h2 class="admin-brand-title">Crear testimonio</h2>
            <p class="admin-brand-text">Agrega una prueba social clara y creible para la landing de Vendly.</p>
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
    <form method="POST" action="{{ route('admin.testimonials.store') }}">
        @csrf

        @include('admin.testimonials.form', ['testimonial' => null])

        <button class="btn">Guardar testimonio</button>
    </form>
</div>
@endsection
