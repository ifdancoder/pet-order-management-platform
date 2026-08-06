<?php

declare(strict_types=1);

namespace Modules\Order\Application\Port\Out\Identity;

use Modules\Order\Domain\ValueObject\OrderId;

interface IOrderIdGenerator
{
    public function generate(): OrderId;
}
