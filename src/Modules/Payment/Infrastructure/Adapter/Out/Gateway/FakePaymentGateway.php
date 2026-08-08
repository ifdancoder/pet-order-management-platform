<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Gateway;

use Modules\Payment\Application\Data\GatewayPaymentRequest;
use Modules\Payment\Application\Data\GatewayPaymentResult;
use Modules\Payment\Application\Port\Out\Gateway\IPaymentGateway;
use Modules\Payment\Domain\Enum\PaymentProvider;

final readonly class FakePaymentGateway implements IPaymentGateway
{
    public function __construct(
        private bool $decline = false,
    ) {}

    public function provider(): PaymentProvider
    {
        return PaymentProvider::Fake;
    }

    public function authorize(GatewayPaymentRequest $request): GatewayPaymentResult
    {
        if ($this->decline) {
            return GatewayPaymentResult::failed('payment_declined');
        }

        return GatewayPaymentResult::authorized(
            'fake_'.substr(hash('sha256', $request->idempotencyKey), 0, 32),
        );
    }

    public function capture(
        string $providerPaymentId,
        string $idempotencyKey,
    ): GatewayPaymentResult {
        return GatewayPaymentResult::authorized($providerPaymentId);
    }

    public function refund(
        string $providerPaymentId,
        int $amount,
        string $currency,
        string $idempotencyKey,
    ): GatewayPaymentResult {
        return GatewayPaymentResult::authorized($providerPaymentId);
    }
}
