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
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('sku', 64)->unique();
            $table->unsignedInteger('on_hand');
            $table->unsignedInteger('reserved')->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_reservations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reservation_key', 128)->unique();
            $table->string('status', 16);
            $table->timestamps();
        });

        Schema::create('inventory_reservation_lines', function (Blueprint $table): void {
            $table->foreignUuid('reservation_id')
                ->constrained('inventory_reservations')
                ->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')
                ->constrained('inventory_items')
                ->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->primary(['reservation_id', 'inventory_item_id']);
            $table->index('inventory_item_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE inventory_items ADD CONSTRAINT inventory_items_stock_check CHECK (on_hand >= 0 AND reserved >= 0 AND reserved <= on_hand)',
            );
            DB::statement(
                "ALTER TABLE inventory_reservations ADD CONSTRAINT inventory_reservations_status_check CHECK (status IN ('active', 'released'))",
            );
            DB::statement(
                'ALTER TABLE inventory_reservation_lines ADD CONSTRAINT inventory_reservation_lines_quantity_check CHECK (quantity > 0)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservation_lines');
        Schema::dropIfExists('inventory_reservations');
        Schema::dropIfExists('inventory_items');
    }
};
