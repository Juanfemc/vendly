<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'sort_order')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('store_id');
                $table->index(['store_id', 'sort_order']);
            });
        }

        DB::table('products')
            ->select('store_id')
            ->whereNotNull('store_id')
            ->distinct()
            ->orderBy('store_id')
            ->pluck('store_id')
            ->each(function ($storeId): void {
                $products = DB::table('products')
                    ->where('store_id', $storeId)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->pluck('id');

                foreach ($products as $position => $productId) {
                    DB::table('products')
                        ->where('id', $productId)
                        ->update(['sort_order' => $position + 1]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'sort_order')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['store_id', 'sort_order']);
                $table->dropColumn('sort_order');
            });
        }
    }
};
