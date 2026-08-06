<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerModel;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\InventoryItemModel;
use Modules\Order\Application\Command\Checkout\CheckoutCommand;
use Modules\Order\Application\Data\OrderItemData;
use Shared\Application\Bus\Command\ICommandBus;

uses(DatabaseMigrations::class);

it('applies concurrent checkout retries once', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $customer = CustomerModel::factory()->create();
    $inventoryItem = InventoryItemModel::factory()->create([
        'sku' => 'CONCURRENT-PHONE',
        'on_hand' => 1,
        'reserved' => 0,
    ]);
    $resultPaths = [
        checkoutConcurrencyFile('checkout-result-'),
        checkoutConcurrencyFile('checkout-result-'),
    ];
    $barrierPath = checkoutConcurrencyFile('checkout-barrier-');
    unlink($barrierPath);
    $processIds = [];

    try {
        foreach ($resultPaths as $resultPath) {
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork checkout process.');
            }

            if ($processId === 0) {
                runCheckoutProcess(
                    barrierPath: $barrierPath,
                    resultPath: $resultPath,
                    customerId: $customer->getKey(),
                    inventoryItemId: $inventoryItem->getKey(),
                );
            }

            $processIds[] = $processId;
        }

        touch($barrierPath);

        foreach ($processIds as $processId) {
            pcntl_waitpid($processId, $status);

            if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                throw new RuntimeException('A checkout process failed.');
            }
        }

        $results = array_map(
            static fn (string $path): string => trim((string) file_get_contents($path)),
            $resultPaths,
        );

        expect(array_unique($results))->toHaveCount(1)
            ->and($results[0])->toStartWith('completed:');

        DB::purge('pgsql');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_checkouts', 1);
        $this->assertDatabaseCount('inventory_reservations', 1);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $inventoryItem->getKey(),
            'reserved' => 1,
        ]);
    } finally {
        foreach ([$barrierPath, ...$resultPaths] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
});

function checkoutConcurrencyFile(string $prefix): string
{
    $path = tempnam(sys_get_temp_dir(), $prefix);

    if ($path === false) {
        throw new RuntimeException('Unable to create checkout concurrency test file.');
    }

    return $path;
}

function runCheckoutProcess(
    string $barrierPath,
    string $resultPath,
    string $customerId,
    string $inventoryItemId,
): never {
    DB::purge('pgsql');
    $deadline = microtime(true) + 5;

    while (! file_exists($barrierPath)) {
        if (microtime(true) >= $deadline) {
            file_put_contents($resultPath, 'error:barrier_timeout');
            exit(1);
        }

        usleep(1_000);
    }

    try {
        $result = app(ICommandBus::class)->dispatch(new CheckoutCommand(
            idempotencyKey: 'concurrent-checkout-request',
            customerId: $customerId,
            currency: 'USD',
            items: [
                new OrderItemData(
                    inventoryItemId: $inventoryItemId,
                    sku: 'CONCURRENT-PHONE',
                    quantity: 1,
                    unitPriceAmount: 100_00,
                ),
            ],
        ));
        file_put_contents(
            $resultPath,
            'completed:'.$result->orderId.':'.$result->inventoryReservationId,
        );
        exit(0);
    } catch (Throwable $exception) {
        file_put_contents(
            $resultPath,
            'error:'.$exception::class.':'.$exception->getMessage(),
        );
        exit(1);
    }
}
