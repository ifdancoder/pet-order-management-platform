<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence;

use Illuminate\Database\ConnectionInterface;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class LaravelTransactionManager implements ITransactionManager
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed
    {
        return $this->connection->transaction(
            static fn (ConnectionInterface $connection): mixed => $callback(),
        );
    }
}
