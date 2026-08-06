<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Query\GetUser\GetUserQuery;
use Modules\Identity\Presentation\Http\V1\Resource\UserResource;
use Shared\Application\Bus\Query\IQueryBus;

final readonly class GetUserController
{
    public function __construct(
        private IQueryBus $queryBus,
    ) {}

    public function __invoke(string $userId): UserResource
    {
        $user = $this->queryBus->ask(
            new GetUserQuery(
                userId: $userId,
            ),
        );

        return new UserResource($user);
    }
}
