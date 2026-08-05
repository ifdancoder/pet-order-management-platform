<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\SuspendUser\SuspendUserCommand;
use Modules\Identity\Application\Command\SuspendUser\SuspendUserHandler;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;

final readonly class SuspendUserController
{
    public function __construct(
        private SuspendUserHandler $handler,
    ) {}

    public function __invoke(string $userId): UserResource
    {
        $user = ($this->handler)(new SuspendUserCommand($userId));

        return new UserResource($user);
    }
}
