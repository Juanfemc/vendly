<?php

use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'color_variant_display')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('color_variant_display', 20)
                    ->default(Store::COLOR_VARIANT_DISPLAY_SWATCH)
                    ->after('responsive_product_columns');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'color_variant_display')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('color_variant_display');
            });
        }
    }
};
