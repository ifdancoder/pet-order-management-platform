<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Port\Out\Transaction;

interface ITransactionManager
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
