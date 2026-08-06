<?php

declare(strict_types=1);

arch('promotion domain has no framework or outer-layer dependencies')
    ->expect('Modules\Promotion\Domain')
    ->not->toUse([
        'Illuminate',
        'Modules\Promotion\Application',
        'Modules\Promotion\Infrastructure',
        'Modules\Promotion\Presentation',
    ]);

arch('promotion application has no framework or adapter dependencies')
    ->expect('Modules\Promotion\Application')
    ->not->toUse([
        'Illuminate',
        'Modules\Promotion\Infrastructure',
        'Modules\Promotion\Presentation',
    ]);

arch('promotion does not depend on another modules infrastructure')
    ->expect('Modules\Promotion')
    ->not->toUse([
        'Modules\Customer\Infrastructure',
        'Modules\Identity\Infrastructure',
        'Modules\Inventory\Infrastructure',
        'Modules\Notification\Infrastructure',
        'Modules\Order\Infrastructure',
        'Modules\Payment\Infrastructure',
        'Modules\Return\Infrastructure',
        'Modules\Shipping\Infrastructure',
    ]);

arch('promotion ports and domain strategies use the interface prefix')
    ->expect([
        'Modules\Promotion\Application\Port',
        'Modules\Promotion\Domain\Discount\IDiscountPolicy',
        'Modules\Promotion\Domain\Specification\IPromotionSpecification',
    ])
    ->interfaces()
    ->toHavePrefix('I');
