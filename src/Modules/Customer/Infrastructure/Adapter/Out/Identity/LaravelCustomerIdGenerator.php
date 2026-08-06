<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Customer\Application\Port\Out\Identity\ICustomerIdGenerator;
use Modules\Customer\Domain\ValueObject\CustomerId;

final class LaravelCustomerIdGenerator implements ICustomerIdGenerator
{
    public function generate(): CustomerId
    {
        return new CustomerId(Str::uuid7()->toString());
    }
}
