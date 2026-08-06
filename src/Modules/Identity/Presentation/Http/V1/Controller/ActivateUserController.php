<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\ActivateUser\ActivateUserCommand;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class ActivateUserController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(string $userId): UserResource
    {
        $user = $this->commandBus->dispatch(new ActivateUserCommand($userId));

        return new UserResource($user);
    }
}
