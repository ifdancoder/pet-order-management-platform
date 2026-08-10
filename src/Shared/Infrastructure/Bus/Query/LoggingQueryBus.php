<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Query;

use Psr\Log\LoggerInterface;
use Shared\Application\Bus\Query\IQuery;
use Shared\Application\Bus\Query\IQueryBus;
use Throwable;

final readonly class LoggingQueryBus implements IQueryBus
{
    public function __construct(
        private IQueryBus $inner,
        private LoggerInterface $logger,
    ) {}

    /** @param IQuery<mixed> $query */
    public function ask(IQuery $query): mixed
    {
        $context = ['query' => $query::class];

        $this->logger->info('Query started.', $context);

        $startedAt = hrtime(true);

        try {
            $result = $this->inner->ask($query);
        } catch (Throwable $exception) {
            $this->logger->error('Query failed.', [
                ...$context,
                'exception' => $exception::class,
                'duration_ms' => $this->durationInMs($startedAt),
                'outcome' => 'failure',
            ]);

            throw $exception;
        }

        $this->logger->info('Query completed.', [
            ...$context,
            'duration_ms' => $this->durationInMs($startedAt),
            'outcome' => 'success',
        ]);

        return $result;
    }

    private function durationInMs(int $startedAt): float
    {
        return (hrtime(true) - $startedAt) / 1_000_000;
    }
}
