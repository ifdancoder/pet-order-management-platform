<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\UpdateUser\UpdateUserCommand;
use Modules\Identity\Application\Command\UpdateUser\UpdateUserHandler;
use Modules\Identity\Presentation\Http\V1\Request\UpdateUserRequest;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;

final readonly class UpdateUserController
{
    public function __construct(
        private UpdateUserHandler $handler,
    ) {}

    public function __invoke(UpdateUserRequest $request, string $userId): UserResource
    {
        $user = ($this->handler)(new UpdateUserCommand(
            userId: $userId,
            email: $request->email(),
            password: $request->password(),
        ));

        return new UserResource($user);
    }
}
