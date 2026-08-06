<?php

declare(strict_types=1);

use Shared\Infrastructure\Bus\Exception\HandlerResolutionFailed;
use Shared\Infrastructure\Bus\HandlerRegistry;

it('resolves the registered handler class', function () {
    $message = new stdClass;
    $registry = new HandlerRegistry;
    $registry->register(stdClass::class, ArrayObject::class);

    $handlerClass = $registry->handlerFor($message);

    expect($handlerClass)->toBe(ArrayObject::class);
});

it('rejects duplicate handler registration', function () {
    $registry = new HandlerRegistry;
    $registry->register(stdClass::class, ArrayObject::class);

    $registry->register(stdClass::class, ArrayIterator::class);
})->throws(HandlerResolutionFailed::class);

it('rejects a message without a registered handler', function () {
    (new HandlerRegistry)->handlerFor(new stdClass);
})->throws(HandlerResolutionFailed::class);
