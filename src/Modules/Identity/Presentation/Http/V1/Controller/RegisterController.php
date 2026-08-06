<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Illuminate\Http\JsonResponse;
use Modules\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use Modules\Identity\Presentation\Http\V1\Request\RegisterRequest;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class RegisterController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new RegisterUserCommand(
            email: $request->email(),
            password: $request->password(),
        ));

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
