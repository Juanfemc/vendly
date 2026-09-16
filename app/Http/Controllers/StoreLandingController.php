<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreLanding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoreLandingController extends Controller
{
    public function editOwn(): View
    {
        return $this->editForStore($this->currentStoreOrFail(), false);
    }

    public function updateOwn(Request $request): RedirectResponse
    {
        return $this->updateForStore($request, $this->currentStoreOrFail(), false);
    }

    public function previewOwn()
    {
        return $this->previewForStore($this->currentStoreOrFail());
    }

    public function editStore(Store $store): View
    {
        return $this->editForStore($store, true);
    }

    public function updateStore(Request $request, Store $store): RedirectResponse
    {
        return $this->updateForStore($request, $store, true);
    }

    public function previewStore(Store $store)
    {
        return $this->previewForStore($store);
    }

    public function updateActivation(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('update', $store);
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_unless(StoreLanding::supportsTable(), 404);

        $landing = $store->singleProductLanding()->firstOrNew(['store_id' => $store->id]);
        $enable = $request->boolean('enabled');

        if ($enable && ! $landing->product_id) {
            return back()->with('error', 'Selecciona un producto principal antes de activar la landing.');
        }

        $landing->forceFill([
            'enabled_by_admin' => $enable,
            'enabled_at' => $enable ? now() : null,
            'enabled_by' => $enable ? auth()->id() : null,
        ])->save();

        return back()->with('success', $enable ? 'Landing activada publicamente.' : 'Landing desactivada.');
    }

    private function editForStore(Store $store, bool $adminMode): View
    {
        $this->authorize('update', $store);
        abort_unless(StoreLanding::supportsTable(), 404);

        $landing = $store->singleProductLanding()->firstOrNew(['store_id' => $store->id]);
        $products = $store->products()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'image', 'stock_quantity', 'is_sold_out']);

        return view('admin.store-landings.edit', compact('store', 'landing', 'products', 'adminMode'));
    }

    private function updateForStore(Request $request, Store $store, bool $adminMode): RedirectResponse
    {
        $this->authorize('update', $store);
        abort_unless(StoreLanding::supportsTable(), 404);

        $validated = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('store_id', $store->id)),
            ],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'headline' => ['nullable', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'video_title' => ['nullable', 'string', 'max:180'],
            'video_description' => ['nullable', 'string', 'max:1200'],
            'bundle_enabled' => ['nullable', 'boolean'],
            'bundle_quantity' => ['nullable', 'integer', 'min:2', 'max:10'],
            'bundle_badge' => ['nullable', 'string', 'max:80'],
            'bundle_shipping_text' => ['nullable', 'string', 'max:160'],
            'feature_titles' => ['nullable', 'array', 'max:8'],
            'feature_titles.*' => ['nullable', 'string', 'max:80'],
            'feature_descriptions' => ['nullable', 'array', 'max:8'],
            'feature_descriptions.*' => ['nullable', 'string', 'max:160'],
            'faq_questions' => ['nullable', 'array', 'max:8'],
            'faq_questions.*' => ['nullable', 'string', 'max:160'],
            'faq_answers' => ['nullable', 'array', 'max:8'],
            'faq_answers.*' => ['nullable', 'string', 'max:600'],
            'remove_review_images' => ['nullable', 'array'],
            'remove_review_images.*' => ['string'],
            'review_images' => ['nullable', 'array', 'max:12'],
            'review_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'request_activation' => ['nullable', 'boolean'],
        ]);

        $landing = $store->singleProductLanding()->firstOrNew(['store_id' => $store->id]);
        $reviewImages = $this->reviewImagesForRequest($request, $landing, $store);

        $landing->fill([
            'product_id' => $validated['product_id'] ?? null,
            'eyebrow' => $validated['eyebrow'] ?? null,
            'headline' => $validated['headline'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'description' => $validated['description'] ?? null,
            'video_url' => $validated['video_url'] ?? null,
            'video_title' => $validated['video_title'] ?? null,
            'video_description' => $validated['video_description'] ?? null,
            'bundle_enabled' => $request->boolean('bundle_enabled'),
            'bundle_quantity' => (int) ($validated['bundle_quantity'] ?? 2),
            'bundle_price' => null,
            'bundle_badge' => $validated['bundle_badge'] ?? null,
            'bundle_shipping_text' => $validated['bundle_shipping_text'] ?? null,
            'features' => $this->normalizedFeatures($request),
            'faqs' => $this->normalizedFaqs($request),
            'review_images' => $reviewImages,
        ]);

        if (! $adminMode && $request->boolean('request_activation')) {
            $landing->activation_requested_at = now();
        }

        $landing->save();

        return back()->with('success', $request->boolean('request_activation') && ! $adminMode
            ? 'Configuracion guardada y solicitud enviada.'
            : 'Configuracion de landing guardada.');
    }

    private function previewForStore(Store $store)
    {
        $this->authorize('update', $store);
        abort_unless(StoreLanding::supportsTable(), 404);

        $landing = $store->singleProductLanding()->with([
            'product' => fn ($query) => $query->withReviewStats()->with('approvedReviews'),
        ])->firstOrFail();

        abort_unless($landing->product, 404);

        return view('store_single_product_landing', [
            'store' => $store,
            'landing' => $landing,
            'product' => $landing->product,
            'previewMode' => true,
        ]);
    }

    private function currentStoreOrFail(): Store
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);

        return $store;
    }

    private function reviewImagesForRequest(Request $request, StoreLanding $landing, Store $store): array
    {
        $existing = collect($landing->reviewImages());
        $remove = collect($request->input('remove_review_images', []))
            ->map(fn ($path) => trim((string) $path))
            ->intersect($existing)
            ->values();

        if ($remove->isNotEmpty()) {
            $remove->each(fn ($path) => Storage::disk('public')->delete($path));
            $existing = $existing->reject(fn ($path) => $remove->contains($path))->values();
        }

        $remainingSlots = max(0, 12 - $existing->count());

        $uploaded = collect($request->file('review_images', []))
            ->filter()
            ->take($remainingSlots)
            ->map(fn ($image) => $image->store('store-landings/' . $store->id . '/reviews', 'public'));

        return $existing
            ->merge($uploaded)
            ->filter()
            ->take(12)
            ->values()
            ->all();
    }

    private function normalizedFeatures(Request $request): array
    {
        $titles = $request->input('feature_titles', []);
        $descriptions = $request->input('feature_descriptions', []);

        return collect($titles)
            ->map(fn ($title, $index) => [
                'title' => trim((string) $title),
                'description' => trim((string) ($descriptions[$index] ?? '')),
            ])
            ->filter(fn (array $feature) => $feature['title'] !== '' || $feature['description'] !== '')
            ->take(8)
            ->values()
            ->all();
    }

    private function normalizedFaqs(Request $request): array
    {
        $questions = $request->input('faq_questions', []);
        $answers = $request->input('faq_answers', []);

        return collect($questions)
            ->map(fn ($question, $index) => [
                'question' => trim((string) $question),
                'answer' => trim((string) ($answers[$index] ?? '')),
            ])
            ->filter(fn (array $faq) => $faq['question'] !== '' && $faq['answer'] !== '')
            ->take(8)
            ->values()
            ->all();
    }
}
