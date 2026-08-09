<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_message_id');
            $table->string('recipient', 254);
            $table->string('channel', 32);
            $table->string('template', 100);
            $table->jsonb('data');
            $table->string('status', 20);
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('available_at');
            $table->timestampTz('claimed_at')->nullable();
            $table->uuid('claim_token')->nullable();
            $table->text('last_error')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();

            $table->unique(
                ['source_message_id', 'channel', 'template'],
                'notification_deliveries_source_channel_template_unique',
            );
            $table->index(
                ['status', 'available_at', 'claimed_at', 'id'],
                'notification_deliveries_pending_idx',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE notification_deliveries ADD CONSTRAINT notification_deliveries_channel_check CHECK (channel IN ('email'))",
            );
            DB::statement(
                "ALTER TABLE notification_deliveries ADD CONSTRAINT notification_deliveries_status_check CHECK (status IN ('pending', 'sent', 'failed'))",
            );
            DB::statement(
                'ALTER TABLE notification_deliveries ADD CONSTRAINT notification_deliveries_claim_check CHECK ((claimed_at IS NULL) = (claim_token IS NULL))',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
