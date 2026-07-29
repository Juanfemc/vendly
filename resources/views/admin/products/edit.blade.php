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
                <h2>Editar producto</h2>
                <p>Actualiza la información, imágenes, precio y disponibilidad.</p>
            </div>
        </div>

        @if(auth()->user()->isAdmin() || ($product->store?->allowsCategories() ?? true))
            <a href="/admin/categories" class="btn btn-secondary">Gestionar categorías</a>
        @endif
    </div>

    @if ($errors->any())
        <div class="flash error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif

    @include('admin.products.partials.form', [
        'action' => route('admin.products.update', $product),
        'product' => $product,
        'store' => $product->store,
        'stores' => $stores ?? collect(),
        'categoryOptions' => $categoryOptions,
    ])

    @if(($product->store?->allowsProductReviews() ?? false))
        <div class="list-card product-review-panel">
            <div class="product-review-panel__head">
                <div>
                    <span class="product-review-panel__eyebrow">Moderacion</span>
                    <h3>Resenas del producto</h3>
                    <p>Aprueba las reseñas antes de que aparezcan en la tienda.</p>
                </div>
                <div class="product-review-panel__summary">
                    <strong>{{ ($productReviews ?? collect())->where('is_approved', false)->count() }}</strong>
                    <span>Pendientes</span>
                </div>
            </div>

            <div class="product-review-admin-list">
            @forelse(($productReviews ?? collect()) as $review)
                <article class="product-review-admin-card {{ $review->is_approved ? 'is-approved' : 'is-pending' }}">
                    <div class="resource-card__main">
                        <div class="product-review-admin-card__head">
                            <div>
                                <h4>{{ $review->name }}</h4>
                                <p>{{ number_format((float) $review->rating, 1) }} estrellas</p>
                            </div>
                            <div class="resource-badges">
                                @if($review->is_approved)
                                    <span class="resource-badge resource-badge--active">Aprobada</span>
                                @else
                                    <span class="resource-badge resource-badge--warning">Pendiente</span>
                                @endif
                            </div>
                        </div>
                        @if($review->comment)
                            <p class="product-review-admin-card__comment">{{ $review->comment }}</p>
                        @else
                            <p class="product-review-admin-card__comment is-empty">Sin comentario adicional.</p>
                        @endif
                    </div>

                    <div class="resource-actions">
                        @unless($review->is_approved)
                            <form method="POST" action="{{ route('admin.product-reviews.approve', $review) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-success">Aprobar</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('admin.product-reviews.destroy', $review) }}" data-confirm-delete data-confirm-message="Seguro que quieres eliminar esta reseña?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="panel-empty" style="margin:0;">
                    <h3>No hay reseñas todavía</h3>
                    <p>Cuando un cliente escriba una reseña, aparecerá aquí para aprobarla.</p>
                </div>
            @endforelse
            </div>
        </div>
    @endif
</div>

<style>
    .product-review-panel {
        margin-top: 22px;
        padding: 0;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .product-review-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        min-width: 0;
        padding: 22px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    }

    .product-review-panel__head > div {
        min-width: 0;
    }

    .product-review-panel__eyebrow {
        display: inline-flex;
        margin-bottom: 8px;
        color: #ff6a00;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .product-review-panel__head h3 {
        margin: 0;
        color: #111827;
        font-size: 22px;
    }

    .product-review-panel__head p {
        margin: 6px 0 0;
        color: #6b7280;
        line-height: 1.5;
    }

    .product-review-panel__summary {
        min-width: 112px;
        min-height: 82px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: #ffffff;
        color: #111827;
        text-align: center;
    }

    .product-review-panel__summary strong {
        font-size: 28px;
        line-height: 1;
    }

    .product-review-panel__summary span {
        color: #6b7280;
        font-size: 12px;
        font-weight: 800;
    }

    .product-review-admin-list {
        display: grid;
        gap: 12px;
        min-width: 0;
        padding: 18px;
    }

    .product-review-admin-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: start;
        min-width: 0;
        border: 1px solid #e5e7eb;
        border-left: 4px solid #f59e0b;
        border-radius: 14px;
        padding: 16px;
        background: #ffffff;
    }

    .product-review-admin-card .resource-actions {
        min-width: 0;
    }

    .product-review-admin-card.is-approved {
        border-left-color: #16a34a;
    }

    .product-review-admin-card__head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        min-width: 0;
    }

    .product-review-admin-card__head > div {
        min-width: 0;
    }

    .product-review-admin-card h4 {
        margin: 0;
        color: #111827;
        font-size: 16px;
    }

    .product-review-admin-card__head p {
        margin: 5px 0 0;
        color: #f59e0b;
        font-size: 13px;
        font-weight: 900;
    }

    .product-review-admin-card__comment {
        margin: 12px 0 0;
        color: #4b5563;
        line-height: 1.65;
        overflow-wrap: anywhere;
    }

    .product-review-admin-card__comment.is-empty {
        color: #9ca3af;
        font-style: italic;
    }

    @media (max-width: 900px) {
        .product-review-panel__head,
        .product-review-admin-card,
        .product-review-admin-card__head {
            grid-template-columns: 1fr;
            display: grid;
        }

        .product-review-panel__summary {
            width: 100%;
            min-height: 68px;
        }

        .product-review-admin-card .resource-actions {
            width: 100%;
        }
    }
</style>

@include('admin.products.partials.form-scripts', ['aiStore' => $product->store])
@endsection
