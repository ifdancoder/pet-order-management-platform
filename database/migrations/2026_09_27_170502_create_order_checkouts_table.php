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
        Schema::create('order_checkouts', function (Blueprint $table): void {
            $table->string('idempotency_key', 128)->primary();
            $table->char('request_hash', 64);
            $table->foreignUuid('order_id')
                ->nullable()
                ->unique()
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->uuid('inventory_reservation_id')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE order_checkouts ADD CONSTRAINT order_checkouts_completion_check CHECK ((order_id IS NULL AND inventory_reservation_id IS NULL AND completed_at IS NULL) OR (order_id IS NOT NULL AND inventory_reservation_id IS NOT NULL AND completed_at IS NOT NULL))',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_checkouts');
    }
};
