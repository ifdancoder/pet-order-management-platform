<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Port\Out\Identity;

use Modules\Customer\Domain\ValueObject\AddressId;

interface IAddressIdGenerator
{
    public function generate(): AddressId;
}
