<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Payment\Application\Port\Out\Identity\IRefundIdGenerator;
use Modules\Payment\Domain\ValueObject\RefundId;

final readonly class LaravelRefundIdGenerator implements IRefundIdGenerator
{
    public function generate(): RefundId
    {
        return new RefundId(Str::uuid7()->toString());
    }
}
