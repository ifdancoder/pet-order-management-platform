<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Query\GetUser;

final readonly class GetUserQuery
{
    public function __construct(
        public string $userId,
    ) {}
}
