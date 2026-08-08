<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Data;

use Modules\Payment\Domain\Enum\PaymentProvider;

final readonly class PaymentWebhookRequest
{
    public function __construct(
        public PaymentProvider $provider,
        public string $payload,
        public ?string $signature,
        public ?string $transmissionId,
        public ?string $transmissionTime,
        public ?string $certificateUrl,
        public ?string $authAlgorithm,
    ) {}
}
