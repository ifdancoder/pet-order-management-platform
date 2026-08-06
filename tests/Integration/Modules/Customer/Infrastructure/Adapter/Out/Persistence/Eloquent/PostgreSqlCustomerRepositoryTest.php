<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);

it('enforces customer status values', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    DB::table('customers')->insert([
        'id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f16',
        'identity_user_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f17',
        'given_name' => 'Ada',
        'family_name' => 'Lovelace',
        'phone_number' => null,
        'status' => 'unknown',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
})->throws(QueryException::class);

it('enforces uppercase alpha two country codes', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    DB::table('customers')->insert([
        'id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f16',
        'identity_user_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f17',
        'given_name' => 'Ada',
        'family_name' => 'Lovelace',
        'phone_number' => null,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('customer_addresses')->insert([
        'id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f18',
        'customer_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f16',
        'recipient_name' => 'Ada Lovelace',
        'line_1' => '1 Analytical Engine Road',
        'line_2' => null,
        'city' => 'London',
        'region' => null,
        'postal_code' => 'SW1A 1AA',
        'country_code' => 'gb',
        'is_default' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
})->throws(QueryException::class);
