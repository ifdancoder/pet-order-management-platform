<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Illuminate\Http\JsonResponse;
use Modules\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use Modules\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use Modules\Identity\Presentation\Http\V1\Request\RegisterRequest;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;

final readonly class RegisterController
{
    public function __construct(
        private RegisterUserHandler $handler,
    ) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = ($this->handler)(new RegisterUserCommand(
            email: $request->email(),
            password: $request->password(),
        ));

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
