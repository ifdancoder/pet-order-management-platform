<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Command\ReserveStock\ReserveStockCommand;
use Modules\Inventory\Application\Data\StockRequest;
use Modules\Inventory\Domain\Exception\InsufficientStock;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\InventoryItemModel;
use Shared\Application\Bus\Command\ICommandBus;

uses(DatabaseMigrations::class);

function inventoryConcurrencyFile(string $prefix): string
{
    $path = tempnam(sys_get_temp_dir(), $prefix);

    if ($path === false) {
        throw new RuntimeException('Unable to create concurrency test file.');
    }

    return $path;
}

/**
 * @param  list<string>  $reservationKeys
 * @return list<string>
 */
function runInventoryReservationRace(
    string $inventoryItemId,
    array $reservationKeys,
): array {
    $barrierPath = inventoryConcurrencyFile('inventory-barrier-');
    unlink($barrierPath);
    $resultPaths = array_map(
        static fn (string $_reservationKey): string => inventoryConcurrencyFile('inventory-result-'),
        $reservationKeys,
    );
    $processIds = [];

    try {
        foreach ($reservationKeys as $index => $reservationKey) {
            $resultPath = $resultPaths[$index];
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork reservation process.');
            }

            if ($processId === 0) {
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
                    $reservation = app(ICommandBus::class)->dispatch(new ReserveStockCommand(
                        reservationKey: $reservationKey,
                        items: [new StockRequest($inventoryItemId, 1)],
                    ));
                    file_put_contents(
                        $resultPath,
                        'reserved:'.$reservation->id()->value(),
                    );
                    exit(0);
                } catch (InsufficientStock) {
                    file_put_contents($resultPath, 'insufficient');
                    exit(0);
                } catch (Throwable $exception) {
                    file_put_contents(
                        $resultPath,
                        'error:'.$exception::class.':'.$exception->getMessage(),
                    );
                    exit(1);
                }
            }

            $processIds[] = $processId;
        }

        touch($barrierPath);

        foreach ($processIds as $processId) {
            pcntl_waitpid($processId, $status);

            if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                throw new RuntimeException('A reservation process failed.');
            }
        }

        $results = array_map(
            static fn (string $path): string => trim((string) file_get_contents($path)),
            $resultPaths,
        );
        sort($results);

        return $results;
    } finally {
        foreach ([$barrierPath, ...$resultPaths] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}

it('allows only one process to reserve the final stock unit', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $inventoryItem = InventoryItemModel::factory()->create([
        'on_hand' => 1,
        'reserved' => 0,
    ]);

    $results = runInventoryReservationRace(
        $inventoryItem->getKey(),
        ['concurrent-checkout-1', 'concurrent-checkout-2'],
    );
    $successfulReservations = array_filter(
        $results,
        static fn (string $result): bool => str_starts_with($result, 'reserved:'),
    );

    expect($successfulReservations)->toHaveCount(1)
        ->and($results)->toContain('insufficient');

    DB::purge('pgsql');

    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'on_hand' => 1,
        'reserved' => 1,
    ]);
    $this->assertDatabaseCount('inventory_reservations', 1);
});

it('applies concurrent retries with the same reservation key once', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $inventoryItem = InventoryItemModel::factory()->create([
        'on_hand' => 1,
        'reserved' => 0,
    ]);

    $results = runInventoryReservationRace(
        $inventoryItem->getKey(),
        ['same-checkout', 'same-checkout'],
    );

    expect($results)->toHaveCount(2)
        ->and(array_unique($results))->toHaveCount(1)
        ->and($results[0])->toStartWith('reserved:');

    DB::purge('pgsql');

    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'on_hand' => 1,
        'reserved' => 1,
    ]);
    $this->assertDatabaseCount('inventory_reservations', 1);
});

it('prevents reserved stock from exceeding on-hand stock', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    InventoryItemModel::factory()->create([
        'on_hand' => 1,
        'reserved' => 2,
    ]);
})->throws(QueryException::class);
