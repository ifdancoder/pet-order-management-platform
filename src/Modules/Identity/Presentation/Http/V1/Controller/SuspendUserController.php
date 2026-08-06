<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\SuspendUser\SuspendUserCommand;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class SuspendUserController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(string $userId): UserResource
    {
        $user = $this->commandBus->dispatch(new SuspendUserCommand($userId));

        return new UserResource($user);
    }
}
