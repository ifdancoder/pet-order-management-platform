<?php

declare(strict_types=1);

use Modules\Order\Application\Command\Checkout\CheckoutCommand;
use Shared\Application\Bus\Command\ICommand;
use Shared\Application\Bus\Query\IQuery;

arch('order domain has no framework or outer-layer dependencies')
    ->expect('Modules\Order\Domain')
    ->not->toUse([
        'Illuminate',
        'Modules\Order\Application',
        'Modules\Order\Infrastructure',
        'Modules\Order\Presentation',
    ]);

arch('order application has no framework or adapter dependencies')
    ->expect('Modules\Order\Application')
    ->not->toUse([
        'Illuminate',
        'Modules\Order\Infrastructure',
        'Modules\Order\Presentation',
    ]);

arch('order does not depend on another modules infrastructure')
    ->expect('Modules\Order')
    ->not->toUse([
        'Modules\Customer\Infrastructure',
        'Modules\Identity\Infrastructure',
        'Modules\Inventory\Infrastructure',
        'Modules\Notification\Infrastructure',
        'Modules\Payment\Infrastructure',
        'Modules\Promotion\Infrastructure',
        'Modules\Return\Infrastructure',
        'Modules\Shipping\Infrastructure',
    ]);

arch('order application ports use the interface prefix')
    ->expect('Modules\Order\Application\Port')
    ->interfaces()
    ->toHavePrefix('I');

arch('order commands declare their result contract')
    ->expect([
        'Modules\Order\Application\Command\AddOrderItem\AddOrderItemCommand',
        CheckoutCommand::class,
        'Modules\Order\Application\Command\CreateOrderDraft\CreateOrderDraftCommand',
        'Modules\Order\Application\Command\PlaceOrder\PlaceOrderCommand',
        'Modules\Order\Application\Command\RemoveOrderItem\RemoveOrderItemCommand',
        'Modules\Order\Application\Command\TransitionOrder\TransitionOrderCommand',
    ])
    ->classes()
    ->toImplement(ICommand::class);

arch('order queries declare their result contract')
    ->expect([
        'Modules\Order\Application\Query\GetOrder\GetOrderQuery',
    ])
    ->classes()
    ->toImplement(IQuery::class);
