<?php

declare(strict_types=1);

use Shared\Application\Bus\Command\ICommand;
use Shared\Application\Bus\Query\IQuery;

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

arch('identity commands declare their result contract')
    ->expect([
        'Modules\Identity\Application\Command\ActivateUser\ActivateUserCommand',
        'Modules\Identity\Application\Command\DisableUser\DisableUserCommand',
        'Modules\Identity\Application\Command\Login\LoginCommand',
        'Modules\Identity\Application\Command\Logout\LogoutCommand',
        'Modules\Identity\Application\Command\RefreshAccessToken\RefreshAccessTokenCommand',
        'Modules\Identity\Application\Command\RegisterUser\RegisterUserCommand',
        'Modules\Identity\Application\Command\RestoreUser\RestoreUserCommand',
        'Modules\Identity\Application\Command\SuspendUser\SuspendUserCommand',
        'Modules\Identity\Application\Command\UpdateUser\UpdateUserCommand',
    ])
    ->classes()
    ->toImplement(ICommand::class);

arch('identity queries declare their result contract')
    ->expect([
        'Modules\Identity\Application\Query\FindUserByEmail\FindUserByEmailQuery',
        'Modules\Identity\Application\Query\GetUser\GetUserQuery',
    ])
    ->classes()
    ->toImplement(IQuery::class);

arch('identity presentation uses buses instead of handlers')
    ->expect([
        'Modules\Identity\Application\Command\ActivateUser\ActivateUserHandler',
        'Modules\Identity\Application\Command\DisableUser\DisableUserHandler',
        'Modules\Identity\Application\Command\Login\LoginHandler',
        'Modules\Identity\Application\Command\Logout\LogoutHandler',
        'Modules\Identity\Application\Command\RefreshAccessToken\RefreshAccessTokenHandler',
        'Modules\Identity\Application\Command\RegisterUser\RegisterUserHandler',
        'Modules\Identity\Application\Command\RestoreUser\RestoreUserHandler',
        'Modules\Identity\Application\Command\SuspendUser\SuspendUserHandler',
        'Modules\Identity\Application\Command\UpdateUser\UpdateUserHandler',
        'Modules\Identity\Application\Query\FindUserByEmail\FindUserByEmailHandler',
        'Modules\Identity\Application\Query\GetUser\GetUserHandler',
    ])
    ->not->toBeUsedIn('Modules\Identity\Presentation');
