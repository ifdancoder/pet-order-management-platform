<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Data;

final readonly class GatewayPaymentRequest
{
    public function __construct(
        public string $paymentId,
        public string $orderId,
        public int $amount,
        public string $currency,
        public string $idempotencyKey,
    ) {}
}
