<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'show_fashion_size_filter')) {
            return;
        }

        DB::table('stores')
            ->where('business_type', 'store')
            ->update(['show_fashion_size_filter' => false]);

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE stores MODIFY show_fashion_size_filter TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stores', 'show_fashion_size_filter')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE stores MODIFY show_fashion_size_filter TINYINT(1) NOT NULL DEFAULT 1');
    }
};
