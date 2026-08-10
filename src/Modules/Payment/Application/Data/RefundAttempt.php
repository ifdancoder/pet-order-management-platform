<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Data;

use Modules\Payment\Domain\Enum\PaymentProvider;

final readonly class RefundAttempt
{
    public function __construct(
        public string $refundId,
        public string $returnId,
        public string $claimToken,
        public string $providerPaymentId,
        public PaymentProvider $provider,
        public int $amount,
        public string $currency,
        public int $attempts,
    ) {}
}
