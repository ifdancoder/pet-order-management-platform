<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\RestoreUser\RestoreUserCommand;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class RestoreUserController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(string $userId): UserResource
    {
        $user = $this->commandBus->dispatch(new RestoreUserCommand($userId));

        return new UserResource($user);
    }
}
