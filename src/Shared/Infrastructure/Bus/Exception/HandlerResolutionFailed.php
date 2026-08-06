<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Exception;

use LogicException;

final class HandlerResolutionFailed extends LogicException
{
    public static function notRegistered(string $messageClass): self
    {
        return new self(sprintf(
            'No handler is registered for [%s].',
            $messageClass,
        ));
    }

    public static function alreadyRegistered(string $messageClass): self
    {
        return new self(sprintf(
            'A handler is already registered for [%s].',
            $messageClass,
        ));
    }

    public static function notInvokable(string $handlerClass): self
    {
        return new self(sprintf(
            'The handler [%s] must be invokable.',
            $handlerClass,
        ));
    }
}
