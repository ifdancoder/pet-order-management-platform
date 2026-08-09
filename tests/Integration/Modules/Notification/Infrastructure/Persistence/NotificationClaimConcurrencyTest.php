<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Modules\Notification\Application\Port\Out\Persistence\INotificationRepository;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\NotificationDeliveryModel;

uses(DatabaseMigrations::class);

it('allows only one worker to claim a pending notification', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
    NotificationDeliveryModel::factory()->create();
    $barrierPath = notificationClaimFile('notification-claim-barrier-');
    unlink($barrierPath);
    $resultPaths = [
        notificationClaimFile('notification-claim-result-'),
        notificationClaimFile('notification-claim-result-'),
    ];
    $processIds = [];

    try {
        foreach ($resultPaths as $resultPath) {
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork notification claim process.');
            }

            if ($processId === 0) {
                runNotificationClaimProcess($barrierPath, $resultPath);
            }

            $processIds[] = $processId;
        }

        touch($barrierPath);

        foreach ($processIds as $processId) {
            pcntl_waitpid($processId, $status);

            if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                throw new RuntimeException('A notification claim process failed.');
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

function notificationClaimFile(string $prefix): string
{
    $path = tempnam(sys_get_temp_dir(), $prefix);

    if ($path === false) {
        throw new RuntimeException('Unable to create notification claim file.');
    }

    return $path;
}

function runNotificationClaimProcess(
    string $barrierPath,
    string $resultPath,
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
        $attempts = app(INotificationRepository::class)->claimBatch(1, 60);
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
