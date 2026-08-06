<?php

declare(strict_types=1);

arch('identity domain has no framework or outer-layer dependencies')
    ->expect('Modules\Identity\Domain')
    ->not->toUse([
        'Illuminate',
        'Modules\Identity\Application',
        'Modules\Identity\Infrastructure',
        'Modules\Identity\Presentation',
    ]);

arch('identity application has no framework or adapter dependencies')
    ->expect('Modules\Identity\Application')
    ->not->toUse([
        'Illuminate',
        'Modules\Identity\Infrastructure',
        'Modules\Identity\Presentation',
    ]);

arch('identity presentation does not depend on infrastructure')
    ->expect('Modules\Identity\Presentation')
    ->not->toUse('Modules\Identity\Infrastructure');

arch('identity infrastructure does not depend on presentation')
    ->expect('Modules\Identity\Infrastructure')
    ->not->toUse('Modules\Identity\Presentation');

arch('identity does not depend on another modules infrastructure')
    ->expect('Modules\Identity')
    ->not->toUse([
        'Modules\Customer\Infrastructure',
        'Modules\Inventory\Infrastructure',
        'Modules\Notification\Infrastructure',
        'Modules\Order\Infrastructure',
        'Modules\Payment\Infrastructure',
        'Modules\Promotion\Infrastructure',
        'Modules\Return\Infrastructure',
        'Modules\Shipping\Infrastructure',
    ]);

arch('identity application ports use the interface prefix')
    ->expect('Modules\Identity\Application\Port')
    ->interfaces()
    ->toHavePrefix('I');
