<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'stock_restored_at')) {
                $table->timestamp('stock_restored_at')->nullable();
            }

            if (! Schema::hasColumn('orders', 'stock_restored_by')) {
                $table->foreignId('stock_restored_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'stock_restored_by')) {
                $table->dropConstrainedForeignId('stock_restored_by');
            }

            if (Schema::hasColumn('orders', 'stock_restored_at')) {
                $table->dropColumn('stock_restored_at');
            }
        });
    }
};
