<?php

declare(strict_types=1);

arch('notification domain has no framework or outer-layer dependencies')
    ->expect('Modules\Notification\Domain')
    ->not->toUse([
        'Illuminate',
        'Modules\Notification\Application',
        'Modules\Notification\Infrastructure',
        'Modules\Notification\Presentation',
    ]);

arch('notification application has no framework or adapter dependencies')
    ->expect('Modules\Notification\Application')
    ->not->toUse([
        'Illuminate',
        'Modules\Notification\Infrastructure',
        'Modules\Notification\Presentation',
    ]);

arch('notification does not depend on another modules infrastructure')
    ->expect('Modules\Notification')
    ->not->toUse([
        'Modules\Customer\Infrastructure',
        'Modules\Identity\Infrastructure',
        'Modules\Inventory\Infrastructure',
        'Modules\Order\Infrastructure',
        'Modules\Payment\Infrastructure',
        'Modules\Promotion\Infrastructure',
        'Modules\Return\Infrastructure',
        'Modules\Shipping\Infrastructure',
    ]);

arch('notification application ports use the interface prefix')
    ->expect('Modules\Notification\Application\Port')
    ->interfaces()
    ->toHavePrefix('I');
