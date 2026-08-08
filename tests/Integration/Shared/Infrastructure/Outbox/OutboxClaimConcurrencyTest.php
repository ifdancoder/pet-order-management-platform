<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Shared\Application\Event\IIntegrationEvent;
use Shared\Application\Port\Out\Outbox\IOutboxRepository;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;

uses(DatabaseMigrations::class);

it('allows only one worker to claim a pending outbox message', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    app(IOutboxWriter::class)->record(new ConcurrentOutboxIntegrationEvent);
    $barrierPath = outboxClaimFile('outbox-claim-barrier-');
    unlink($barrierPath);
    $resultPaths = [
        outboxClaimFile('outbox-claim-result-'),
        outboxClaimFile('outbox-claim-result-'),
    ];
    $processIds = [];

    try {
        foreach ($resultPaths as $resultPath) {
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork outbox claim process.');
            }

            if ($processId === 0) {
                runOutboxClaimProcess($barrierPath, $resultPath);
            }

            $processIds[] = $processId;
        }

        touch($barrierPath);

        foreach ($processIds as $processId) {
            pcntl_waitpid($processId, $status);

            if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                throw new RuntimeException('An outbox claim process failed.');
            }
        }

        $results = array_map(
            static fn (string $path): string => trim((string) file_get_contents($path)),
            $resultPaths,
        );
        $claimed = array_values(array_filter(
            $results,
            static fn (string $result): bool => $result !== 'none',
        ));

        expect($claimed)->toHaveCount(1)
            ->and($claimed[0])->toStartWith('claimed:');
    } finally {
        foreach ([$barrierPath, ...$resultPaths] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
});

function outboxClaimFile(string $prefix): string
{
    $path = tempnam(sys_get_temp_dir(), $prefix);

    if ($path === false) {
        throw new RuntimeException('Unable to create outbox claim file.');
    }

    return $path;
}

function runOutboxClaimProcess(string $barrierPath, string $resultPath): never
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
        $messages = app(IOutboxRepository::class)->claimBatch(
            limit: 1,
            claimTimeoutSeconds: 60,
        );
        file_put_contents(
            $resultPath,
            isset($messages[0]) ? 'claimed:'.$messages[0]->messageId : 'none',
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

final readonly class ConcurrentOutboxIntegrationEvent implements IIntegrationEvent
{
    public function name(): string
    {
        return 'test.concurrent.v1';
    }

    public function aggregateId(): string
    {
        return 'aggregate-1';
    }

    /** @return array{value: string} */
    public function payload(): array
    {
        return ['value' => 'concurrent'];
    }
}
