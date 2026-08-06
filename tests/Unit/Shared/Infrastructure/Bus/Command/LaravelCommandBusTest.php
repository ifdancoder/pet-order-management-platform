<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Shared\Application\Bus\Command\ICommand;
use Shared\Application\Bus\Command\ICommandMiddleware;
use Shared\Infrastructure\Bus\Command\LaravelCommandBus;
use Shared\Infrastructure\Bus\Exception\HandlerResolutionFailed;
use Shared\Infrastructure\Bus\HandlerInvoker;
use Shared\Infrastructure\Bus\HandlerRegistry;

/** @implements ICommand<string> */
final readonly class CommandBusTestCommand implements ICommand
{
    public function __construct(
        public string $value,
    ) {}
}

final readonly class CommandBusTestHandler
{
    public function __invoke(CommandBusTestCommand $command): string
    {
        return mb_strtoupper($command->value);
    }
}

final class CommandBusTestMiddleware implements ICommandMiddleware
{
    /**
     * @param  ICommand<mixed>  $command
     * @param  \Closure(ICommand<mixed>): mixed  $next
     */
    public function process(ICommand $command, \Closure $next): mixed
    {
        return 'middleware:'.$next($command);
    }
}

final class CommandBusTestNonInvokableHandler {}

it('dispatches a command through middleware to its handler', function () {
    $container = new Container;
    $handlers = new HandlerRegistry;
    $handlers->register(CommandBusTestCommand::class, CommandBusTestHandler::class);
    $bus = new LaravelCommandBus(
        invoker: new HandlerInvoker($container, $handlers),
        middleware: [new CommandBusTestMiddleware],
    );

    $result = $bus->dispatch(new CommandBusTestCommand('handled'));

    expect($result)->toBe('middleware:HANDLED');
});

it('rejects a handler that is not invokable', function () {
    $container = new Container;
    $handlers = new HandlerRegistry;
    $handlers->register(
        CommandBusTestCommand::class,
        CommandBusTestNonInvokableHandler::class,
    );
    $bus = new LaravelCommandBus(
        invoker: new HandlerInvoker($container, $handlers),
        middleware: [],
    );

    $bus->dispatch(new CommandBusTestCommand('unhandled'));
})->throws(HandlerResolutionFailed::class);
