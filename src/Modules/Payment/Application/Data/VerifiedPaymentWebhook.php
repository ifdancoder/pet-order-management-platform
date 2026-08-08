<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Data;

use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;

final readonly class VerifiedPaymentWebhook
{
    public function __construct(
        public string $eventId,
        public PaymentProvider $provider,
        public string $lookupProviderPaymentId,
        public string $providerPaymentId,
        public PaymentStatus $status,
        public ?string $failureCode = null,
    ) {}
}
