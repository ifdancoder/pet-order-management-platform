<?php

declare(strict_types=1);

namespace Shared\Application\Bus\Command;

interface ICommandBus
{
    /**
     * @template TResult
     *
     * @param  ICommand<TResult>  $command
     * @return TResult
     */
    public function dispatch(ICommand $command): mixed;
}
