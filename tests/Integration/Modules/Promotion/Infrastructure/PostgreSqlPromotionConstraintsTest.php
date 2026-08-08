<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(DatabaseMigrations::class);

it('limits percentage discounts to one hundred percent', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    DB::table('promotions')->insert(postgresPromotionAttributes([
        'discount_value' => 10_001,
    ]));
})->throws(QueryException::class);

it('rejects an inverted promotion date range', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    DB::table('promotions')->insert(postgresPromotionAttributes([
        'starts_at' => '2026-10-01T00:00:00+00:00',
        'ends_at' => '2026-09-01T00:00:00+00:00',
    ]));
})->throws(QueryException::class);

it('stores customer eligibility without a cross-module foreign key', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $promotionId = (string) Str::uuid7();
    DB::table('promotions')->insert(postgresPromotionAttributes([
        'id' => $promotionId,
    ]));
    DB::table('promotion_customer_eligibilities')->insert([
        'promotion_id' => $promotionId,
        'customer_id' => (string) Str::uuid7(),
    ]);

    $this->assertDatabaseCount('promotion_customer_eligibilities', 1);
});

/** @param array<string, mixed> $overrides */
function postgresPromotionAttributes(array $overrides = []): array
{
    return $overrides + [
        'id' => (string) Str::uuid7(),
        'code' => 'SAVE10',
        'enabled' => true,
        'discount_type' => 'percentage',
        'discount_value' => 1_000,
        'currency' => 'USD',
        'minimum_order_amount' => null,
        'starts_at' => '2026-09-01T00:00:00+00:00',
        'ends_at' => '2026-09-30T23:59:59+00:00',
        'created_at' => now(),
        'updated_at' => now(),
    ];
}
