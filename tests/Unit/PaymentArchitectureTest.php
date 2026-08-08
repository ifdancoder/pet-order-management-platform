<?php

declare(strict_types=1);

use Modules\Payment\Application\Command\RequestPayment\RequestPaymentCommand;
use Shared\Application\Bus\Command\ICommand;

arch('payment domain has no framework or outer-layer dependencies')
    ->expect('Modules\Payment\Domain')
    ->not->toUse([
        'Illuminate',
        'Modules\Payment\Application',
        'Modules\Payment\Infrastructure',
        'Modules\Payment\Presentation',
    ]);

arch('payment application has no framework or adapter dependencies')
    ->expect('Modules\Payment\Application')
    ->not->toUse([
        'Illuminate',
        'Modules\Payment\Infrastructure',
        'Modules\Payment\Presentation',
    ]);

arch('payment does not depend on another modules infrastructure')
    ->expect('Modules\Payment')
    ->not->toUse([
        'Modules\Customer\Infrastructure',
        'Modules\Identity\Infrastructure',
        'Modules\Inventory\Infrastructure',
        'Modules\Notification\Infrastructure',
        'Modules\Order\Infrastructure',
        'Modules\Promotion\Infrastructure',
        'Modules\Return\Infrastructure',
        'Modules\Shipping\Infrastructure',
    ]);

arch('payment application ports use the interface prefix')
    ->expect('Modules\Payment\Application\Port')
    ->interfaces()
    ->toHavePrefix('I');

arch('payment commands declare their result contract')
    ->expect([RequestPaymentCommand::class])
    ->classes()
    ->toImplement(ICommand::class);
