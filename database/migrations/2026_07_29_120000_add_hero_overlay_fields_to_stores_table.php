<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('hero_overlay_title', 120)->nullable()->after('show_hero_products_action');
            $table->string('hero_overlay_button_text', 60)->nullable()->after('hero_overlay_title');
            $table->string('hero_overlay_button_url')->nullable()->after('hero_overlay_button_text');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'hero_overlay_title',
                'hero_overlay_button_text',
                'hero_overlay_button_url',
            ]);
        });
    }
};
