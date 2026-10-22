<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Store;
use App\Services\VendlyMeasurementService;
use Illuminate\Support\Facades\Schema;

class StoreMeasurementObserver
{
    public function saved(Store|Product $model): void
    {
        if (! Schema::hasTable('vendly_measurement_events')) {
            return;
        }
        $store = $model instanceof Store ? $model : $model->store;
        if ($store) {
            app(VendlyMeasurementService::class)->storeConfigured($store);
        }
    }
}
