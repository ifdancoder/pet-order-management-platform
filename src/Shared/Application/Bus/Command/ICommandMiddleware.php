<?php

declare(strict_types=1);

namespace Shared\Application\Bus\Command;

use Closure;

interface ICommandMiddleware
{
    /**
     * @param  ICommand<mixed>  $command
     * @param  Closure(ICommand<mixed>): mixed  $next
     */
    public function process(ICommand $command, Closure $next): mixed;
}
