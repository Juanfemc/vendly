<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'global_discount_enabled')) {
                $table->boolean('global_discount_enabled')->default(false)->after('free_shipping_minimum');
            }

            if (! Schema::hasColumn('stores', 'global_discount_type')) {
                $table->string('global_discount_type', 20)->nullable()->after('global_discount_enabled');
            }

            if (! Schema::hasColumn('stores', 'global_discount_value')) {
                $table->decimal('global_discount_value', 12, 2)->nullable()->after('global_discount_type');
            }

            if (! Schema::hasColumn('stores', 'global_discount_label')) {
                $table->string('global_discount_label', 40)->nullable()->after('global_discount_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('stores', 'global_discount_label') ? 'global_discount_label' : null,
                Schema::hasColumn('stores', 'global_discount_value') ? 'global_discount_value' : null,
                Schema::hasColumn('stores', 'global_discount_type') ? 'global_discount_type' : null,
                Schema::hasColumn('stores', 'global_discount_enabled') ? 'global_discount_enabled' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
