<?php

declare(strict_types=1);

namespace Shared\Application\Bus\Query;

interface IQueryBus
{
    /**
     * @template TResult
     *
     * @param  IQuery<TResult>  $query
     * @return TResult
     */
    public function ask(IQuery $query): mixed;
}
