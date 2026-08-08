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
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('discount_amount')->default(0);
        });

        Schema::create('order_applied_promotions', function (Blueprint $table): void {
            $table->foreignUuid('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->string('promotion_code', 32);
            $table->primary(['order_id', 'promotion_code']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE orders ADD CONSTRAINT orders_discount_amount_check CHECK (discount_amount >= 0)',
            );
            DB::statement(
                "ALTER TABLE order_applied_promotions ADD CONSTRAINT order_applied_promotions_code_check CHECK (promotion_code ~ '^[A-Z0-9_-]{3,32}$')",
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_applied_promotions');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('discount_amount');
        });
    }
};
