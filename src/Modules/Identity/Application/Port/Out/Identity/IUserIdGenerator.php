<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Port\Out\Identity;

use Modules\Identity\Domain\ValueObject\UserId;

interface IUserIdGenerator
{
    public function generate(): UserId;
}
