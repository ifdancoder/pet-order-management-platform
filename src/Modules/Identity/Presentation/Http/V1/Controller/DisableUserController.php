<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\DisableUser\DisableUserCommand;
use Modules\Identity\Application\Command\DisableUser\DisableUserHandler;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;

final readonly class DisableUserController
{
    public function __construct(
        private DisableUserHandler $handler,
    ) {}

    public function __invoke(string $userId): UserResource
    {
        $user = ($this->handler)(new DisableUserCommand($userId));

        return new UserResource($user);
    }
}
