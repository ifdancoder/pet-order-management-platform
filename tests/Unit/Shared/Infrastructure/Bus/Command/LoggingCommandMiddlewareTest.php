<?php

declare(strict_types=1);

use Psr\Log\AbstractLogger;
use Shared\Application\Bus\Command\ICommand;
use Shared\Infrastructure\Bus\Command\LoggingCommandMiddleware;

/** @implements ICommand<string> */
final readonly class LoggingMiddlewareTestCommand implements ICommand
{
    public function __construct(
        public string $password,
    ) {}
}

final class LoggingMiddlewareTestLogger extends AbstractLogger
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

it('logs command execution without serializing command data', function () {
    $logger = new LoggingMiddlewareTestLogger;
    $middleware = new LoggingCommandMiddleware($logger);

    $result = $middleware->process(
        new LoggingMiddlewareTestCommand('do-not-log-this'),
        static fn (): string => 'completed',
    );

    expect($result)->toBe('completed')
        ->and($logger->records)->toHaveCount(2)
        ->and($logger->records[0]['context'])->toBe([
            'command' => LoggingMiddlewareTestCommand::class,
        ])
        ->and(json_encode($logger->records))->not->toContain('do-not-log-this');
});

it('logs the exception type and rethrows command failures', function () {
    $logger = new LoggingMiddlewareTestLogger;
    $middleware = new LoggingCommandMiddleware($logger);

    try {
        $middleware->process(
            new LoggingMiddlewareTestCommand('secret'),
            static fn (): never => throw new RuntimeException('failed'),
        );
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('failed');
    }

    expect($logger->records)->toHaveCount(2)
        ->and($logger->records[1]['message'])->toBe('Command failed.')
        ->and($logger->records[1]['context'])->toBe([
            'command' => LoggingMiddlewareTestCommand::class,
            'exception' => RuntimeException::class,
        ]);
});
