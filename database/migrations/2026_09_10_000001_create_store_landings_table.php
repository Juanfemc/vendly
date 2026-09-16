<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_landings')) {
            return;
        }

        Schema::create('store_landings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('enabled_by_admin')->default(false);
            $table->timestamp('activation_requested_at')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->foreignId('enabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('eyebrow', 120)->nullable();
            $table->string('headline', 180)->nullable();
            $table->string('subtitle', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->string('video_title', 180)->nullable();
            $table->text('video_description')->nullable();
            $table->boolean('bundle_enabled')->default(false);
            $table->unsignedTinyInteger('bundle_quantity')->default(2);
            $table->decimal('bundle_price', 12, 2)->nullable();
            $table->string('bundle_badge', 80)->nullable();
            $table->string('bundle_shipping_text', 160)->nullable();
            $table->json('features')->nullable();
            $table->json('faqs')->nullable();
            $table->json('review_images')->nullable();
            $table->json('sections_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_landings');
    }
};
