<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Port\Out\Gateway;

use Modules\Payment\Domain\Enum\PaymentProvider;

interface IPaymentGatewayResolver
{
    public function resolve(PaymentProvider $provider): IPaymentGateway;
}
