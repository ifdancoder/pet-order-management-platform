<?php

declare(strict_types=1);

use Shared\Application\Bus\Command\ICommand;
use Shared\Application\Bus\Query\IQuery;

arch('inventory domain has no framework or outer-layer dependencies')
    ->expect('Modules\Inventory\Domain')
    ->not->toUse([
        'Illuminate',
        'Modules\Inventory\Application',
        'Modules\Inventory\Infrastructure',
        'Modules\Inventory\Presentation',
    ]);

arch('inventory application has no framework or adapter dependencies')
    ->expect('Modules\Inventory\Application')
    ->not->toUse([
        'Illuminate',
        'Modules\Inventory\Infrastructure',
        'Modules\Inventory\Presentation',
    ]);

arch('inventory does not depend on another modules infrastructure')
    ->expect('Modules\Inventory')
    ->not->toUse([
        'Modules\Customer\Infrastructure',
        'Modules\Identity\Infrastructure',
        'Modules\Notification\Infrastructure',
        'Modules\Order\Infrastructure',
        'Modules\Payment\Infrastructure',
        'Modules\Promotion\Infrastructure',
        'Modules\Return\Infrastructure',
        'Modules\Shipping\Infrastructure',
    ]);

arch('inventory application ports use the interface prefix')
    ->expect('Modules\Inventory\Application\Port')
    ->interfaces()
    ->toHavePrefix('I');

arch('inventory commands declare their result contract')
    ->expect([
        'Modules\Inventory\Application\Command\CreateInventoryItem\CreateInventoryItemCommand',
        'Modules\Inventory\Application\Command\ReleaseReservation\ReleaseReservationCommand',
        'Modules\Inventory\Application\Command\ReserveStock\ReserveStockCommand',
        'Modules\Inventory\Application\Command\RestockInventoryItem\RestockInventoryItemCommand',
    ])
    ->classes()
    ->toImplement(ICommand::class);

arch('inventory queries declare their result contract')
    ->expect([
        'Modules\Inventory\Application\Query\GetInventoryItem\GetInventoryItemQuery',
    ])
    ->classes()
    ->toImplement(IQuery::class);
