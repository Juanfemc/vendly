@extends('layouts.admin')

@section('content')
<div class="product-editor-page">
    <div class="product-editor-hero">
        <div class="product-editor-title">
            <a href="/admin/products" class="product-editor-back" aria-label="Volver a productos">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h2>Nuevo producto</h2>
                <p>Completa la informacion para agregar un nuevo item a tu catalogo.</p>
            </div>
        </div>

        @if(auth()->user()->isAdmin() || ($store?->allowsCategories() ?? true))
            <a href="/admin/categories" class="btn btn-secondary">Gestionar categorias</a>
        @endif
    </div>

    @if ($errors->any())
        <div class="flash error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @include('admin.products.partials.form', [
        'action' => url('/admin/products'),
        'store' => $store,
        'stores' => $stores ?? collect(),
        'categoryOptions' => $categoryOptions,
    ])
</div>

@include('admin.products.partials.form-scripts', ['aiStore' => $store])
@endsection
