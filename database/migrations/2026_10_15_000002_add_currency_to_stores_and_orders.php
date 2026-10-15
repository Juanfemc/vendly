<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'currency_code')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('currency_code', 3)->default('COP')->after('business_type');
            });
        }
        if (! Schema::hasColumn('orders', 'currency_code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('currency_code', 3)->default('COP')->after('total');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'currency_code')) {
            Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('currency_code'));
        }
        if (Schema::hasColumn('stores', 'currency_code')) {
            Schema::table('stores', fn (Blueprint $table) => $table->dropColumn('currency_code'));
        }
    }
};
