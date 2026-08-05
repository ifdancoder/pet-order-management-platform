<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\ActivateUser\ActivateUserCommand;
use Modules\Identity\Application\Command\ActivateUser\ActivateUserHandler;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;

final readonly class ActivateUserController
{
    public function __construct(
        private ActivateUserHandler $handler,
    ) {}

    public function __invoke(string $userId): UserResource
    {
        $user = ($this->handler)(new ActivateUserCommand($userId));

        return new UserResource($user);
    }
}
