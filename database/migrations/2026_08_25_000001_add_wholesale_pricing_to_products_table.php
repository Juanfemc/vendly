<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'has_wholesale_price')) {
                $table->boolean('has_wholesale_price')->default(false)->after('offer_original_price');
            }

            if (! Schema::hasColumn('products', 'wholesale_min_quantity')) {
                $table->unsignedInteger('wholesale_min_quantity')->nullable()->after('has_wholesale_price');
            }

            if (! Schema::hasColumn('products', 'wholesale_price')) {
                $table->decimal('wholesale_price', 10, 2)->nullable()->after('wholesale_min_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = collect([
                'has_wholesale_price',
                'wholesale_min_quantity',
                'wholesale_price',
            ])->filter(fn (string $column) => Schema::hasColumn('products', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
