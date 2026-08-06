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
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->char('currency', 3);
            $table->string('status', 32);
            $table->unsignedBigInteger('total_amount');
            $table->timestamps();

            $table->index(['customer_id', 'created_at', 'id']);
            $table->index(['status', 'created_at', 'id']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->foreignUuid('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->uuid('inventory_item_id');
            $table->string('sku', 64);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_amount');
            $table->primary(['order_id', 'inventory_item_id']);
            $table->index('inventory_item_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE orders ADD CONSTRAINT orders_currency_check CHECK (currency ~ '^[A-Z]{3}$')",
            );
            DB::statement(
                "ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('draft', 'placed', 'confirmed', 'processing', 'shipped', 'completed', 'cancelled', 'payment_failed'))",
            );
            DB::statement(
                'ALTER TABLE orders ADD CONSTRAINT orders_total_amount_check CHECK (total_amount >= 0)',
            );
            DB::statement(
                'ALTER TABLE order_items ADD CONSTRAINT order_items_quantity_check CHECK (quantity > 0)',
            );
            DB::statement(
                'ALTER TABLE order_items ADD CONSTRAINT order_items_unit_price_amount_check CHECK (unit_price_amount >= 0)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
