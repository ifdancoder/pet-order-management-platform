<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processed_messages', function (Blueprint $table) {
            $table->id();
            $table->string('consumer', 100);
            $table->uuid('message_id');
            $table->timestampTz('processed_at');

            $table->unique(['consumer', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_messages');
    }
};
