<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StoreNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->abortIfUnsupported();

        $type = $this->validType($request->query('type'));
        $status = in_array($request->query('status'), ['unread', 'read'], true)
            ? $request->query('status')
            : null;

        $notifications = $this->visibleNotifications()
            ->with('store')
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($status === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($status === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $unreadCount = $this->visibleNotifications()->whereNull('read_at')->count();
        $typeOptions = $this->typeOptions();

        return view('admin.notifications.index', compact('notifications', 'unreadCount', 'typeOptions', 'type', 'status'));
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $this->abortIfUnsupported();
        $notification = $this->visibleNotifications()->whereKey($notification)->firstOrFail();
        $this->authorizeVisibleNotification($notification);

        $notification->markAsRead();

        $redirectTo = $request->query('redirect');

        $redirectTo = $this->safeRedirectPath($redirectTo);

        if ($redirectTo) {
            return redirect($redirectTo);
        }

        return back()->with('success', 'Notificacion marcada como leida.');
    }

    public function readAll(): RedirectResponse
    {
        $this->abortIfUnsupported();

        $this->visibleNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Notificaciones marcadas como leidas.');
    }

    private function visibleNotifications()
    {
        $query = StoreNotification::query();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        $storeIds = $user?->stores()->pluck('stores.id') ?? collect();

        if ($user?->store_id) {
            $storeIds->push($user->store_id);
        }

        $storeIds = $storeIds->filter()->unique()->values();

        return $storeIds->isEmpty()
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('store_id', $storeIds);
    }

    private function authorizeVisibleNotification(StoreNotification $notification): void
    {
        $this->abortIfUnsupported();

        abort_unless(
            $this->visibleNotifications()->whereKey($notification->getKey())->exists(),
            404
        );
    }

    private function abortIfUnsupported(): void
    {
        abort_unless(StoreNotification::supportsTable(), 404);
    }

    private function safeRedirectPath(mixed $redirectTo): ?string
    {
        if (! is_string($redirectTo)
            || ! str_starts_with($redirectTo, '/')
            || str_starts_with($redirectTo, '//')
            || str_contains($redirectTo, '\\')
        ) {
            return null;
        }

        if (preg_match('#^/admin/products/(\d+)/edit$#', $redirectTo, $matches) === 1) {
            $product = Product::find((int) $matches[1]);

            return $product
                ? route('admin.products.edit', $product, false)
                : '/admin/products';
        }

        return $redirectTo;
    }

    private function validType(mixed $type): ?string
    {
        $type = is_string($type) ? $type : null;

        return array_key_exists((string) $type, $this->typeOptions()) ? $type : null;
    }

    private function typeOptions(): array
    {
        return [
            StoreNotification::TYPE_NEW_ORDER => 'Pedidos',
            StoreNotification::TYPE_NEW_REVIEW => 'Reseñas',
        ];
    }
}
