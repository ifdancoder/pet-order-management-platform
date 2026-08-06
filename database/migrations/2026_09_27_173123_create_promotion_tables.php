<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 32)->unique();
            $table->boolean('enabled')->default(true);
            $table->string('discount_type', 16);
            $table->unsignedBigInteger('discount_value');
            $table->char('currency', 3);
            $table->unsignedBigInteger('minimum_order_amount')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->timestamps();

            $table->index(['enabled', 'starts_at', 'ends_at']);
        });

        Schema::create('promotion_customer_eligibilities', function (Blueprint $table): void {
            $table->foreignUuid('promotion_id')
                ->constrained('promotions')
                ->cascadeOnDelete();
            $table->uuid('customer_id');
            $table->primary(['promotion_id', 'customer_id']);
            $table->index('customer_id');
        });

        Schema::create('promotion_product_eligibilities', function (Blueprint $table): void {
            $table->foreignUuid('promotion_id')
                ->constrained('promotions')
                ->cascadeOnDelete();
            $table->string('sku', 64);
            $table->primary(['promotion_id', 'sku']);
            $table->index('sku');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE promotions ADD CONSTRAINT promotions_code_check CHECK (code ~ '^[A-Z0-9_-]{3,32}$')",
            );
            DB::statement(
                "ALTER TABLE promotions ADD CONSTRAINT promotions_discount_type_check CHECK (discount_type IN ('percentage', 'fixed'))",
            );
            DB::statement(
                'ALTER TABLE promotions ADD CONSTRAINT promotions_discount_value_check CHECK (discount_value > 0 AND (discount_type <> \'percentage\' OR discount_value <= 10000))',
            );
            DB::statement(
                "ALTER TABLE promotions ADD CONSTRAINT promotions_currency_check CHECK (currency ~ '^[A-Z]{3}$')",
            );
            DB::statement(
                'ALTER TABLE promotions ADD CONSTRAINT promotions_date_range_check CHECK (ends_at >= starts_at)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_product_eligibilities');
        Schema::dropIfExists('promotion_customer_eligibilities');
        Schema::dropIfExists('promotions');
    }
};
