<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\AdminUpdateService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private AdminUpdateService $adminUpdateService,
        private CheckoutService $checkoutService,
    )
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $user = auth()->user();
        $statusOptions = Order::statusOptions();
        $statusFilterOptions = $statusOptions;
        unset($statusFilterOptions['pagado']);
        $selectedStatus = $request->query('status');
        $selectedStatus = array_key_exists((string) $selectedStatus, $statusFilterOptions)
            ? (string) $selectedStatus
            : null;
        $paymentStatusOptions = [
            'pending' => [
                'label' => 'Por pagar',
                'description' => 'Esperando que el cliente realice el pago',
            ],
            'partial' => [
                'label' => 'Pago parcial',
                'description' => 'Se recibió un pago pero no cubre el total',
            ],
            'approved' => [
                'label' => 'Pagado',
                'description' => 'Pago completo verificado',
            ],
            'refunded' => [
                'label' => 'Reembolsado',
                'description' => 'El dinero fue devuelto al cliente',
            ],
            'failed' => [
                'label' => 'Fallido',
                'description' => 'El pago fue rechazado o falló',
            ],
        ];
        $paymentManagementOptions = [
            Order::PAYMENT_STATUS_NOT_REQUIRED => 'No requerido',
            Order::PAYMENT_STATUS_PENDING => 'Por pagar',
            Order::PAYMENT_STATUS_PARTIAL => 'Pago parcial',
            Order::PAYMENT_STATUS_APPROVED => 'Pagado',
            Order::PAYMENT_STATUS_REFUNDED => 'Reembolsado',
            'failed' => 'Fallido',
        ];
        $selectedPaymentStatus = $request->query('payment_status');
        $selectedPaymentStatus = array_key_exists((string) $selectedPaymentStatus, $paymentStatusOptions)
            ? (string) $selectedPaymentStatus
            : null;
        $applyPaymentFilter = function ($query) use ($selectedPaymentStatus) {
            return $query->when($selectedPaymentStatus, function ($query) use ($selectedPaymentStatus) {
                if ($selectedPaymentStatus === 'failed') {
                    return $query->whereIn('payment_status', [
                        Order::PAYMENT_STATUS_REJECTED,
                        Order::PAYMENT_STATUS_CANCELLED,
                        Order::PAYMENT_STATUS_EXPIRED,
                    ]);
                }

                return $query->where('payment_status', $selectedPaymentStatus);
            });
        };

        if ($user?->isAdmin()) {
            $ordersQuery = Order::with(['items.product', 'store']);
            $totalOrders = (clone $ordersQuery)->count();
            $orders = $ordersQuery
                ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
                ->tap($applyPaymentFilter)
                ->latest()
                ->get();

            return view('admin.orders.index', compact('orders', 'statusOptions', 'statusFilterOptions', 'selectedStatus', 'paymentStatusOptions', 'paymentManagementOptions', 'selectedPaymentStatus', 'totalOrders'));
        }

        $store = $user?->store ?? $user?->stores()->first();

        if (! $store) {
            return view('admin.orders.index', [
                'orders' => collect(),
                'statusOptions' => $statusOptions,
                'statusFilterOptions' => $statusFilterOptions,
                'selectedStatus' => $selectedStatus,
                'paymentStatusOptions' => $paymentStatusOptions,
                'paymentManagementOptions' => $paymentManagementOptions,
                'selectedPaymentStatus' => $selectedPaymentStatus,
                'totalOrders' => 0,
            ]);
        }

        $ordersQuery = Order::with('items.product')->where('store_id', $store->id);
        $totalOrders = (clone $ordersQuery)->count();
        $orders = $ordersQuery
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
            ->tap($applyPaymentFilter)
            ->latest()
            ->get();

        return view('admin.orders.index', compact('orders', 'statusOptions', 'statusFilterOptions', 'selectedStatus', 'paymentStatusOptions', 'paymentManagementOptions', 'selectedPaymentStatus', 'totalOrders'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $user = auth()->user();
        $statusOptions = Order::statusOptions();
        unset($statusOptions['pagado']);

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys($statusOptions))],
        ]);

        if ($validated['status'] === 'devuelto' && ! in_array($order->status, ['pagado', 'enviado'], true)) {
            return back()->withErrors([
                'status' => 'Solo puedes marcar como devuelto un pedido pagado o enviado.',
            ]);
        }

        if (! $user?->isAdmin()) {
            $store = $user?->store ?? $user?->stores()->first();

            abort_if(! $store, 404);
            abort_unless((int) $order->store_id === (int) $store->id, 404);
        }

        $this->authorize('update', $order);
        $order->update([
            'status' => $validated['status'],
        ]);

        $this->adminUpdateService->record(
            'Pedido actualizado',
            'Pedido #' . $order->id . ' ahora esta ' . Order::statusOptions()[$validated['status']],
            'pedido',
            '/admin/orders'
        );

        return redirect('/admin/orders')->with('success', 'Estado del pedido actualizado.');
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'payment_status' => ['required', 'in:not_required,pending,partial,approved,refunded,failed'],
        ]);

        $paymentStatus = $validated['payment_status'] === 'failed'
            ? Order::PAYMENT_STATUS_REJECTED
            : $validated['payment_status'];

        if ($paymentStatus === Order::PAYMENT_STATUS_NOT_REQUIRED
            && in_array($order->payment_method, [Order::PAYMENT_METHOD_MERCADOPAGO, Order::PAYMENT_METHOD_WOMPI], true)) {
            return back()->withErrors([
                'payment' => 'Los pedidos con pago online deben conservar un estado de pago real.',
            ])->withInput();
        }

        $updates = ['payment_status' => $paymentStatus];

        if ($paymentStatus === Order::PAYMENT_STATUS_APPROVED) {
            $updates['status'] = 'pagado';
            $updates['paid_at'] = $order->paid_at ?: now();
        } elseif (in_array($paymentStatus, [
            Order::PAYMENT_STATUS_PARTIAL,
            Order::PAYMENT_STATUS_PENDING,
            Order::PAYMENT_STATUS_REFUNDED,
            Order::PAYMENT_STATUS_REJECTED,
        ], true) && $order->status === 'pagado') {
            $updates['status'] = 'pendiente';
            $updates['paid_at'] = null;
        }

        $order->update($updates);

        if (in_array($paymentStatus, [
            Order::PAYMENT_STATUS_REJECTED,
            Order::PAYMENT_STATUS_REFUNDED,
        ], true)) {
            $this->checkoutService->releaseStockForUnpaidOnlinePaymentOrder($order->refresh());
        }

        return redirect('/admin/orders')->with('success', 'Estado del pago actualizado.');
    }

    public function updateOrderAndPaymentStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(Order::statusOptions()))],
            'payment_status' => ['required', 'in:not_required,pending,partial,approved,refunded,failed'],
        ]);

        $paymentStatus = $validated['payment_status'] === 'failed'
            ? Order::PAYMENT_STATUS_REJECTED
            : $validated['payment_status'];

        if ($paymentStatus === Order::PAYMENT_STATUS_NOT_REQUIRED
            && in_array($order->payment_method, [Order::PAYMENT_METHOD_MERCADOPAGO, Order::PAYMENT_METHOD_WOMPI], true)) {
            return back()->withErrors([
                'payment' => 'Los pedidos con pago online deben conservar un estado de pago real.',
            ])->withInput();
        }

        if ($validated['status'] === 'devuelto' && ! in_array($order->status, ['pagado', 'enviado'], true)) {
            return back()->withErrors([
                'status' => 'Solo puedes marcar como devuelto un pedido pagado o enviado.',
            ])->withInput();
        }

        $updates = [
            'status' => $validated['status'],
            'payment_status' => $paymentStatus,
        ];

        if ($paymentStatus === Order::PAYMENT_STATUS_APPROVED) {
            $updates['status'] = 'pagado';
            $updates['paid_at'] = $order->paid_at ?: now();
        } elseif ($validated['status'] === 'pagado' && in_array($paymentStatus, [
            Order::PAYMENT_STATUS_PARTIAL,
            Order::PAYMENT_STATUS_PENDING,
            Order::PAYMENT_STATUS_REFUNDED,
            Order::PAYMENT_STATUS_REJECTED,
        ], true)) {
            $updates['status'] = 'pendiente';
            $updates['paid_at'] = null;
        } elseif ($order->status === 'pagado' && $validated['status'] !== 'devuelto') {
            $updates['paid_at'] = null;
        }

        DB::transaction(fn () => $order->update($updates));

        if (in_array($paymentStatus, [
            Order::PAYMENT_STATUS_REJECTED,
            Order::PAYMENT_STATUS_REFUNDED,
        ], true)) {
            $this->checkoutService->releaseStockForUnpaidOnlinePaymentOrder($order->refresh());
        }

        $this->adminUpdateService->record(
            'Pedido actualizado',
            'Pedido #' . $order->id . ' actualizo su estado y pago',
            'pedido',
            '/admin/orders'
        );

        return redirect('/admin/orders')->with('success', 'Cambios del pedido guardados.');
    }

    public function restoreStock(Order $order)
    {
        $this->authorize('update', $order);

        $restored = $this->checkoutService->restoreStockForManualOrder($order);

        if (! $restored) {
            return redirect('/admin/orders')->withErrors([
                'stock' => $this->stockRestorationError($order),
            ]);
        }

        $this->adminUpdateService->record(
            'Stock restaurado',
            'Pedido #' . $order->id . ' devolvio sus unidades al inventario',
            'pedido',
            '/admin/orders'
        );

        return redirect('/admin/orders')->with('success', 'Stock restaurado para el pedido #' . $order->id . '.');
    }

    private function stockRestorationError(Order $order): string
    {
        if (! Order::supportsStockRestorationColumns()) {
            return 'Ejecuta primero las migraciones para activar el control de restauración de stock.';
        }

        if ($order->stockRestored()) {
            return 'El stock de este pedido ya fue restaurado.';
        }

        if (! $order->isManualWhatsappOrder()) {
            return 'Solo puedes restaurar stock manualmente en pedidos por WhatsApp.';
        }

        return 'Solo puedes restaurar stock en pedidos pendientes o devueltos.';
    }

    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);

        $orderId = $order->id;
        $storeName = $order->store?->name;

        $order->delete();

        $this->adminUpdateService->record(
            'Pedido eliminado',
            'Pedido #' . $orderId . ($storeName ? ' en ' . $storeName : ''),
            'pedido',
            '/admin/orders'
        );

        return redirect('/admin/orders')->with('success', 'Pedido eliminado.');
    }
}
