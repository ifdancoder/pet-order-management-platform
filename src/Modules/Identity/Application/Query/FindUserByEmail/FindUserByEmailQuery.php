<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Query\FindUserByEmail;

use Modules\Identity\Domain\Entity\User;
use Shared\Application\Bus\Query\IQuery;

/** @implements IQuery<User> */
final readonly class FindUserByEmailQuery implements IQuery
{
    public function __construct(
        public string $email,
    ) {}
}
