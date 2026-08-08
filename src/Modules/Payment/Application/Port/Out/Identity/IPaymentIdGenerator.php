<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Port\Out\Identity;

use Modules\Payment\Domain\ValueObject\PaymentId;

interface IPaymentIdGenerator
{
    public function generate(): PaymentId;
}
