<?php

declare(strict_types=1);

use Psr\Log\AbstractLogger;
use Shared\Application\Bus\Query\IQuery;
use Shared\Application\Bus\Query\IQueryBus;
use Shared\Infrastructure\Bus\Query\LoggingQueryBus;

/** @implements IQuery<string> */
final readonly class LoggingQueryBusTestQuery implements IQuery
{
    public function __construct(
        public string $secret,
    ) {}
}

final class LoggingQueryBusTestLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
    public array $records = [];

    /** @param array<string, mixed> $context */
    public function log(
        mixed $level,
        Stringable|string $message,
        array $context = [],
    ): void {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}

final readonly class LoggingQueryBusTestInnerBus implements IQueryBus
{
    public function __construct(
        private mixed $result = null,
        private ?Throwable $throws = null,
    ) {}

    public function ask(IQuery $query): mixed
    {
        if ($this->throws !== null) {
            throw $this->throws;
        }

        return $this->result;
    }
}

it('logs query execution without serializing query data', function () {
    $logger = new LoggingQueryBusTestLogger;
    $bus = new LoggingQueryBus(new LoggingQueryBusTestInnerBus('answer'), $logger);

    $result = $bus->ask(new LoggingQueryBusTestQuery('do-not-log-this'));

    expect($result)->toBe('answer')
        ->and($logger->records)->toHaveCount(2)
        ->and($logger->records[0]['context'])->toBe([
            'query' => LoggingQueryBusTestQuery::class,
        ])
        ->and($logger->records[1]['message'])->toBe('Query completed.')
        ->and($logger->records[1]['context'])->toHaveKeys(['query', 'duration_ms', 'outcome'])
        ->and($logger->records[1]['context']['query'])->toBe(LoggingQueryBusTestQuery::class)
        ->and($logger->records[1]['context']['outcome'])->toBe('success')
        ->and($logger->records[1]['context']['duration_ms'])->toBeFloat()
        ->and(json_encode($logger->records))->not->toContain('do-not-log-this');
});

it('logs the exception type and rethrows query failures', function () {
    $logger = new LoggingQueryBusTestLogger;
    $bus = new LoggingQueryBus(
        new LoggingQueryBusTestInnerBus(throws: new RuntimeException('failed')),
        $logger,
    );

    try {
        $bus->ask(new LoggingQueryBusTestQuery('secret'));
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('failed');
    }

    expect($logger->records)->toHaveCount(2)
        ->and($logger->records[1]['message'])->toBe('Query failed.')
        ->and($logger->records[1]['context'])->toHaveKeys(['query', 'exception', 'duration_ms', 'outcome'])
        ->and($logger->records[1]['context']['query'])->toBe(LoggingQueryBusTestQuery::class)
        ->and($logger->records[1]['context']['exception'])->toBe(RuntimeException::class)
        ->and($logger->records[1]['context']['outcome'])->toBe('failure')
        ->and($logger->records[1]['context']['duration_ms'])->toBeFloat();
});
