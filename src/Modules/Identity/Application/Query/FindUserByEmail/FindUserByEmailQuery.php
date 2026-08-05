<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Query\FindUserByEmail;

final readonly class FindUserByEmailQuery
{
    public function __construct(
        public string $email,
    ) {}
}
