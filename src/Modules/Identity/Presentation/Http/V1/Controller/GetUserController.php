<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Query\GetUser\GetUserHandler;
use Modules\Identity\Application\Query\GetUser\GetUserQuery;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;

final readonly class GetUserController
{
    public function __construct(
        private GetUserHandler $handler,
    ) {}

    public function __invoke(string $userId): UserResource
    {
        $user = ($this->handler)(
            new GetUserQuery(
                userId: $userId,
            ),
        );

        return new UserResource($user);
    }
}
