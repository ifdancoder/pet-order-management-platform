<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Port\Out\Webhook;

use Modules\Payment\Application\Data\PaymentWebhookRequest;
use Modules\Payment\Application\Data\VerifiedPaymentWebhook;
use Modules\Payment\Domain\Enum\PaymentProvider;

interface IPaymentWebhookVerifier
{
    public function provider(): PaymentProvider;

    public function verify(PaymentWebhookRequest $request): VerifiedPaymentWebhook;
}
