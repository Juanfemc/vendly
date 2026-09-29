<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'hero_overlay_subtitle')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('hero_overlay_subtitle', 240)->nullable()->after('hero_overlay_title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'hero_overlay_subtitle')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('hero_overlay_subtitle');
            });
        }
    }
};
