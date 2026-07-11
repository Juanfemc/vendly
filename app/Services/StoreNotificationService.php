<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductReview;
use App\Models\Store;
use App\Models\StoreNotification;

class StoreNotificationService
{
    public function notifyNewOrder(Order $order): void
    {
        if (! StoreNotification::supportsTable()) {
            return;
        }

        $order->loadMissing('store');
        $store = $order->store;

        if (! $store) {
            return;
        }

        $label = $store->isReservationStore() ? 'Nueva reserva' : 'Nuevo pedido';

        $this->create($store, StoreNotification::TYPE_NEW_ORDER, $label, sprintf(
            '%s #%s por %s - $%s',
            $store->isReservationStore() ? 'Reserva' : 'Pedido',
            $order->id,
            $order->customer_name,
            number_format((float) $order->total, 0, ',', '.')
        ), '/admin/orders', [
            'order_id' => $order->id,
            'total' => (float) $order->total,
            'customer_name' => $order->customer_name,
        ]);
    }

    public function notifyNewReview(ProductReview $review): void
    {
        if (! StoreNotification::supportsTable()) {
            return;
        }

        $review->loadMissing(['store', 'product']);
        $store = $review->store;

        if (! $store) {
            return;
        }

        $productName = $review->product?->name ?: 'Producto';
        $actionUrl = $review->product
            ? route('admin.products.edit', $review->product, false)
            : '/admin/products';

        $this->create($store, StoreNotification::TYPE_NEW_REVIEW, 'Nueva reseña', sprintf(
            '%s dejo %s estrellas en %s',
            $review->name,
            $review->rating,
            $productName
        ), $actionUrl, [
            'review_id' => $review->id,
            'product_id' => $review->product_id,
            'product_name' => $productName,
            'rating' => (int) $review->rating,
        ]);
    }

    private function create(Store $store, string $type, string $title, string $message, ?string $actionUrl = null, array $data = []): StoreNotification
    {
        return StoreNotification::create([
            'store_id' => $store->id,
            'user_id' => $store->user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }
}
