<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Shared\Application\Bus\Query\IQuery;
use Shared\Infrastructure\Bus\HandlerInvoker;
use Shared\Infrastructure\Bus\HandlerRegistry;
use Shared\Infrastructure\Bus\Query\LaravelQueryBus;

/** @implements IQuery<string> */
final readonly class QueryBusTestQuery implements IQuery
{
    public function __construct(
        public string $value,
    ) {}
}

final readonly class QueryBusTestHandler
{
    public function __invoke(QueryBusTestQuery $query): string
    {
        return 'result:'.$query->value;
    }
}

it('asks the registered handler for a query result', function () {
    $container = new Container;
    $handlers = new HandlerRegistry;
    $handlers->register(QueryBusTestQuery::class, QueryBusTestHandler::class);
    $bus = new LaravelQueryBus(new HandlerInvoker($container, $handlers));

    $result = $bus->ask(new QueryBusTestQuery('found'));

    expect($result)->toBe('result:found');
});
