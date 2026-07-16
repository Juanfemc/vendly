<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('discount_coupons') || Schema::hasColumn('discount_coupons', 'applies_to')) {
            return;
        }

        Schema::table('discount_coupons', function (Blueprint $table) {
            $table->string('applies_to', 40)->default('products')->after('type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('discount_coupons') || ! Schema::hasColumn('discount_coupons', 'applies_to')) {
            return;
        }

        Schema::table('discount_coupons', function (Blueprint $table) {
            $table->dropColumn('applies_to');
        });
    }
};
