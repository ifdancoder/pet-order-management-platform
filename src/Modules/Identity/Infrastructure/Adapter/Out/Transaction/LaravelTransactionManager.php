<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Adapter\Out\Transaction;

use Illuminate\Database\ConnectionInterface;
use Modules\Identity\Application\Port\Out\Transaction\ITransactionManager;

final readonly class LaravelTransactionManager implements ITransactionManager
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    public function run(callable $callback): mixed
    {
        return $this->connection->transaction($callback);
    }
}
