<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'show_fashion_delivery_times')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->boolean('show_fashion_delivery_times')
                    ->default(false)
                    ->after('color_variant_display');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'show_fashion_delivery_times')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('show_fashion_delivery_times');
            });
        }
    }
};
