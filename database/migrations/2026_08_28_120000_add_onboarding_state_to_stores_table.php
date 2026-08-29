<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'onboarding_completed_at')) {
                $column = $table->timestamp('onboarding_completed_at')->nullable();

                if (Schema::hasColumn('stores', 'hero_overlay_button_url')) {
                    $column->after('hero_overlay_button_url');
                }
            }

            if (! Schema::hasColumn('stores', 'onboarding_last_step')) {
                $table->string('onboarding_last_step', 40)->nullable()->after('onboarding_completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'onboarding_last_step')) {
                $table->dropColumn('onboarding_last_step');
            }

            if (Schema::hasColumn('stores', 'onboarding_completed_at')) {
                $table->dropColumn('onboarding_completed_at');
            }
        });
    }
};
