<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Order\Domain\ValueObject\OrderId;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\OrderMapper;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentOrderRepository;

uses(DatabaseMigrations::class);

function postgresOrderAttributes(array $overrides = []): array
{
    return $overrides + [
        'id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f40',
        'customer_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f41',
        'currency' => 'USD',
        'status' => 'draft',
        'total_amount' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

it('enforces order status values', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    DB::table('orders')->insert(postgresOrderAttributes([
        'status' => 'unknown',
    ]));
})->throws(QueryException::class);

it('enforces positive item quantities', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    DB::table('orders')->insert(postgresOrderAttributes());

    DB::table('order_items')->insert([
        'order_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f40',
        'inventory_item_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f42',
        'sku' => 'SKU-001',
        'quantity' => 0,
        'unit_price_amount' => 1000,
    ]);
})->throws(QueryException::class);

it('stores external module identifiers without cross-module foreign keys', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    DB::table('orders')->insert(postgresOrderAttributes([
        'total_amount' => 1000,
    ]));
    DB::table('order_items')->insert([
        'order_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f40',
        'inventory_item_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f42',
        'sku' => 'SKU-001',
        'quantity' => 1,
        'unit_price_amount' => 1000,
    ]);

    $this->assertDatabaseHas('orders', [
        'customer_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f41',
    ]);
    $this->assertDatabaseHas('order_items', [
        'inventory_item_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f42',
    ]);
});

it('holds a row lock while loading an order for update', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $order = OrderModel::factory()->create();
    $repository = new EloquentOrderRepository(new OrderMapper);
    $primaryConnection = DB::connection();
    $primaryConnection->beginTransaction();

    try {
        expect($repository->findByIdForUpdate(
            new OrderId($order->getKey()),
        ))->not->toBeNull();

        Config::set(
            'database.connections.pgsql_order_lock_contender',
            Config::get('database.connections.pgsql'),
        );

        $contender = DB::connection('pgsql_order_lock_contender');
        $contender->statement("SET lock_timeout TO '250ms'");

        try {
            OrderModel::on('pgsql_order_lock_contender')
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->first();

            test()->fail('The competing connection acquired a locked order row.');
        } catch (QueryException $exception) {
            expect((string) $exception->getCode())->toBe('55P03');
        } finally {
            DB::disconnect('pgsql_order_lock_contender');
        }
    } finally {
        $primaryConnection->rollBack();
    }
});
