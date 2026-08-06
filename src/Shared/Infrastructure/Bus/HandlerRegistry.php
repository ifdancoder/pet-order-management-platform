<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus;

use Shared\Infrastructure\Bus\Exception\HandlerResolutionFailed;

final class HandlerRegistry
{
    /** @var array<class-string, class-string> */
    private array $handlers = [];

    /**
     * @param  class-string  $messageClass
     * @param  class-string  $handlerClass
     */
    public function register(string $messageClass, string $handlerClass): void
    {
        if (isset($this->handlers[$messageClass])) {
            throw HandlerResolutionFailed::alreadyRegistered($messageClass);
        }

        $this->handlers[$messageClass] = $handlerClass;
    }

    /** @return class-string */
    public function handlerFor(object $message): string
    {
        return $this->handlers[$message::class]
            ?? throw HandlerResolutionFailed::notRegistered($message::class);
    }
}
