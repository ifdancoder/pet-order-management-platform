<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus;

use Illuminate\Contracts\Container\Container;
use Shared\Infrastructure\Bus\Exception\HandlerResolutionFailed;

final readonly class HandlerInvoker
{
    public function __construct(
        private Container $container,
        private HandlerRegistry $handlers,
    ) {}

    public function invoke(object $message): mixed
    {
        $handlerClass = $this->handlers->handlerFor($message);
        $handler = $this->container->make($handlerClass);

        if (! is_callable($handler)) {
            throw HandlerResolutionFailed::notInvokable($handlerClass);
        }

        return $handler($message);
    }
}
