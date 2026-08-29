<?php

namespace App\Http\Controllers;

use App\Exceptions\TrialPhoneHashConfigurationException;
use App\Http\Requests\StoreOnboardingRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\TrialSignupClaim;
use App\Services\AdminUpdateService;
use App\Services\ProductContentService;
use App\Services\ProductFileService;
use App\Services\StoreFileService;
use App\Services\StorefrontUrlService;
use App\Services\TrialPhoneHashService;
use App\Services\WhatsAppPhoneVerificationService;
use App\Support\StoreOnboardingSteps;
use App\Support\StoreTemplateCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StoreOnboardingController extends Controller
{
    public function __construct(
        private StoreFileService $storeFileService,
        private ProductFileService $productFileService,
        private ProductContentService $productContentService,
        private StorefrontUrlService $storefrontUrls,
        private AdminUpdateService $adminUpdateService,
        private WhatsAppPhoneVerificationService $phoneVerification,
        private TrialPhoneHashService $trialPhoneHashes,
    ) {
    }

    public function edit(Request $request): View
    {
        $store = $this->currentStore();
        $steps = $this->availableSteps($store);
        $currentStep = $this->currentStep($store, $steps, (string) $request->query('step', ''));
        $stepIndex = array_search($currentStep, array_keys($steps), true);
        $stepIndex = $stepIndex === false ? 0 : $stepIndex;

        return view('admin.stores.onboarding', [
            'store' => $store,
            'storeUrl' => $this->storefrontUrls->publicHome($store),
            'checklist' => $store->onboardingChecklist(),
            'progress' => $store->onboardingProgress(),
            'steps' => $steps,
            'currentStep' => $currentStep,
            'currentStepIndex' => $stepIndex,
            'availableTemplates' => $this->availableTemplates($store),
            'categoryOptions' => $this->categoryOptions($store),
            'paymentAccounts' => $this->paymentAccounts($store),
        ]);
    }

    public function update(StoreOnboardingRequest $request): RedirectResponse
    {
        $store = $this->currentStore();
        $steps = $this->availableSteps($store);
        $requestedStep = $request->stepKey();

        if (! array_key_exists($requestedStep, $steps)) {
            $step = $this->currentStep($store, $steps, '');

            if (Store::supportsOnboardingStateColumns()) {
                $store->forceFill(['onboarding_last_step' => $step])->save();
            }

            return redirect()
                ->route('admin.store.onboarding', ['step' => $step])
                ->with('error', 'Ese paso no está disponible para esta tienda.');
        }

        $step = $requestedStep;
        $previousWhatsApp = (string) $store->whatsapp;

        $data = $request->onboardingData($step, $store);

        if ($step === 'identity') {
            $template = StoreTemplateCatalog::find((string) $request->input('template_key'));

            if ($store->allowsTemplates() && ($template['available'] ?? false)) {
                $data['business_type'] = $template['business_type'];
            }

            $data = $this->storeFileService->replaceUploadedImages($store, $request, $data);
        }

        if ($step === 'product') {
            $this->createOnboardingProduct($request, $store);
        }

        if (array_key_exists('whatsapp', $data) && $previousWhatsApp !== (string) $data['whatsapp']) {
            $this->ensurePhoneIsAvailableForStore((string) $data['whatsapp'], $store);
            $data['whatsapp_verified_at'] = null;
        }

        if ($data !== []) {
            $store->update($data);
        }

        $store = $store->refresh();
        $intent = (string) $request->input('intent', 'continue');
        $nextStep = $intent === 'back'
            ? $this->previousStep($steps, $step)
            : $this->nextStep($steps, $step);

        $this->adminUpdateService->record(
            'Onboarding actualizado',
            $store->name,
            'tienda',
            route('admin.store.onboarding')
        );

        if ($intent === 'exit') {
            if (Store::supportsOnboardingStateColumns()) {
                $store->forceFill([
                    'onboarding_last_step' => $step,
                ])->save();
            }

            return redirect()
                ->route('dashboard')
                ->with('success', 'Progreso guardado. Puedes continuar la configuración cuando quieras.');
        }

        if (Store::supportsOnboardingStateColumns() && $intent !== 'finish') {
            $store->forceFill([
                'onboarding_last_step' => $nextStep ?: $step,
            ])->save();
        }

        if ($intent === 'finish' || ! $nextStep) {
            $blockingStep = $this->firstIncompleteRequiredStep($store->refresh(), $steps);

            if ($blockingStep) {
                if (Store::supportsOnboardingStateColumns()) {
                    $store->forceFill(['onboarding_last_step' => $blockingStep])->save();
                }

                return redirect()
                    ->route('admin.store.onboarding', ['step' => $blockingStep])
                    ->with('error', 'Completa primero la información necesaria para finalizar.');
            }

            if (Store::supportsOnboardingStateColumns()) {
                $store->forceFill([
                    'onboarding_completed_at' => $store->onboarding_completed_at ?: now(),
                    'onboarding_last_step' => null,
                ])->save();
            }

            return redirect()
                ->route('dashboard')
                ->with('success', 'Configuración finalizada. Tu tienda está lista para compartir.');
        }

        return redirect()
            ->route('admin.store.onboarding', ['step' => $nextStep])
            ->with('success', 'Paso guardado.');
    }

    public function sendWhatsAppVerificationCode(Request $request): JsonResponse
    {
        $store = $this->currentStore();
        $phone = $this->normalizedPhone((string) $request->input('whatsapp', $store->whatsapp));

        validator(['whatsapp' => $phone], [
            'whatsapp' => ['required', 'regex:/^573\d{9}$/'],
        ])->validate();

        if (! $this->phoneVerification->isRequired()) {
            $this->ensurePhoneIsAvailableForStore($phone, $store);

            $store->forceFill([
                'whatsapp' => $phone,
                'whatsapp_verified_at' => now(),
            ])->save();

            return response()->json(['message' => 'WhatsApp marcado como verificado en este entorno.']);
        }

        $phoneHash = $this->trialPhoneHash($phone);
        $token = $this->phoneVerification->send(
            $phone,
            fn () => $this->phoneIsAvailableForStore($phoneHash, $store),
            (string) ($request->ip() ?: 'unknown'),
        );

        return response()->json([
            'message' => 'Te enviamos un codigo por WhatsApp.',
            'verification_token' => $token,
        ]);
    }

    public function verifyWhatsApp(Request $request): JsonResponse
    {
        $store = $this->currentStore();
        $phone = $this->normalizedPhone((string) $request->input('whatsapp', $store->whatsapp));

        $validated = validator([
            'whatsapp' => $phone,
            'whatsapp_verification_code' => $request->input('whatsapp_verification_code'),
            'whatsapp_verification_token' => $request->input('whatsapp_verification_token'),
        ], [
            'whatsapp' => ['required', 'regex:/^573\d{9}$/'],
            'whatsapp_verification_code' => ['required', 'digits:6'],
            'whatsapp_verification_token' => ['required', 'string', 'size:64'],
        ])->validate();

        $this->phoneVerification->runVerified(
            $phone,
            $validated['whatsapp_verification_token'],
            $validated['whatsapp_verification_code'],
            function () use ($store, $phone) {
                DB::transaction(function () use ($store, $phone) {
                    $phoneHash = $this->trialPhoneHash($phone);
                    $claim = TrialSignupClaim::where('phone_hash', $phoneHash)->first();

                    if ($claim && (int) $claim->store_id !== (int) $store->id) {
                        throw ValidationException::withMessages([
                            'whatsapp' => 'Este numero ya utilizo su prueba gratis.',
                        ]);
                    }

                    TrialSignupClaim::updateOrCreate(
                        ['phone_hash' => $phoneHash],
                        [
                            'store_id' => $store->id,
                            'source' => 'trial_signup',
                            'claimed_at' => now(),
                        ],
                    );

                    $store->forceFill([
                        'whatsapp' => $phone,
                        'whatsapp_verified_at' => now(),
                    ])->save();
                });
            },
        );

        return response()->json(['message' => 'WhatsApp verificado correctamente.']);
    }

    private function currentStore(): Store
    {
        $store = auth()->user()?->store ?? auth()->user()?->stores()->first();

        abort_if(! $store, 404);
        $this->authorize('update', $store);

        return $store;
    }

    private function availableSteps(Store $store): array
    {
        return StoreOnboardingSteps::available($store);
    }

    private function currentStep(Store $store, array $steps, string $requestedStep): string
    {
        if (array_key_exists($requestedStep, $steps)) {
            return $requestedStep;
        }

        $savedStep = Store::supportsOnboardingStateColumns()
            ? (string) $store->onboarding_last_step
            : '';

        if (array_key_exists($savedStep, $steps)) {
            return $savedStep;
        }

        foreach ($steps as $key => $step) {
            if (! ($step['complete'] ?? false)) {
                return $key;
            }
        }

        return array_key_first($steps);
    }

    private function nextStep(array $steps, string $currentStep): ?string
    {
        $keys = array_keys($steps);
        $index = array_search($currentStep, $keys, true);

        return $index === false ? null : ($keys[$index + 1] ?? null);
    }

    private function previousStep(array $steps, string $currentStep): ?string
    {
        $keys = array_keys($steps);
        $index = array_search($currentStep, $keys, true);

        return $index === false ? null : ($keys[$index - 1] ?? null);
    }

    private function firstIncompleteRequiredStep(Store $store, array $steps): ?string
    {
        $step = StoreOnboardingSteps::firstIncompleteRequiredStep($store);

        if ($step && array_key_exists($step, $steps)) {
            return $step;
        }

        return null;
    }

    private function createOnboardingProduct(StoreOnboardingRequest $request, Store $store): void
    {
        if ($store->products()->exists() || ! $request->filled('product_name')) {
            return;
        }

        if (! $store->canCreateMoreProducts()) {
            throw ValidationException::withMessages([
                'product_name' => 'El plan ' . $store->planLabel() . ' permite hasta ' . $store->productLimit() . ' productos.',
            ]);
        }

        $category = trim((string) $request->input('product_category'));

        if (! $this->categoryBelongsToStore($store, $category)) {
            throw ValidationException::withMessages([
                'product_category' => 'Crea la categoría en la sección Categorías antes de asignarla a un producto.',
            ]);
        }

        $productData = [
            'name' => trim((string) $request->input('product_name')),
            'slug' => Product::uniqueSlugFor((int) $store->id, (string) $request->input('product_name')),
            'price' => (float) $request->input('product_price'),
            'description' => \App\Support\ProductText::plain($request->input('product_description')) ?: null,
            'features' => $this->productContentService->cleanRichText($request->input('product_features')),
            'category' => $this->categoriesAvailable($store) ? ($category ?: null) : null,
            'image' => $this->productFileService->storeImage($request, $store),
            'sizes' => $this->productContentService->optionList($request->input('product_sizes')),
            'colors' => $this->productContentService->optionList($request->input('product_colors')),
            'user_id' => $store->user_id,
            'store_id' => $store->id,
        ];

        if (Product::supportsInventoryColumns()) {
            $productData['is_sold_out'] = false;
        }

        if (Product::supportsOfferColumn()) {
            $productData['has_offer'] = false;
        }

        if (Product::supportsWholesalePricingColumns()) {
            $productData['has_wholesale_price'] = false;
        }

        if (Product::supportsCustomBadgesColumn()) {
            $productData['custom_badges'] = [];
        }

        Product::create($productData);
    }

    private function categoryBelongsToStore(Store $store, ?string $categoryName): bool
    {
        $categoryName = trim((string) $categoryName);

        if ($categoryName === '' || ! $this->categoriesAvailable($store)) {
            return true;
        }

        return StoreCategory::where('store_id', $store->id)
            ->where('name', $categoryName)
            ->exists();
    }

    private function productsAvailable(): bool
    {
        return StoreOnboardingSteps::productsAvailable();
    }

    private function paymentsAvailable(): bool
    {
        return StoreOnboardingSteps::paymentsAvailable();
    }

    private function paymentAccounts(Store $store)
    {
        if (! $this->paymentsAvailable()) {
            return collect();
        }

        return $store->paymentAccounts()->get()->keyBy('provider');
    }

    private function categoryOptions(Store $store): array
    {
        if (! $this->categoriesAvailable($store)) {
            return [];
        }

        return $store->productCategorySelectOptions();
    }

    private function categoriesAvailable(Store $store): bool
    {
        return StoreOnboardingSteps::categoriesAvailable($store);
    }

    private function availableTemplates(Store $store): array
    {
        if (! $store->allowsTemplates()) {
            return [];
        }

        return collect(StoreTemplateCatalog::all())
            ->filter(fn (array $template) => (bool) ($template['available'] ?? false))
            ->all();
    }

    private function normalizedPhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
            $phone = '57'.$phone;
        }

        return $phone;
    }

    private function trialPhoneHash(string $phone): string
    {
        try {
            return $this->trialPhoneHashes->make($phone);
        } catch (TrialPhoneHashConfigurationException $exception) {
            Log::critical('La proteccion de pruebas gratis no esta disponible.', [
                'exception' => $exception::class,
            ]);

            throw ValidationException::withMessages([
                'whatsapp' => 'No podemos verificar WhatsApp temporalmente. Intenta nuevamente mas tarde.',
            ]);
        }
    }

    private function ensurePhoneIsAvailableForStore(string $phone, Store $store): void
    {
        $phoneHash = $this->trialPhoneHash($phone);

        if (! $this->phoneIsAvailableForStore($phoneHash, $store)) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Este numero ya utilizo su prueba gratis.',
            ]);
        }
    }

    private function phoneIsAvailableForStore(string $phoneHash, Store $store): bool
    {
        $claim = TrialSignupClaim::where('phone_hash', $phoneHash)->first();

        return ! $claim || (int) $claim->store_id === (int) $store->id;
    }
}
