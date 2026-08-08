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
        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('message_id')->unique();
            $table->string('event_name', 128);
            $table->string('aggregate_id', 128);
            $table->jsonb('payload');
            $table->timestampTz('occurred_at');
            $table->timestampTz('available_at');
            $table->timestampTz('claimed_at')->nullable();
            $table->uuid('claim_token')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestampTz('published_at')->nullable();

            $table->index(
                ['published_at', 'available_at', 'claimed_at', 'id'],
                'outbox_messages_pending_idx',
            );
            $table->index(['aggregate_id', 'occurred_at', 'id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE outbox_messages ADD CONSTRAINT outbox_messages_claim_check CHECK ((claimed_at IS NULL) = (claim_token IS NULL))',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
