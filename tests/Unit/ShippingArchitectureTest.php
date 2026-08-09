<?php

declare(strict_types=1);

use Modules\Shipping\Application\Query\CalculateShippingCost\CalculateShippingCostQuery;
use Shared\Application\Bus\Query\IQuery;

arch('shipping domain has no framework or outer-layer dependencies')
    ->expect('Modules\Shipping\Domain')
    ->not->toUse([
        'Illuminate',
        'Modules\Shipping\Application',
        'Modules\Shipping\Infrastructure',
        'Modules\Shipping\Presentation',
    ]);

arch('shipping application has no framework or adapter dependencies')
    ->expect('Modules\Shipping\Application')
    ->not->toUse([
        'Illuminate',
        'Modules\Shipping\Infrastructure',
        'Modules\Shipping\Presentation',
    ]);

arch('shipping does not depend on another modules infrastructure')
    ->expect('Modules\Shipping')
    ->not->toUse([
        'Modules\Customer\Infrastructure',
        'Modules\Identity\Infrastructure',
        'Modules\Inventory\Infrastructure',
        'Modules\Notification\Infrastructure',
        'Modules\Order\Infrastructure',
        'Modules\Payment\Infrastructure',
        'Modules\Promotion\Infrastructure',
        'Modules\Return\Infrastructure',
    ]);

arch('shipping interfaces use the interface prefix')
    ->expect('Modules\Shipping')
    ->interfaces()
    ->toHavePrefix('I');

arch('shipping queries declare their result contract')
    ->expect([CalculateShippingCostQuery::class])
    ->classes()
    ->toImplement(IQuery::class);
