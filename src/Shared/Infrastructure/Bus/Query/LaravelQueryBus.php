<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Query;

use Shared\Application\Bus\Query\IQuery;
use Shared\Application\Bus\Query\IQueryBus;
use Shared\Infrastructure\Bus\HandlerInvoker;

final readonly class LaravelQueryBus implements IQueryBus
{
    public function __construct(
        private HandlerInvoker $invoker,
    ) {}

    /** @param IQuery<mixed> $query */
    public function ask(IQuery $query): mixed
    {
        return $this->invoker->invoke($query);
    }
}
