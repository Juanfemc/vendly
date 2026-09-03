<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Support\StoreTemplateCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreTemplateController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $stores = $this->availableStores();
        $store = $this->storeForRequest($request, $stores);

        abort_unless($store, $stores->isEmpty() ? 403 : 404);

        $templates = StoreTemplateCatalog::all();

        return view('admin.templates.index', compact('store', 'stores', 'templates'));
    }

    public function apply(Request $request, string $template): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $allStores = $this->accessibleStores();
        $stores = $this->templateEligibleStores($allStores);
        $store = $this->storeForRequest($request, $allStores);
        $templateData = StoreTemplateCatalog::find($template);

        abort_unless($store, $allStores->isEmpty() ? 403 : 404);
        abort_unless($templateData, 404);

        if (! $store->allowsTemplates()) {
            $redirectStore = $stores->first();

            return redirect()
                ->route('admin.templates.index', $redirectStore ? ['store_id' => $redirectStore->id] : [])
                ->with('error', 'Esta tienda no puede usar plantillas con su plan actual.');
        }

        if (! ($templateData['available'] ?? false)) {
            return redirect()
                ->route('admin.templates.index', ['store_id' => $store->id])
                ->with('error', 'Esta plantilla estara disponible muy pronto.');
        }

        $store->forceFill([
            'business_type' => $templateData['business_type'],
        ])->save();

        return redirect()
            ->route('admin.templates.index', ['store_id' => $store->id])
            ->with('success', 'Plantilla '.$templateData['name'].' aplicada correctamente.');
    }

    private function availableStores(): Collection
    {
        return $this->templateEligibleStores($this->accessibleStores());
    }

    private function accessibleStores(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return Store::newCollection();
        }

        return $user->isAdmin()
            ? Store::orderBy('name')->get()
            : $user->stores()->orderBy('name')->get();
    }

    private function templateEligibleStores(Collection $stores): Collection
    {
        return $stores->filter(fn (Store $store) => $store->allowsTemplates())->values();
    }

    private function storeForRequest(Request $request, Collection $stores): ?Store
    {
        if ($request->filled('store_id')) {
            return $stores->firstWhere('id', $request->integer('store_id'));
        }

        $userStore = auth()->user()?->store;

        if ($userStore && $stores->contains('id', $userStore->id)) {
            return $userStore;
        }

        return $stores->first();
    }
}
