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
        Schema::create('processed_payment_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 16);
            $table->string('event_id', 128);
            $table->timestampTz('processed_at');

            $table->unique(['provider', 'event_id']);
            $table->index('processed_at');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE processed_payment_webhooks ADD CONSTRAINT processed_payment_webhooks_provider_check CHECK (provider IN ('stripe', 'paypal', 'fake'))",
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_payment_webhooks');
    }
};
