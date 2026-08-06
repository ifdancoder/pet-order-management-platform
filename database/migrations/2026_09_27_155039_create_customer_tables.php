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
        Schema::create('customers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('identity_user_id')->unique();
            $table->string('given_name', 100);
            $table->string('family_name', 100);
            $table->string('phone_number', 16)->nullable();
            $table->string('status', 16);
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();
            $table->string('recipient_name', 200);
            $table->string('line_1', 200);
            $table->string('line_2', 200)->nullable();
            $table->string('city', 100);
            $table->string('region', 100)->nullable();
            $table->string('postal_code', 32);
            $table->char('country_code', 2);
            $table->boolean('is_default');
            $table->timestamps();
        });

        DB::statement(
            'CREATE UNIQUE INDEX customer_addresses_one_default_per_customer ON customer_addresses (customer_id) WHERE is_default = true',
        );

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE customers ADD CONSTRAINT customers_status_check CHECK (status IN ('active', 'archived'))",
            );
            DB::statement(
                "ALTER TABLE customer_addresses ADD CONSTRAINT customer_addresses_country_code_check CHECK (country_code ~ '^[A-Z]{2}$')",
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
