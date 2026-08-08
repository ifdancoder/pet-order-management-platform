<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Port\Out\Gateway;

use Modules\Payment\Application\Data\GatewayPaymentRequest;
use Modules\Payment\Application\Data\GatewayPaymentResult;
use Modules\Payment\Domain\Enum\PaymentProvider;

interface IPaymentGateway
{
    public function provider(): PaymentProvider;

    public function authorize(GatewayPaymentRequest $request): GatewayPaymentResult;

    public function capture(
        string $providerPaymentId,
        string $idempotencyKey,
    ): GatewayPaymentResult;

    public function refund(
        string $providerPaymentId,
        int $amount,
        string $currency,
        string $idempotencyKey,
    ): GatewayPaymentResult;
}
