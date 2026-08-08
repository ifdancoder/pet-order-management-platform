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
        Schema::create('payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('provider', 16);
            $table->string('status', 16);
            $table->string('idempotency_key', 128)->unique();
            $table->char('request_hash', 64);
            $table->string('provider_payment_id', 128)->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at', 'id']);
            $table->index(['status', 'created_at', 'id']);
            $table->unique(['provider', 'provider_payment_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount > 0)',
            );
            DB::statement(
                "ALTER TABLE payments ADD CONSTRAINT payments_currency_check CHECK (currency ~ '^[A-Z]{3}$')",
            );
            DB::statement(
                "ALTER TABLE payments ADD CONSTRAINT payments_provider_check CHECK (provider IN ('stripe', 'paypal', 'fake'))",
            );
            DB::statement(
                "ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending', 'authorized', 'captured', 'failed', 'refunded'))",
            );
            DB::statement(
                "ALTER TABLE payments ADD CONSTRAINT payments_request_hash_check CHECK (request_hash ~ '^[a-f0-9]{64}$')",
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
