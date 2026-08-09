<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;
use Shared\Application\Messaging\IntegrationMessage;
use Shared\Application\Messaging\IntegrationMessageConsumer;

uses(DatabaseMigrations::class);

it('applies concurrent deliveries of one payment message once', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $order = OrderModel::factory()->create([
        'status' => OrderStatus::Placed->value,
    ]);
    $barrierPath = paymentMessageConcurrencyFile('payment-message-barrier-');
    unlink($barrierPath);
    $resultPaths = [
        paymentMessageConcurrencyFile('payment-message-result-'),
        paymentMessageConcurrencyFile('payment-message-result-'),
    ];
    $processIds = [];

    try {
        foreach ($resultPaths as $resultPath) {
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork payment message process.');
            }

            if ($processId === 0) {
                runPaymentMessageProcess(
                    $barrierPath,
                    $resultPath,
                    $order->getKey(),
                );
            }

            $processIds[] = $processId;
        }

        touch($barrierPath);

        foreach ($processIds as $processId) {
            pcntl_waitpid($processId, $status);

            if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                throw new RuntimeException('A payment message process failed.');
            }
        }

        DB::purge('pgsql');

        expect(array_map(
            static fn (string $path): string => trim((string) file_get_contents($path)),
            $resultPaths,
        ))->each->toBeIn(['processed', 'duplicate']);
        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'status' => OrderStatus::Confirmed->value,
        ]);
        $this->assertDatabaseCount('processed_messages', 1);
    } finally {
        foreach ([$barrierPath, ...$resultPaths] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
});

function paymentMessageConcurrencyFile(string $prefix): string
{
    $path = tempnam(sys_get_temp_dir(), $prefix);

    if ($path === false) {
        throw new RuntimeException('Unable to create payment message concurrency file.');
    }

    return $path;
}

function runPaymentMessageProcess(
    string $barrierPath,
    string $resultPath,
    string $orderId,
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
        $processed = app(IntegrationMessageConsumer::class)->consume(
            'order-payment-status',
            new IntegrationMessage(
                messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f90',
                name: 'payment.captured.v1',
                aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f91',
                occurredAt: new DateTimeImmutable('2026-09-28T05:00:00+00:00'),
                data: ['order_id' => $orderId],
            ),
        );
        file_put_contents($resultPath, $processed ? 'processed' : 'duplicate');
        exit(0);
    } catch (Throwable $exception) {
        file_put_contents(
            $resultPath,
            'error:'.$exception::class.':'.$exception->getMessage(),
        );
        exit(1);
    }
}
