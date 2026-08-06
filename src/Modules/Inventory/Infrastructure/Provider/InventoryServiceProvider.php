<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Checkout\InventoryCheckout;
use Modules\Inventory\Application\Command\CreateInventoryItem\CreateInventoryItemCommand;
use Modules\Inventory\Application\Command\CreateInventoryItem\CreateInventoryItemHandler;
use Modules\Inventory\Application\Command\ReleaseReservation\ReleaseReservationCommand;
use Modules\Inventory\Application\Command\ReleaseReservation\ReleaseReservationHandler;
use Modules\Inventory\Application\Command\ReserveStock\ReserveStockCommand;
use Modules\Inventory\Application\Command\ReserveStock\ReserveStockHandler;
use Modules\Inventory\Application\Command\RestockInventoryItem\RestockInventoryItemCommand;
use Modules\Inventory\Application\Command\RestockInventoryItem\RestockInventoryItemHandler;
use Modules\Inventory\Application\Port\In\IInventoryCheckout;
use Modules\Inventory\Application\Port\Out\Identity\IInventoryItemIdGenerator;
use Modules\Inventory\Application\Port\Out\Identity\IReservationIdGenerator;
use Modules\Inventory\Application\Port\Out\Persistence\IInventoryItemRepository;
use Modules\Inventory\Application\Port\Out\Persistence\IReservationRepository;
use Modules\Inventory\Application\Query\GetInventoryItem\GetInventoryItemHandler;
use Modules\Inventory\Application\Query\GetInventoryItem\GetInventoryItemQuery;
use Modules\Inventory\Infrastructure\Adapter\Out\Identity\LaravelInventoryItemIdGenerator;
use Modules\Inventory\Infrastructure\Adapter\Out\Identity\LaravelReservationIdGenerator;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentInventoryItemRepository;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentReservationRepository;
use Shared\Infrastructure\Bus\HandlerRegistry;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            IInventoryItemRepository::class,
            EloquentInventoryItemRepository::class,
        );
        $this->app->bind(
            IReservationRepository::class,
            EloquentReservationRepository::class,
        );
        $this->app->bind(
            IInventoryItemIdGenerator::class,
            LaravelInventoryItemIdGenerator::class,
        );
        $this->app->bind(
            IReservationIdGenerator::class,
            LaravelReservationIdGenerator::class,
        );
        $this->app->bind(IInventoryCheckout::class, InventoryCheckout::class);
    }

    public function boot(): void
    {
        $handlers = $this->app->make(HandlerRegistry::class);

        foreach ([
            CreateInventoryItemCommand::class => CreateInventoryItemHandler::class,
            ReleaseReservationCommand::class => ReleaseReservationHandler::class,
            ReserveStockCommand::class => ReserveStockHandler::class,
            RestockInventoryItemCommand::class => RestockInventoryItemHandler::class,
            GetInventoryItemQuery::class => GetInventoryItemHandler::class,
        ] as $messageClass => $handlerClass) {
            $handlers->register($messageClass, $handlerClass);
        }
    }
}
