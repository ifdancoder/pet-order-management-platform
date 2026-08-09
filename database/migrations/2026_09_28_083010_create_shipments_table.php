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
        Schema::create('shipments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->unique();
            $table->string('method', 32);
            $table->jsonb('address');
            $table->unsignedInteger('weight_grams');
            $table->unsignedBigInteger('cost_amount');
            $table->char('currency', 3);
            $table->string('status', 20);
            $table->string('provider_shipment_id')->nullable()->unique();
            $table->string('tracking_number')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('available_at');
            $table->timestampTz('claimed_at')->nullable();
            $table->uuid('claim_token')->nullable();
            $table->text('last_error')->nullable();
            $table->timestampTz('booked_at')->nullable();
            $table->timestamps();

            $table->index(
                ['status', 'available_at', 'claimed_at', 'id'],
                'shipments_pending_idx',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE shipments ADD CONSTRAINT shipments_method_check CHECK (method IN ('courier', 'express', 'pickup_point', 'international'))",
            );
            DB::statement(
                "ALTER TABLE shipments ADD CONSTRAINT shipments_status_check CHECK (status IN ('pending', 'booked', 'failed'))",
            );
            DB::statement(
                "ALTER TABLE shipments ADD CONSTRAINT shipments_currency_check CHECK (currency ~ '^[A-Z]{3}$')",
            );
            DB::statement(
                'ALTER TABLE shipments ADD CONSTRAINT shipments_weight_check CHECK (weight_grams > 0)',
            );
            DB::statement(
                'ALTER TABLE shipments ADD CONSTRAINT shipments_claim_check CHECK ((claimed_at IS NULL) = (claim_token IS NULL))',
            );
            DB::statement(
                "ALTER TABLE shipments ADD CONSTRAINT shipments_booking_check CHECK ((status = 'booked') = (provider_shipment_id IS NOT NULL AND tracking_number IS NOT NULL AND booked_at IS NOT NULL))",
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
