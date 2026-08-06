<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Port\Out\Identity;

use Modules\Customer\Domain\ValueObject\CustomerId;

interface ICustomerIdGenerator
{
    public function generate(): CustomerId;
}
