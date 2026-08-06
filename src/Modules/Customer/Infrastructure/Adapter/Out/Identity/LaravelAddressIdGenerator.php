<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Customer\Application\Port\Out\Identity\IAddressIdGenerator;
use Modules\Customer\Domain\ValueObject\AddressId;

final class LaravelAddressIdGenerator implements IAddressIdGenerator
{
    public function generate(): AddressId
    {
        return new AddressId(Str::uuid7()->toString());
    }
}
