<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Port\Out\Webhook;

use Modules\Payment\Domain\Enum\PaymentProvider;

interface IPaymentWebhookVerifierResolver
{
    public function resolve(PaymentProvider $provider): IPaymentWebhookVerifier;
}
