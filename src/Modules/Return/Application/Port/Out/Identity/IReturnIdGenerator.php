<?php

declare(strict_types=1);

namespace Modules\Return\Application\Port\Out\Identity;

use Modules\Return\Domain\ValueObject\ReturnId;

interface IReturnIdGenerator
{
    public function generate(): ReturnId;
}
