<?php

declare(strict_types=1);

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
        Schema::create('return_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->unique();
            $table->uuid('customer_id');
            $table->string('status', 16);
            $table->string('reason', 500);
            $table->char('currency', 3);
            $table->unsignedBigInteger('refund_amount');
            $table->timestampTz('requested_at');
            $table->timestamps();

            $table->index(['customer_id', 'requested_at', 'id']);
            $table->index(['status', 'updated_at', 'id']);
        });

        Schema::create('return_items', function (Blueprint $table): void {
            $table->foreignUuid('return_request_id')
                ->constrained('return_requests')
                ->cascadeOnDelete();
            $table->uuid('inventory_item_id');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_amount');
            $table->primary(['return_request_id', 'inventory_item_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE return_requests ADD CONSTRAINT return_requests_status_check CHECK (status IN ('requested', 'approved', 'rejected', 'received', 'refunded'))",
            );
            DB::statement(
                "ALTER TABLE return_requests ADD CONSTRAINT return_requests_currency_check CHECK (currency ~ '^[A-Z]{3}$')",
            );
            DB::statement(
                'ALTER TABLE return_requests ADD CONSTRAINT return_requests_refund_amount_check CHECK (refund_amount > 0)',
            );
            DB::statement(
                'ALTER TABLE return_items ADD CONSTRAINT return_items_quantity_check CHECK (quantity > 0)',
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_requests');
    }
};
