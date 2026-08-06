<?php

declare(strict_types=1);

use Shared\Application\Bus\Command\ICommand;
use Shared\Application\Bus\Query\IQuery;

arch('customer domain has no framework or outer-layer dependencies')
    ->expect('Modules\Customer\Domain')
    ->not->toUse([
        'Illuminate',
        'Modules\Customer\Application',
        'Modules\Customer\Infrastructure',
        'Modules\Customer\Presentation',
    ]);

arch('customer application has no framework or adapter dependencies')
    ->expect('Modules\Customer\Application')
    ->not->toUse([
        'Illuminate',
        'Modules\Customer\Infrastructure',
        'Modules\Customer\Presentation',
    ]);

arch('customer does not depend on another modules infrastructure')
    ->expect('Modules\Customer')
    ->not->toUse([
        'Modules\Identity\Infrastructure',
        'Modules\Inventory\Infrastructure',
        'Modules\Notification\Infrastructure',
        'Modules\Order\Infrastructure',
        'Modules\Payment\Infrastructure',
        'Modules\Promotion\Infrastructure',
        'Modules\Return\Infrastructure',
        'Modules\Shipping\Infrastructure',
    ]);

arch('customer application ports use the interface prefix')
    ->expect('Modules\Customer\Application\Port')
    ->interfaces()
    ->toHavePrefix('I');

arch('customer commands declare their result contract')
    ->expect([
        'Modules\Customer\Application\Command\AddAddress\AddAddressCommand',
        'Modules\Customer\Application\Command\MakeAddressDefault\MakeAddressDefaultCommand',
        'Modules\Customer\Application\Command\RegisterCustomer\RegisterCustomerCommand',
        'Modules\Customer\Application\Command\RemoveAddress\RemoveAddressCommand',
        'Modules\Customer\Application\Command\UpdateAddress\UpdateAddressCommand',
        'Modules\Customer\Application\Command\UpdateCustomer\UpdateCustomerCommand',
    ])
    ->classes()
    ->toImplement(ICommand::class);

arch('customer queries declare their result contract')
    ->expect([
        'Modules\Customer\Application\Query\GetCustomer\GetCustomerQuery',
        'Modules\Customer\Application\Query\GetCustomerByIdentity\GetCustomerByIdentityQuery',
    ])
    ->classes()
    ->toImplement(IQuery::class);
