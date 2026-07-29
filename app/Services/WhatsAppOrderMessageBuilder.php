<?php

namespace App\Services;

use App\Models\Order;

class WhatsAppOrderMessageBuilder
{
    public function url(Order $order): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $order->store?->whatsapp);

        if (! $phone) {
            return null;
        }

        return "https://wa.me/{$phone}?text=" . urlencode($this->message($order));
    }

    public function message(Order $order): string
    {
        $order->loadMissing(['items', 'store']);

        $isReservationStore = $order->store?->isReservationStore() ?? false;
        $subtotal = $order->items->sum(fn ($item) => (float) $item->price * (int) $item->quantity);

        $lines = [
            $isReservationStore ? 'Nueva reserva' : 'Nuevo pedido',
            'Tienda: ' . ($order->store?->name ?: 'Vendly'),
            'Pedido: #' . $order->id,
            '',
            'Cliente',
            'Nombre: ' . $order->customer_name,
            'WhatsApp: ' . $order->customer_phone,
        ];

        if ($order->customer_document) {
            $lines[] = 'Cedula: ' . $order->customer_document;
        }

        if ($order->customer_neighborhood) {
            $lines[] = 'Barrio: ' . $order->customer_neighborhood;
        }

        if ($order->customer_city) {
            $lines[] = 'Ciudad: ' . $order->customer_city;
        }

        if ($order->customer_address) {
            $lines[] = ($isReservationStore ? 'Referencia: ' : 'Direccion: ') . $order->customer_address;
        }

        if ($isReservationStore) {
            $lines[] = 'Fecha deseada: ' . optional($order->reservation_date)->format('Y-m-d');
            $lines[] = 'Hora deseada: ' . $order->reservation_time;

            if ($order->store?->business_hours) {
                $lines[] = 'Horario de atencion: ' . $order->store->business_hours;
            }
        }

        $lines[] = '';
        $lines[] = $isReservationStore ? 'Servicios' : 'Productos';

        foreach ($order->items as $item) {
            $variantText = collect([
                $item->size ? 'Talla: ' . $item->size : null,
                $item->color ? 'Color: ' . $item->color : null,
            ])->filter()->implode(' | ');

            $itemTotal = (float) $item->price * (int) $item->quantity;
            $line = ($isReservationStore ? 'Servicio: ' : '- ') . $item->displayName() . ' x' . (int) $item->quantity;

            if ($variantText !== '') {
                $line .= ' | ' . $variantText;
            }

            $lines[] = $line . ' | ' . $this->money($itemTotal);
        }

        $lines[] = '';
        $lines[] = 'Subtotal: ' . $this->money($subtotal);

        if ($order->shipping_method) {
            $lines[] = 'Envio: ' . $order->shipping_method . ' (' . $this->money((float) $order->shipping_cost) . ')';
        }

        if (Order::supportsDiscountColumns() && (float) ($order->discount_amount ?? 0) > 0) {
            $discountLine = 'Descuento';

            if ($order->discount_code) {
                $discountLine .= ' (' . $order->discount_code . ')';
            }

            $lines[] = $discountLine . ': -' . $this->money((float) $order->discount_amount);
        }

        $lines[] = 'Total: ' . $this->money((float) $order->total);

        if ($order->notes) {
            $lines[] = '';
            $lines[] = 'Notas: ' . trim((string) $order->notes);
        }

        if ($order->payment_method && $order->payment_method !== Order::PAYMENT_METHOD_WHATSAPP) {
            $lines[] = '';
            $lines[] = 'Pago: ' . $order->paymentMethodLabel() . ' - ' . $order->paymentStatusLabel();
        }

        return implode("\n", $lines);
    }

    private function money(float $amount): string
    {
        return '$' . number_format($amount, 0, ',', '.');
    }
}
