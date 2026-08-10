<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('shipping_cost_amount')->default(0);
            $table->string('shipping_method', 32)->nullable();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE orders ADD CONSTRAINT orders_shipping_cost_check CHECK (shipping_cost_amount >= 0)',
            );
            DB::statement(
                "ALTER TABLE orders ADD CONSTRAINT orders_shipping_method_check CHECK (shipping_method IS NULL OR shipping_method IN ('courier', 'express', 'pickup_point', 'international'))",
            );
            DB::statement(
                'ALTER TABLE orders ADD CONSTRAINT orders_shipping_pair_check CHECK (shipping_cost_amount = 0 OR shipping_method IS NOT NULL)',
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['shipping_cost_amount', 'shipping_method']);
        });
    }
};
