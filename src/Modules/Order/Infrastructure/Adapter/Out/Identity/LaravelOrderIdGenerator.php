<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Order\Application\Port\Out\Identity\IOrderIdGenerator;
use Modules\Order\Domain\ValueObject\OrderId;

final class LaravelOrderIdGenerator implements IOrderIdGenerator
{
    public function generate(): OrderId
    {
        return new OrderId(Str::uuid7()->toString());
    }
}
