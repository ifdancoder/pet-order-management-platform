<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Modules\Shipping\Application\Port\Out\Persistence\IShipmentRepository;
use Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ShipmentModel;

uses(DatabaseMigrations::class);

it('allows only one worker to claim a pending shipment', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
    ShipmentModel::factory()->create();
    $barrierPath = shipmentClaimFile('shipment-claim-barrier-');
    unlink($barrierPath);
    $resultPaths = [
        shipmentClaimFile('shipment-claim-result-'),
        shipmentClaimFile('shipment-claim-result-'),
    ];
    $processIds = [];

    try {
        foreach ($resultPaths as $resultPath) {
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork shipment claim process.');
            }

            if ($processId === 0) {
                runShipmentClaimProcess($barrierPath, $resultPath);
            }

            $processIds[] = $processId;
        }

        touch($barrierPath);

        foreach ($processIds as $processId) {
            pcntl_waitpid($processId, $status);

            if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                throw new RuntimeException('A shipment claim process failed.');
            }
        }

        $results = array_map(
            static fn (string $path): string => trim((string) file_get_contents($path)),
            $resultPaths,
        );
        $claimed = array_values(array_filter(
            $results,
            static fn (string $result): bool => $result === 'claimed',
        ));

        expect($claimed)->toHaveCount(1);
    } finally {
        foreach ([$barrierPath, ...$resultPaths] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
});

function shipmentClaimFile(string $prefix): string
{
    $path = tempnam(sys_get_temp_dir(), $prefix);

    if ($path === false) {
        throw new RuntimeException('Unable to create shipment claim file.');
    }

    return $path;
}

function runShipmentClaimProcess(string $barrierPath, string $resultPath): never
{
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
        $attempts = app(IShipmentRepository::class)->claimBatch(1, 60);
        file_put_contents($resultPath, $attempts === [] ? 'none' : 'claimed');
        exit(0);
    } catch (Throwable $exception) {
        file_put_contents(
            $resultPath,
            'error:'.$exception::class.':'.$exception->getMessage(),
        );
        exit(1);
    }
}
