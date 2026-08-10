<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'ALTER TABLE shipments DROP CONSTRAINT shipments_status_check',
        );
        DB::statement(
            "ALTER TABLE shipments ADD CONSTRAINT shipments_status_check CHECK (status IN ('awaiting_payment', 'pending', 'booked', 'failed'))",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'ALTER TABLE shipments DROP CONSTRAINT shipments_status_check',
        );
        DB::statement(
            "ALTER TABLE shipments ADD CONSTRAINT shipments_status_check CHECK (status IN ('pending', 'booked', 'failed'))",
        );
    }
};
