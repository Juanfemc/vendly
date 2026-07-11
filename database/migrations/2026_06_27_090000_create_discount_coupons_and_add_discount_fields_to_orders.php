<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('discount_coupons')) {
            Schema::create('discount_coupons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->string('code', 60);
                $table->string('type', 20);
                $table->decimal('value', 12, 2);
                $table->decimal('min_subtotal', 12, 2)->default(0);
                $table->decimal('max_discount_amount', 12, 2)->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['store_id', 'code']);
                $table->index(['store_id', 'is_active']);
            });
        }

        if (Schema::hasTable('orders')) {
            $this->addOrderColumn('discount_coupon_id', fn (Blueprint $table) => $table->foreignId('discount_coupon_id')->nullable()->constrained('discount_coupons')->nullOnDelete());
            $this->addOrderColumn('discount_code', fn (Blueprint $table) => $table->string('discount_code', 60)->nullable());
            $this->addOrderColumn('discount_amount', fn (Blueprint $table) => $table->decimal('discount_amount', 12, 2)->default(0));
            $this->addOrderColumn('discount_snapshot', fn (Blueprint $table) => $table->text('discount_snapshot')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                foreach (['discount_coupon_id', 'discount_code', 'discount_amount', 'discount_snapshot'] as $column) {
                    if (Schema::hasColumn('orders', $column)) {
                        if ($column === 'discount_coupon_id') {
                            $table->dropConstrainedForeignId($column);
                        } else {
                            $table->dropColumn($column);
                        }
                    }
                }
            });
        }

        Schema::dropIfExists('discount_coupons');
    }

    private function addOrderColumn(string $column, callable $definition): void
    {
        if (Schema::hasColumn('orders', $column)) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }
};
