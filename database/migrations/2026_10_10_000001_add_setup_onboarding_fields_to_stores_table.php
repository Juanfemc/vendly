<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'onboarding_choice')) {
                $column = $table->string('onboarding_choice', 24)->nullable();

                if (Schema::hasColumn('stores', 'onboarding_last_step')) {
                    $column->after('onboarding_last_step');
                }
            }

            if (! Schema::hasColumn('stores', 'onboarding_assistance_requested_at')) {
                $column = $table->timestamp('onboarding_assistance_requested_at')->nullable();

                if (Schema::hasColumn('stores', 'onboarding_choice')) {
                    $column->after('onboarding_choice');
                }
            }

            if (! Schema::hasColumn('stores', 'onboarding_store_shared_at')) {
                $column = $table->timestamp('onboarding_store_shared_at')->nullable();

                if (Schema::hasColumn('stores', 'onboarding_assistance_requested_at')) {
                    $column->after('onboarding_assistance_requested_at');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('stores', 'onboarding_store_shared_at') ? 'onboarding_store_shared_at' : null,
                Schema::hasColumn('stores', 'onboarding_assistance_requested_at') ? 'onboarding_assistance_requested_at' : null,
                Schema::hasColumn('stores', 'onboarding_choice') ? 'onboarding_choice' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
