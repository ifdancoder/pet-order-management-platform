<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Payment\Application\Port\Out\Identity\IPaymentIdGenerator;
use Modules\Payment\Domain\ValueObject\PaymentId;

final class LaravelPaymentIdGenerator implements IPaymentIdGenerator
{
    public function generate(): PaymentId
    {
        return new PaymentId(Str::uuid7()->toString());
    }
}
