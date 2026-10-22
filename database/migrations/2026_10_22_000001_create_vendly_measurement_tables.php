<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plan');
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('COP');
            $table->string('kind');
            $table->string('method');
            $table->string('reference');
            $table->string('reference_key', 64)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->timestamp('paid_at');
            $table->timestamps();
            $table->index(['user_id', 'paid_at']);
        });

        Schema::create('vendly_measurement_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('source_store_id');
            $table->string('event_name');
            $table->string('event_id')->unique();
            $table->string('action_source')->default('system_generated');
            $table->text('event_source_url')->nullable();
            $table->json('custom_data');
            $table->json('store_payload');
            $table->text('user_data'); // Encrypted cast; never store raw matching data here.
            $table->timestamp('occurred_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('discarded_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamps();
            $table->index(['sent_at', 'discarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendly_measurement_events');
        Schema::dropIfExists('subscription_payments');
    }
};
