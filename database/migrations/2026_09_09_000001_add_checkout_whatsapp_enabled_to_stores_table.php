<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'checkout_whatsapp_enabled')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->boolean('checkout_whatsapp_enabled')->default(true)->after('whatsapp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'checkout_whatsapp_enabled')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('checkout_whatsapp_enabled');
            });
        }
    }
};
