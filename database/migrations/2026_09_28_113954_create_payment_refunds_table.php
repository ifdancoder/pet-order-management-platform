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
        Schema::create('payment_refunds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('return_id')->unique();
            $table->foreignUuid('payment_id')->constrained('payments');
            $table->uuid('order_id');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('status', 16);
            $table->string('provider_refund_id', 128)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('available_at');
            $table->timestampTz('claimed_at')->nullable();
            $table->uuid('claim_token')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at', 'claimed_at', 'id']);
            $table->index(['payment_id', 'created_at', 'id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE payment_refunds ADD CONSTRAINT payment_refunds_status_check CHECK (status IN ('pending', 'completed', 'failed'))",
            );
            DB::statement(
                'ALTER TABLE payment_refunds ADD CONSTRAINT payment_refunds_amount_check CHECK (amount > 0)',
            );
            DB::statement(
                "ALTER TABLE payment_refunds ADD CONSTRAINT payment_refunds_currency_check CHECK (currency ~ '^[A-Z]{3}$')",
            );
            DB::statement(
                'ALTER TABLE payment_refunds ADD CONSTRAINT payment_refunds_claim_check CHECK ((claimed_at IS NULL) = (claim_token IS NULL))',
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
