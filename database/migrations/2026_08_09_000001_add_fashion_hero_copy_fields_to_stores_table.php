<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'show_hero_overlay')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->boolean('show_hero_overlay')->default(false)->after('show_hero_products_action');
            });
        }

        if (! Schema::hasColumn('stores', 'hero_overlay_eyebrow')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('hero_overlay_eyebrow', 80)->nullable()->after('show_hero_overlay');
            });
        }
    }

    public function down(): void
    {
        foreach (['hero_overlay_eyebrow', 'show_hero_overlay'] as $column) {
            if (Schema::hasColumn('stores', $column)) {
                Schema::table('stores', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
