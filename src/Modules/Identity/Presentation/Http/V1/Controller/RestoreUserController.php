<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\RestoreUser\RestoreUserCommand;
use Modules\Identity\Application\Command\RestoreUser\RestoreUserHandler;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;

final readonly class RestoreUserController
{
    public function __construct(
        private RestoreUserHandler $handler,
    ) {}

    public function __invoke(string $userId): UserResource
    {
        $user = ($this->handler)(new RestoreUserCommand($userId));

        return new UserResource($user);
    }
}
