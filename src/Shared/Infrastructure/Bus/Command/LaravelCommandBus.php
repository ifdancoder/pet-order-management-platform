<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Command;

use Closure;
use Shared\Application\Bus\Command\ICommand;
use Shared\Application\Bus\Command\ICommandBus;
use Shared\Application\Bus\Command\ICommandMiddleware;
use Shared\Infrastructure\Bus\HandlerInvoker;

final readonly class LaravelCommandBus implements ICommandBus
{
    /**
     * @param  list<ICommandMiddleware>  $middleware
     */
    public function __construct(
        private HandlerInvoker $invoker,
        private array $middleware,
    ) {}

    /** @param ICommand<mixed> $command */
    public function dispatch(ICommand $command): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            static fn (Closure $next, ICommandMiddleware $middleware): Closure => static fn (ICommand $message): mixed => $middleware->process(
                $message,
                $next,
            ),
            fn (ICommand $message): mixed => $this->invoker->invoke($message),
        );

        return $pipeline($command);
    }
}
