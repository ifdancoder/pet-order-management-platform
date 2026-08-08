<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Data;

use InvalidArgumentException;

final readonly class GatewayPaymentResult
{
    private function __construct(
        public bool $succeeded,
        public ?string $providerPaymentId,
        public ?string $failureCode,
    ) {}

    public static function authorized(string $providerPaymentId): self
    {
        if ($providerPaymentId === '') {
            throw new InvalidArgumentException(
                'Provider payment ID cannot be empty.',
            );
        }

        return new self(true, $providerPaymentId, null);
    }

    public static function failed(string $failureCode): self
    {
        if ($failureCode === '') {
            throw new InvalidArgumentException(
                'Payment failure code cannot be empty.',
            );
        }

        return new self(false, null, $failureCode);
    }
}
